<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConfirmCandidateRequest;
use App\Http\Requests\ScreeningRequest;
use App\Http\Resources\DonorHistoryResource;
use App\Models\DonorCandidate;
use App\Models\DonorHistory;
use App\Models\DonorScreening;
use App\Models\BloodRequest;
use App\Traits\ApiResponse;
use Illuminate\Database\QueryException;
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

        // Atomic quota check inside transaction to prevent race condition
        if ($request->status === 'confirmed') {
            $result = DB::transaction(function () use ($candidate) {
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

                return $bloodRequest;
            });

            if ($result === 'closed') {
                return $this->error('Permintaan ini sudah tidak menerima konfirmasi baru (status saat ini bukan open).', 400);
            }

            if ($result === 'full') {
                return $this->error('Kuota pendonor sudah penuh untuk permintaan ini', 400);
            }
        }

        $kodeVerifikasi = null;
        $expiresAt = null;
        $confirmedAt = null;
        if ($request->status === 'confirmed') {
            $confirmedAt = now();
            $expiresAt = $confirmedAt->copy()->addMinutes(config('donorconnect.confirmation_expiry_minutes', 120));
            $kodeVerifikasi = DonorCandidate::generateVerificationCode();
        }

        $updateData = [
            'status' => $request->status,
            'confirmed_at' => $confirmedAt,
            'kode_verifikasi' => $kodeVerifikasi,
        ];

        // The DB has a unique index on kode_verifikasi, but generation and
        // this update aren't atomic, so two concurrent confirmations could
        // race onto the same code between the exists() check and here.
        // Retry with a freshly generated code on that specific collision
        // instead of surfacing a 500 to the donor.
        $maxAttempts = 5;
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $candidate->update($updateData);
                break;
            } catch (QueryException $e) {
                $isDuplicateCode = $request->status === 'confirmed'
                    && str_contains($e->getMessage(), 'kode_verifikasi');
                if (!$isDuplicateCode || $attempt === $maxAttempts) {
                    throw $e;
                }
                $updateData['kode_verifikasi'] = DonorCandidate::generateVerificationCode();
            }
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
                ->where('status', 'confirmed')->count();
            $declinedCount = DonorCandidate::where('blood_request_id', $candidate->blood_request_id)
                ->where('status', 'declined')->count();

            if ($pendingCount === 0 && $confirmedCount === 0 && $declinedCount > 0) {
                $candidate->load('bloodRequest');
                app(\App\Services\WhatsAppService::class)->notifyAdminAllDeclined($candidate->bloodRequest);
            }
        }

        // Auto-transition to fulfilled if quota of verified candidates met
        $candidate->bloodRequest->checkAndAutoFulfill();

        return $this->success([
            'status' => $candidate->status,
            'kode_verifikasi' => $updateData['kode_verifikasi'],
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

        // Only allow screening for candidates in notified or pending status
        if (!in_array($candidate->status, ['notified', 'pending'])) {
            return $this->error('Kandidat tidak dapat melakukan skrining dengan status saat ini', 400);
        }

        // Create or update screening record
        $screening = DonorScreening::updateOrCreate(
            ['donor_candidate_id' => $candidate->id],
            [
                ...$request->validated(),
                'screened_at' => now(),
            ]
        );

        // Update candidate status to screening_passed agar frontend bisa lanjut ke konfirmasi
        $candidate->update(['status' => 'screening_passed']);

        return $this->success([
            'screening_id' => $screening->id,
            'completed' => true,
        ], 'Self-assessment screening completed successfully');
    }

}
