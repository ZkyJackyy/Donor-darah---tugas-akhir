<?php

namespace App\Jobs;

use App\Models\AdminAlert;
use App\Models\BloodRequest;
use App\Models\DonorCandidate;
use App\Models\User;
use App\Services\DonorFilterService;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class WaveChainJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 60;

    protected int $bloodRequestId;
    protected int $currentWave;

    /**
     * @param int $bloodRequestId ID permintaan darah
     * @param int $currentWave Nomor gelombang saat ini (1, 2, atau 3)
     */
    public function __construct(int $bloodRequestId, int $currentWave)
    {
        $this->bloodRequestId = $bloodRequestId;
        $this->currentWave = $currentWave;
    }

    /**
     * Handle: trigger broadcast gelombang saat ini, lalu chain ke gelombang berikutnya
     * jika kuota belum terpenuhi.
     *
     * Flow:
     *   Wave 1 → delay N menit (tergantung urgency_level) → cek quota → Wave 2 → ... → Wave 3
     */
    public function handle(DonorFilterService $filterService, WhatsAppService $waService): void
    {
        $request = BloodRequest::find($this->bloodRequestId);

        if (!$request || $request->status !== 'open') {
            Log::info("WaveChain: Request #{$this->bloodRequestId} tidak lagi open, skip.");
            return;
        }

        // Cek kuota saat ini
        $confirmedCount = DonorCandidate::where('blood_request_id', $this->bloodRequestId)
            ->whereIn('status', ['confirmed', 'verified'])
            ->count();

        if ($confirmedCount >= $request->required_bags) {
            Log::info("WaveChain: Request #{$this->bloodRequestId} sudah terpenuhi ({$confirmedCount}/{$request->required_bags}), stop.");
            return;
        }

        // Filter donor untuk gelombang ini
        $eligibleDonors = $filterService->filterEligibleDonors($request, $this->currentWave);
        $notifiedCount = 0;

        if ($eligibleDonors->isEmpty()) {
            Log::info("WaveChain: Tidak ada donor eligible di gelombang {$this->currentWave} untuk request #{$this->bloodRequestId}");
        } else {
            $candidates = collect();
            $users = User::whereIn('id', $eligibleDonors->pluck('id'))->get()->keyBy('id');

            foreach ($eligibleDonors as $donor) {
                $candidate = DonorCandidate::firstOrCreate([
                    'blood_request_id' => $this->bloodRequestId,
                    'user_id' => $donor->id,
                ], [
                    'distance_km' => $donor->distance_km,
                    'status' => 'notified',
                    'notified_at' => now(),
                ]);

                // Skip donors already notified by a previous attempt of this job
                // (e.g. a retry after failure) to avoid duplicate WA broadcasts.
                if (!$candidate->wasRecentlyCreated) {
                    continue;
                }

                $candidate->setRelation('user', $users->get($donor->id));
                $candidates->push($candidate);
            }

            if ($candidates->isNotEmpty()) {
                $waService->notifyAllCandidates($candidates, $request, $this->currentWave);
                $notifiedCount = $candidates->count();
                Log::info("WaveChain: Gelombang {$this->currentWave} → {$notifiedCount} notifikasi dikirim untuk request #{$this->bloodRequestId}");
            } else {
                Log::info("WaveChain: Gelombang {$this->currentWave} untuk request #{$this->bloodRequestId} — semua kandidat sudah pernah dinotifikasi, skip.");
            }
        }

        // Wave 1 dipicu manual oleh admin (tombol "Kirim Notifikasi WA") dan
        // langsung dapat flash message di halaman yang sama — hanya wave 2/3
        // yang jalan sendiri di background lewat scheduler/delay tanpa ada
        // admin yang menunggu, jadi cuma wave itu yang perlu dicatat di sini
        // supaya admin tahu itu sudah terjadi tanpa harus buka halaman detail.
        if ($this->currentWave > 1) {
            $message = $notifiedCount > 0
                ? "Gelombang {$this->currentWave} untuk permintaan darah di {$request->hospital_name} telah dikirim ke {$notifiedCount} pendonor baru."
                : "Gelombang {$this->currentWave} untuk permintaan darah di {$request->hospital_name} sudah berjalan, tapi tidak ada pendonor baru yang eligible di radius ini.";

            try {
                AdminAlert::create([
                    'type' => "wave_{$this->currentWave}_sent_{$this->bloodRequestId}",
                    'message' => $message,
                    'blood_request_id' => $this->bloodRequestId,
                ]);
            } catch (UniqueConstraintViolationException $e) {
                // Already alerted for this wave/request — nothing to do.
            }
        }

        // Chain ke gelombang berikutnya (max 3)
        if ($this->currentWave < 3) {
            $nextWave = $this->currentWave + 1;
            $delayMinutes = config('donorconnect.wave_delay_minutes')[$request->urgency_level]
                ?? config('donorconnect.wave_delay_minutes.normal');
            static::dispatch($this->bloodRequestId, $nextWave)
                ->delay(now()->addMinutes($delayMinutes));
            Log::info("WaveChain: Gelombang {$nextWave} scheduled dalam {$delayMinutes} menit untuk request #{$this->bloodRequestId}");
        }
    }
}
