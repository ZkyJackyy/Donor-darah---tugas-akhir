<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBloodRequestRequest;
use App\Http\Requests\VerifyCandidateRequest;
use App\Http\Resources\BloodRequestResource;
use App\Http\Resources\UserResource;
use App\Models\BloodRequest;
use App\Models\DonorCandidate;
use App\Models\DonorHistory;
use App\Services\DonorFilterService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminBloodRequestController extends Controller
{
    use ApiResponse;

    public function index(): AnonymousResourceCollection
    {
        $requests = BloodRequest::with('donorCandidates.user')->latest()->get();
        return BloodRequestResource::collection($requests);
    }

    public function store(StoreBloodRequestRequest $request): BloodRequestResource
    {
        $data = $request->validated();

        // Default to UDD PMI Kota Padang as per AGENTS.md if no location provided
        if (empty($data['hospital_name'])) {
            $data['hospital_name'] = 'UDD PMI Kota Padang';
            $data['hospital_address'] = 'Jl. Sisingamangaraja No.34, Padang';
            $data['latitude'] = -0.9471;
            $data['longitude'] = 100.4172;
        }

        $bloodRequest = BloodRequest::create([
            ...$data,
            'admin_id' => auth()->id(),
            'status' => 'open'
        ]);

        return new BloodRequestResource($bloodRequest);
    }

    public function show(BloodRequest $bloodRequest): BloodRequestResource
    {
        $bloodRequest->load('donorCandidates.user');
        return new BloodRequestResource($bloodRequest);
    }

    public function previewDonors(BloodRequest $bloodRequest, DonorFilterService $filterService)
    {
        $eligibleDonors = $filterService->filterEligibleDonors($bloodRequest);

        return $this->success($eligibleDonors, 'Preview donors retrieved successfully');
    }

    public function notify(BloodRequest $bloodRequest, DonorFilterService $filterService, \App\Services\WhatsAppService $waService)
    {
        // Get all waves of eligible donors
        $waves = $filterService->filterAllWaves($bloodRequest);
        $totalQueued = 0;
        $waveNumber = 0;

        foreach ($waves as $waveNumber => $eligibleDonors) {
            $candidates = collect();

            foreach ($eligibleDonors as $donor) {
                // Ensure we don't duplicate candidates
                $candidate = DonorCandidate::firstOrCreate([
                    'blood_request_id' => $bloodRequest->id,
                    'user_id' => $donor->id,
                ], [
                    'distance_km' => $donor->distance_km,
                    'status' => 'notified',
                    'notified_at' => now(),
                ]);

                // Bind the full user object to pass to WA service
                $candidate->setRelation('user', \App\Models\User::find($donor->id));
                $candidates->push($candidate);
            }

            // Queue notifications for this wave
            if ($candidates->isNotEmpty()) {
                $waService->notifyAllCandidates($candidates, $bloodRequest, $waveNumber);
                $totalQueued += $candidates->count();
            }

            // Stop broadcasting if quota already met
            $confirmedCount = DonorCandidate::where('blood_request_id', $bloodRequest->id)
                ->where('status', 'confirmed')
                ->count();

            if ($confirmedCount >= $bloodRequest->required_bags) {
                break;
            }
        }

        return $this->success(null, "Successfully queued WhatsApp notifications for {$totalQueued} eligible donors across {$waveNumber} wave(s).");
    }

    public function verify(VerifyCandidateRequest $request, DonorCandidate $candidate)
    {
        if ($candidate->status === 'verified') {
            return $this->error('Candidate already verified', 400);
        }

        $candidate->update([
            'status' => 'verified',
            'verified_at' => now(),
            'verification_method' => $request->method
        ]);

        // Create historical record
        DonorHistory::create([
            'user_id' => $candidate->user_id,
            'blood_request_id' => $candidate->blood_request_id,
            'donor_date' => now()->toDateString(),
            'location_name' => $candidate->bloodRequest->hospital_name,
            'verified_by' => auth()->id()
        ]);

        // Update user's last donor date to trigger their cooldown
        $candidate->user->update([
            'last_donor_date' => now()->toDateString(),
            'is_available' => false // Lock user from filter
        ]);

        // Auto-transition to fulfilled if quota met
        $this->checkAndFulfillRequest($candidate->blood_request_id);

        return $this->success(null, 'Candidate manually verified and history updated.');
    }

    public function verifyQr(Request $request)
    {
        $request->validate(['token' => 'required|string']);

        $parts = explode('|', $request->token);
        if (count($parts) !== 2) {
            return $this->error('Invalid QR Token format', 400);
        }

        [$signature, $encodedPayload] = $parts;
        $payload = base64_decode($encodedPayload);

        // Verify HMAC
        $expectedSignature = hash_hmac('sha256', $payload, config('app.key'));
        if (!hash_equals($expectedSignature, $signature)) {
            return $this->error('Invalid QR Signature', 400);
        }

        $data = json_decode($payload, true);

        // Check Expiry
        if (now()->timestamp > $data['expires_at']) {
            return $this->error('QR Token expired', 400);
        }

        $candidate = DonorCandidate::with('user', 'bloodRequest')->findOrFail($data['candidate_id']);

        if ($candidate->status === 'verified') {
            return $this->error('Candidate already verified', 400);
        }

        $candidate->update([
            'status' => 'verified',
            'verified_at' => now(),
            'verification_method' => 'qr'
        ]);

        // Generate History
        DonorHistory::create([
            'user_id' => $candidate->user_id,
            'blood_request_id' => $candidate->blood_request_id,
            'donor_date' => now()->toDateString(),
            'location_name' => $candidate->bloodRequest->hospital_name,
            'verified_by' => auth()->id()
        ]);

        // Lock user from filter
        $candidate->user->update([
            'last_donor_date' => now()->toDateString(),
            'is_available' => false
        ]);

        // Auto-transition to fulfilled if quota met
        $this->checkAndFulfillRequest($candidate->blood_request_id);

        return $this->success(null, 'QR Verification successful. User locked for 56 days.');
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

    public function verifyByCode(Request $request)
    {
        $request->validate([
            'kode_verifikasi' => 'required|string|exists:donor_candidates,kode_verifikasi',
        ]);

        $candidate = DonorCandidate::with('user', 'bloodRequest')
            ->where('kode_verifikasi', $request->kode_verifikasi)
            ->first();

        if (!$candidate) {
            return $this->error('Kode verifikasi tidak valid', 404);
        }

        if ($candidate->status === 'verified') {
            return $this->error('Kandidat sudah terverifikasi', 400);
        }

        if ($candidate->status !== 'confirmed') {
            return $this->error("Status kandidat '{$candidate->status}' — belum bisa diverifikasi", 400);
        }

        $candidate->update([
            'status' => 'verified',
            'verified_at' => now(),
            'verification_method' => 'code'
        ]);

        DonorHistory::create([
            'user_id' => $candidate->user_id,
            'blood_request_id' => $candidate->blood_request_id,
            'donor_date' => now()->toDateString(),
            'location_name' => $candidate->bloodRequest->hospital_name,
            'verified_by' => auth()->id()
        ]);

        $candidate->user->update([
            'last_donor_date' => now()->toDateString(),
            'is_available' => false
        ]);

        $this->checkAndFulfillRequest($candidate->blood_request_id);

        return $this->success([
            'candidate' => [
                'id' => $candidate->id,
                'name' => $candidate->user->name,
                'blood_type' => $candidate->user->blood_type,
            ]
        ], "Pendonor {$candidate->user->name} berhasil diverifikasi via kode.");
    }
}
