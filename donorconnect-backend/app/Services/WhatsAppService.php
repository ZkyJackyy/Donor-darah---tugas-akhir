<?php

namespace App\Services;

use App\Models\BloodRequest;
use App\Models\User;
use Illuminate\Support\Collection;
use App\Jobs\SendDonorNotificationJob;

class WhatsAppService
{
    /**
     * Generate standard Fonnte WA message mapped to Donor Requirement template
     */
    public function sendDonorRequest(User $user, BloodRequest $request, float $distanceKm, int $wave = 1): void
    {
        $urgencyBadge = strtoupper($request->urgency_level);
        $distance = round($distanceKm, 2);
        $waveInfo = $wave > 1 ? " (Gelombang {$wave})" : "";

        $message = "🩸 *BUTUH DONOR DARAH - [{$urgencyBadge}]{$waveInfo}*\n\n"
                 . "Halo {$user->name}, Anda teridentifikasi sebagai pendonor \n"
                 . "golongan darah {$request->blood_type}{$request->rhesus} terdekat ({$distance} km).\n\n"
                 . "📍 RS/Lokasi : {$request->hospital_name}\n"
                 . "🏥 Alamat    : {$request->hospital_address}\n"
                 . "🩸 Kebutuhan : {$request->required_bags} kantong\n"
                 . "⏳ Batas Waktu: {$request->deadline}\n\n"
                 . "Apakah Anda bersedia membantu?\n"
                 . "Buka aplikasi untuk konfirmasi:\n"
                 . "👉 donorpmi://permintaan/{$request->id}\n\n"
                 . "Balas pesan ini atau buka aplikasi.";

        SendDonorNotificationJob::dispatch($user, $message, $request->id);
    }

    /**
     * Dispatch WhatsApp blasts to all eligible donor candidates
     *
     * @param Collection $candidates
     * @param BloodRequest $request
     * @param int $wave Wave number (1, 2, or 3)
     */
    public function notifyAllCandidates(Collection $candidates, BloodRequest $request, int $wave = 1): void
    {
        foreach ($candidates as $candidate) {
            $user = $candidate->user ?? $candidate;

            // Duplicate notification guard: 24h cache locking
            $cacheKey = "notify_{$user->id}_{$request->id}";
            if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                continue;
            }
            \Illuminate\Support\Facades\Cache::put($cacheKey, true, now()->addHours(24));

            $distanceFloat = (float) ($candidate->distance_km ?? 0);
            $this->sendDonorRequest($user, $request, $distanceFloat, $wave);
        }
    }

    public function notifyAdminAllDeclined(BloodRequest $request): void
    {
        $admins = User::where('role', 'admin')->get();
        foreach($admins as $admin) {
            $message = "⚠️ *URGENT ALERT*\n\n"
             . "Semua kandidat pendonor untuk request #{$request->id} di {$request->hospital_name} telah MENOLAK.\n"
             . "Mohon lakukan tindakan verifikasi manual segera.";

            SendDonorNotificationJob::dispatch($admin, $message, $request->id);
        }
    }
}
