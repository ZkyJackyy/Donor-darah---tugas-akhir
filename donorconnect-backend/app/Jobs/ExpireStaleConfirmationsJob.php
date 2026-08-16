<?php

namespace App\Jobs;

use App\Models\BloodRequest;
use App\Models\DonorCandidate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExpireStaleConfirmationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 60;

    /**
     * Handle: kandidat berstatus 'confirmed' yang kode verifikasinya sudah
     * kadaluarsa tanpa pernah discan admin (no-show) tetap dihitung sebagai
     * slot kuota terisi oleh DonorActionController::confirm() dan
     * WaveChainJob, sehingga bisa mengunci permintaan darah "penuh" secara
     * keliru selamanya. Job ini menandai mereka 'expired' (dikeluarkan dari
     * hitungan kuota) lalu memicu ulang gelombang notifikasi kalau kuota
     * ternyata belum benar-benar terpenuhi.
     *
     * Dijalankan oleh scheduler setiap 5 menit.
     */
    public function handle(): void
    {
        // Gunakan kolom expires_at langsung alih-alih menghitung ulang dari confirmed_at
        // Ini memastikan konsistensi jika nilai durasi expiry (confirmation_expiry_minutes)
        // di config berubah di kemudian hari.
        $staleCandidates = DonorCandidate::where('status', 'confirmed')
            ->where('expires_at', '<=', now())
            ->get();

        if ($staleCandidates->isEmpty()) {
            return;
        }

        $affectedRequestIds = $staleCandidates->pluck('blood_request_id')->unique();

        foreach ($staleCandidates as $candidate) {
            $candidate->update(['status' => 'expired']);
        }

        Log::info("ExpireStaleConfirmations: {$staleCandidates->count()} kandidat ditandai expired (no-show).");

        foreach ($affectedRequestIds as $requestId) {
            $request = BloodRequest::find($requestId);

            if (!$request || $request->status !== 'open' || $request->isEvent() || $request->required_bags === null) {
                continue;
            }

            $confirmedCount = DonorCandidate::where('blood_request_id', $requestId)
                ->whereIn('status', ['confirmed', 'verified'])
                ->count();

            if ($confirmedCount >= $request->required_bags) {
                continue;
            }

            // Re-scan gelombang terluas (3) untuk menjaring donor yang belum
            // pernah jadi kandidat — aman dipanggil berkali-kali karena
            // WaveChainJob melewati donor yang sudah pernah dinotifikasi.
            WaveChainJob::dispatch($requestId, 3);
            Log::info("ExpireStaleConfirmations: Request #{$requestId} re-trigger gelombang 3 (kuota belum terpenuhi setelah expiry).");
        }
    }
}
