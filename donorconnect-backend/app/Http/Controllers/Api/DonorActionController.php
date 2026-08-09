<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConfirmCandidateRequest;
use App\Http\Requests\ScreeningRequest;
use App\Http\Resources\DonorHistoryResource;
use App\Models\AdminAlert;
use App\Models\DonorCandidate;
use App\Models\DonorHistory;
use App\Models\DonorScreening;
use App\Models\BloodRequest;
use App\Traits\ApiResponse;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class DonorActionController extends Controller
{
    use ApiResponse;
    public function confirm(ConfirmCandidateRequest $request)
    {
        $candidate = DonorCandidate::where('id', $request->donor_candidate_id)
            ->where('user_id', $request->user()->id)
            ->with('bloodRequest')
            ->firstOrFail();

        if ($request->status === 'confirmed' && $candidate->status !== 'screening_passed') {
            return $this->error('Anda harus menyelesaikan skrining mandiri terlebih dahulu sebelum konfirmasi kesediaan.', 400);
        }

        $kodeVerifikasi = null;
        $expiresAt = null;

        if ($request->status === 'confirmed') {
            $confirmedAt = now();
            $expiresAt = $confirmedAt->copy()->addMinutes(config('donorconnect.confirmation_expiry_minutes', 120));

            // Quota check-and-write must happen atomically inside the same
            // lock: doing the count and the status write in separate
            // transactions let two concurrent confirmations both pass the
            // check before either wrote, over-filling required_bags.
            $result = DB::transaction(function () use ($candidate, $confirmedAt) {
                // Lock the blood request row for update
                $bloodRequest = BloodRequest::where('id', $candidate->blood_request_id)
                    ->lockForUpdate()
                    ->first();

                if ($bloodRequest->status !== 'open') {
                    return 'closed';
                }

                // Count 'confirmed' AND 'verified' — a candidate that has
                // already been verified still occupies a quota slot, so
                // excluding it would let the request over-fill once one
                // donor gets verified before the rest confirm.
                $confirmedCount = DonorCandidate::where('blood_request_id', $candidate->blood_request_id)
                    ->whereIn('status', ['confirmed', 'verified'])
                    ->count();

                if ($confirmedCount >= $bloodRequest->required_bags) {
                    return 'full';
                }

                // The DB has a unique index on kode_verifikasi, but
                // generation and this update aren't atomic, so two
                // concurrent confirmations could race onto the same code.
                // Retry with a freshly generated code on that specific
                // collision instead of surfacing a 500 to the donor.
                $kode = DonorCandidate::generateVerificationCode();
                $maxAttempts = 5;
                for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                    try {
                        $candidate->update([
                            'status' => 'confirmed',
                            'confirmed_at' => $confirmedAt,
                            'kode_verifikasi' => $kode,
                        ]);
                        break;
                    } catch (QueryException $e) {
                        $isDuplicateCode = str_contains($e->getMessage(), 'kode_verifikasi');
                        if (!$isDuplicateCode || $attempt === $maxAttempts) {
                            throw $e;
                        }
                        $kode = DonorCandidate::generateVerificationCode();
                    }
                }

                return $kode;
            });

            if ($result === 'closed') {
                return $this->error('Permintaan ini sudah tidak menerima konfirmasi baru (status saat ini bukan open).', 400);
            }

            if ($result === 'full') {
                return $this->error('Kuota pendonor sudah penuh untuk permintaan ini', 400);
            }

            $kodeVerifikasi = $result;
        } else {
            if ($candidate->status === 'verified') {
                return $this->error('Donor ini sudah diverifikasi selesai mendonor dan tidak dapat dibatalkan lagi.', 400);
            }

            $candidate->update(['status' => $request->status, 'confirmed_at' => null, 'kode_verifikasi' => null]);
        }

        if ($request->status === 'declined') {
            // Alert admin once no candidate is still awaiting a response and
            // nobody has confirmed yet — this catches candidates stuck in
            // 'notified'/'screening_passed'/'no_response' too, not just the
            // case where literally every candidate explicitly declined.
            $pendingCount = DonorCandidate::where('blood_request_id', $candidate->blood_request_id)
                ->whereIn('status', ['notified', 'screening_passed'])
                ->count();
            $confirmedCount = DonorCandidate::where('blood_request_id', $candidate->blood_request_id)
                ->whereIn('status', ['confirmed', 'verified'])->count();
            $declinedCount = DonorCandidate::where('blood_request_id', $candidate->blood_request_id)
                ->where('status', 'declined')->count();

            if ($pendingCount === 0 && $confirmedCount === 0 && $declinedCount > 0) {
                $candidate->load('bloodRequest');

                // Two donors declining near-simultaneously can both reach this
                // point before either insert commits — the unique index on
                // (blood_request_id, type) makes the second attempt fail
                // cleanly instead of producing a duplicate alert.
                try {
                    AdminAlert::create([
                        'type' => 'all_declined',
                        'message' => "Seluruh kandidat pendonor untuk permintaan #{$candidate->blood_request_id} di {$candidate->bloodRequest->hospital_name} telah menolak. Mohon segera melakukan tindak lanjut atau verifikasi manual.",
                        'blood_request_id' => $candidate->blood_request_id,
                    ]);
                } catch (UniqueConstraintViolationException $e) {
                    // Already alerted for this request — nothing to do.
                }
            }
        }

        // Auto-transition to fulfilled if quota of verified candidates met
        $candidate->bloodRequest->checkAndAutoFulfill();

        return $this->success([
            'status' => $candidate->status,
            'kode_verifikasi' => $kodeVerifikasi,
            'hospital_name' => $candidate->bloodRequest->hospital_name,
            'expires_at' => $expiresAt?->toIso8601String(),
        ], 'Donor status updated successfully');
    }

    public function history()
    {
        $histories = DonorHistory::with('verifier')
            ->where('user_id', auth()->id())
            ->orderByDesc('donor_date')
            ->get();

        return $this->success(DonorHistoryResource::collection($histories), 'Donor history fetched successfully');
    }

    public function screening(ScreeningRequest $request)
    {
        $candidate = DonorCandidate::where('id', $request->donor_candidate_id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        // Only allow (re-)screening while notified/pending, or after a previous failed attempt
        // (kondisi seperti obat/kehamilan bisa berubah, jadi user boleh mengulang skrining)
        if (!in_array($candidate->status, ['notified', 'pending', 'screening_failed'])) {
            return $this->error('Kandidat tidak dapat melakukan skrining dengan status saat ini', 400);
        }

        $validated = $request->validated();
        $isEligible = $validated['health_status'] && $validated['min_weight']
            && $validated['no_medicine'] && $validated['not_pregnant'];

        // Simpan jawaban apa adanya (jujur), baik lolos maupun tidak, untuk histori/audit
        $screening = DonorScreening::updateOrCreate(
            ['donor_candidate_id' => $candidate->id],
            [
                ...$validated,
                'screened_at' => now(),
            ]
        );

        $candidate->update(['status' => $isEligible ? 'screening_passed' : 'screening_failed']);

        $message = $isEligible
            ? 'Self-assessment screening completed successfully'
            : 'Anda belum memenuhi syarat untuk mendonor saat ini berdasarkan hasil skrining mandiri';

        return $this->success([
            'screening_id' => $screening->id,
            'completed' => true,
            'eligible' => $isEligible,
        ], $message);
    }

}
