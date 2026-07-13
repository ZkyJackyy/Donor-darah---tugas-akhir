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

        // Atomic quota check inside transaction to prevent race condition
        if ($request->status === 'confirmed') {
            $result = DB::transaction(function () use ($candidate) {
                // Lock the blood request row for update
                $bloodRequest = BloodRequest::where('id', $candidate->blood_request_id)
                    ->lockForUpdate()
                    ->first();

                $confirmedCount = DonorCandidate::where('blood_request_id', $candidate->blood_request_id)
                    ->where('status', 'confirmed')
                    ->count();

                if ($confirmedCount >= $bloodRequest->required_bags) {
                    return null; // Quota full
                }

                return $bloodRequest;
            });

            if ($result === null) {
                return $this->error('Kuota pendonor sudah penuh untuk permintaan ini', 400);
            }
        }

        $qrToken = null;
        if ($request->status === 'confirmed') {
            $payload = json_encode([
                'candidate_id' => $candidate->id,
                'user_id' => $candidate->user_id,
                'request_id' => $candidate->blood_request_id,
                'expires_at' => now()->addHours(2)->timestamp
            ]);
            $qrToken = hash_hmac('sha256', $payload, config('app.key')) . '|' . base64_encode($payload);
        }

        $candidate->update([
            'status' => $request->status,
            'confirmed_at' => $request->status === 'confirmed' ? now() : null,
            'qr_token' => $qrToken
        ]);

        if ($request->status === 'declined') {
            $totalCandidates = DonorCandidate::where('blood_request_id', $candidate->blood_request_id)->count();
            $declinedCount = DonorCandidate::where('blood_request_id', $candidate->blood_request_id)
                ->where('status', 'declined')->count();

            if ($totalCandidates > 0 && $totalCandidates === $declinedCount) {
                $candidate->load('bloodRequest');
                app(\App\Services\WhatsAppService::class)->notifyAdminAllDeclined($candidate->bloodRequest);
            }
        }

        // Auto-transition to fulfilled if quota met
        $this->checkAndFulfillRequest($candidate->blood_request_id);

        return $this->success([
            'status' => $candidate->status,
            'qr_token' => $qrToken
        ], 'Donor status updated successfully');
    }

    public function qrCode(DonorCandidate $candidate)
    {
        if ($candidate->user_id !== auth()->id() || $candidate->status !== 'confirmed') {
            return $this->forbidden('Unauthorized or invalid status');
        }

        return $this->success([
            'qr_token' => $candidate->qr_token
        ]);
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

    private function checkAndFulfillRequest(int $bloodRequestId): void
    {
        $bloodRequest = BloodRequest::findOrFail($bloodRequestId);
        $confirmedCount = DonorCandidate::where('blood_request_id', $bloodRequestId)
            ->where('status', 'confirmed')
            ->count();

        if ($confirmedCount >= $bloodRequest->required_bags && $bloodRequest->status === 'open') {
            $bloodRequest->update(['status' => 'fulfilled']);
        }
    }
}
