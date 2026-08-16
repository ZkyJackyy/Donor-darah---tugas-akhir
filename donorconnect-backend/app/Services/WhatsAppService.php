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
        $urgencyLabel = match ($request->urgency_level) {
            'critical' => 'Darurat',
            'urgent' => 'Mendesak',
            default => 'Normal',
        };
        $distance = round($distanceKm, 2);
        $waveInfo = $wave > 1 ? " (Gelombang {$wave})" : "";

        // Pendonor selalu donor di PMI (skrining/lab cuma ada di sana, darah
        // wajib diproses dulu sebelum dipakai) — bukan di rumah sakit pasien.
        // hospital_name/hospital_address pada $request tetap dipakai sebagai
        // info konteks pasien untuk pengajuan keluarga, bukan tujuan datang.
        $patientInfo = $request->requested_by_user_id
            ? "Untuk pasien di: {$request->hospital_name}\n"
            : "";

        $message = "*Permohonan Donor Darah - {$urgencyLabel}{$waveInfo}*\n\n"
                 . "Halo {$user->name}, kami mohon kesediaan Anda sebagai calon pendonor "
                 . "golongan darah {$request->blood_type}{$request->rhesus} terdekat ({$distance} km dari lokasi).\n\n"
                 . "Lokasi     : " . config('donorconnect.default_hospital_name') . "\n"
                 . "Alamat     : " . config('donorconnect.default_hospital_address') . "\n"
                 . $patientInfo
                 . "Kebutuhan  : {$request->required_bags} kantong\n"
                 . "Batas waktu: {$request->deadline->format('d M Y, H:i')} WIB\n\n"
                 . "Apakah Anda bersedia membantu? Silakan buka aplikasi Sahabat Donor untuk konfirmasi kesediaan Anda.\n\n"
                 . "Terima kasih atas kepedulian Anda.";

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

    /**
     * Broadcast a single open-invitation announcement (event donor darah
     * terbuka) to every available donor — no blood type/distance targeting,
     * no wave. Reuses the same 24h duplicate-notification guard.
     *
     * @param Collection<int, User> $donors
     * @param BloodRequest $request
     */
    public function announceEvent(Collection $donors, BloodRequest $request): void
    {
        foreach ($donors as $donor) {
            $cacheKey = "notify_{$donor->id}_{$request->id}";
            if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                continue;
            }
            \Illuminate\Support\Facades\Cache::put($cacheKey, true, now()->addHours(24));

            $message = "*Ajakan Donor Darah*\n\n"
                     . "Halo {$donor->name}, PMI mengadakan kegiatan donor darah terbuka untuk seluruh golongan darah. "
                     . "Kami mengundang Anda untuk turut berpartisipasi.\n\n"
                     . "Lokasi: {$request->hospital_name}\n"
                     . "Alamat     : {$request->hospital_address}\n"
                     . "Waktu      : {$request->event_starts_at->format('d M Y, H:i')} s.d {$request->deadline->format('d M Y, H:i')}\n\n"
                     . "Mari bergabung dan bantu sesama! Silakan buka aplikasi Sahabat Donor untuk informasi lebih lanjut.\n\n"
                     . "Terima kasih atas partisipasi Anda.";

            SendDonorNotificationJob::dispatch($donor, $message, $request->id);
        }
    }

    /**
     * Kirim notifikasi WA ke anggota keluarga yang mengajukan permintaan darah
     * ketika permintaan tersebut sudah terpenuhi — baik dari stok PMI (manual
     * oleh admin) maupun dari proses verifikasi donor (auto-fulfill).
     *
     * Guard: hanya berlaku jika requested_by_user_id terisi (pengajuan via
     * mobile app). Permintaan yang dibuat langsung oleh admin tidak punya
     * pengaju keluarga, sehingga tidak ada notifikasi yang dikirim.
     */
    public function notifyRequesterFulfilled(BloodRequest $request): void
    {
        if (!$request->requested_by_user_id) {
            return;
        }

        // Idempotency guard: fulfilled notification hanya dikirim sekali per
        // permintaan, bahkan jika method ini dipanggil dari beberapa code path
        // secara hampir bersamaan (misal: dua kandidat terakhir diverifikasi
        // berurutan — post-transaction check keduanya akan melihat status
        // 'fulfilled' dari verifikasi pertama dan sama-sama memanggil method
        // ini). Pola cache ini identik dengan guard di notifyAllCandidates().
        $cacheKey = "fulfilled_notify_{$request->id}";
        if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
            return;
        }
        \Illuminate\Support\Facades\Cache::put($cacheKey, true, now()->addHours(24));

        $requester = \App\Models\User::find($request->requested_by_user_id);

        if (!$requester || !$requester->phone) {
            return;
        }

        $patientInfo = $request->patient_name
            ? "untuk *{$request->patient_name}*"
            : '';

        $message = "*Kabar Baik — Permintaan Darah Terpenuhi* ✅\n\n"
                 . "Halo {$requester->name}, kami dengan senang hati memberitahukan bahwa "
                 . "permintaan darah golongan *{$request->blood_type}{$request->rhesus}* {$patientInfo} "
                 . "di {$request->hospital_name} sudah *terpenuhi*.\n\n"
                 . "Silakan hubungi pihak rumah sakit atau datang langsung ke:\n"
                 . "*" . config('donorconnect.default_hospital_name') . "*\n"
                 . config('donorconnect.default_hospital_address') . "\n\n"
                 . "Terima kasih telah mempercayakan kebutuhan darah kepada kami. "
                 . "Semoga pasien segera pulih. 🙏";

        SendDonorNotificationJob::dispatch($requester, $message, $request->id);
    }
    public function notifyRequesterRejected(BloodRequest $request): void
    {
        if (!$request->requested_by_user_id) {
            return;
        }

        // Idempotency guard
        $cacheKey = "rejected_notify_{$request->id}";
        if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
            return;
        }
        \Illuminate\Support\Facades\Cache::put($cacheKey, true, now()->addHours(24));

        $requester = \App\Models\User::find($request->requested_by_user_id);

        if (!$requester || !$requester->phone) {
            return;
        }

        $patientInfo = $request->patient_name
            ? "untuk pasien *{$request->patient_name}*"
            : '';

        $message = "*Pemberitahuan — Pengajuan Darah Ditolak* ❌\n\n"
                 . "Halo {$requester->name}, mohon maaf, pengajuan darah golongan *{$request->blood_type}{$request->rhesus}* {$patientInfo} "
                 . "yang Anda buat terpaksa kami *tolak* dengan alasan berikut:\n\n"
                 . "_{$request->rejection_reason}_\n\n"
                 . "Jika ada pertanyaan lebih lanjut, silakan hubungi pihak PMI di:\n"
                 . "*" . config('donorconnect.default_hospital_name') . "*\n"
                 . config('donorconnect.default_hospital_address') . "\n\n"
                 . "Terima kasih atas pengertiannya. 🙏";

        SendDonorNotificationJob::dispatch($requester, $message, $request->id);
    }
}
