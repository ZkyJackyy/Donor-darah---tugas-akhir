<?php

namespace App\Jobs;

use App\Models\BloodRequest;
use App\Models\DonorCandidate;
use App\Models\User;
use App\Services\DonorFilterService;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
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
     *   Wave 1 → delay 30 menit → cek quota → Wave 2 → delay 30 menit → cek quota → Wave 3
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
            ->where('status', 'confirmed')
            ->count();

        if ($confirmedCount >= $request->required_bags) {
            Log::info("WaveChain: Request #{$this->bloodRequestId} sudah terpenuhi ({$confirmedCount}/{$request->required_bags}), stop.");
            return;
        }

        // Filter donor untuk gelombang ini
        $eligibleDonors = $filterService->filterEligibleDonors($request, $this->currentWave);

        if ($eligibleDonors->isEmpty()) {
            Log::info("WaveChain: Tidak ada donor eligible di gelombang {$this->currentWave} untuk request #{$this->bloodRequestId}");
        } else {
            $candidates = collect();

            foreach ($eligibleDonors as $donor) {
                $candidate = DonorCandidate::firstOrCreate([
                    'blood_request_id' => $this->bloodRequestId,
                    'user_id' => $donor->id,
                ], [
                    'distance_km' => $donor->distance_km,
                    'status' => 'notified',
                    'notified_at' => now(),
                ]);

                $candidate->setRelation('user', User::find($donor->id));
                $candidates->push($candidate);
            }

            $waService->notifyAllCandidates($candidates, $request, $this->currentWave);
            Log::info("WaveChain: Gelombang {$this->currentWave} → {$candidates->count()} notifikasi dikirim untuk request #{$this->bloodRequestId}");
        }

        // Chain ke gelombang berikutnya (max 3)
        if ($this->currentWave < 3) {
            $nextWave = $this->currentWave + 1;
            static::dispatch($this->bloodRequestId, $nextWave)
                ->delay(now()->addMinutes(30));
            Log::info("WaveChain: Gelombang {$nextWave} scheduled dalam 30 menit untuk request #{$this->bloodRequestId}");
        }
    }
}
