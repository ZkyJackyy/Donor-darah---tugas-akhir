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
            $data['hospital_name'] = config('donorconnect.default_hospital_name');
            $data['hospital_address'] = config('donorconnect.default_hospital_address');
            $data['latitude'] = config('donorconnect.default_lat');
            $data['longitude'] = config('donorconnect.default_lng');
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
        if (!in_array($bloodRequest->status, ['approved', 'open'], true)) {
            return $this->error("Permintaan ini berstatus '{$bloodRequest->status}' — tidak bisa mengirim broadcast WA lagi.", 400);
        }

        if ($bloodRequest->status === 'approved') {
            $bloodRequest->update(['status' => 'open']);
        }

        if ($bloodRequest->isEvent()) {
            $donors = \App\Models\User::where('is_available', true)->where('role', 'user')->get();
            $waService->announceEvent($donors, $bloodRequest);

            return $this->success(null, "Successfully queued event announcement for {$donors->count()} donor(s).");
        }

        $eligibleDonors = $filterService->filterEligibleDonors($bloodRequest);
        $candidates = collect();

        foreach ($eligibleDonors as $donor) {
            $candidate = DonorCandidate::firstOrCreate([
                'blood_request_id' => $bloodRequest->id,
                'user_id' => $donor->id,
            ], [
                'distance_km' => $donor->distance_km,
                'status' => 'notified',
                'notified_at' => now(),
            ]);

            $candidate->setRelation('user', \App\Models\User::find($donor->id));
            $candidates->push($candidate);
        }

        $waService->notifyAllCandidates($candidates, $bloodRequest);

        // Chain wave 2/3 otomatis: jika kuota belum terpenuhi, wave berikutnya jalan
        \App\Jobs\WaveChainJob::dispatch($bloodRequest->id, 2)->delay(now()->addMinutes(15));

        return $this->success(null, "Successfully queued WhatsApp notifications for {$candidates->count()} eligible donors (Wave 1 started).");
    }

    public function verify(VerifyCandidateRequest $request, DonorCandidate $candidate)
    {
        $error = \Illuminate\Support\Facades\DB::transaction(function () use ($candidate, $request) {
            $lockedCandidate = DonorCandidate::with('user', 'bloodRequest')->lockForUpdate()->find($candidate->id);

            if (!$lockedCandidate) {
                return 'Candidate not found.';
            }

            if ($lockedCandidate->status === 'verified') {
                return 'Candidate already verified';
            }

            if ($lockedCandidate->status !== 'confirmed') {
                return "Candidate status is '{$lockedCandidate->status}' — candidate must confirm attendance before being verified.";
            }

            if ($lockedCandidate->bloodRequest->status !== 'open') {
                return "Blood request status is '{$lockedCandidate->bloodRequest->status}' — candidate can no longer be verified.";
            }

            if ($lockedCandidate->expires_at && $lockedCandidate->expires_at->isPast()) {
                return 'Konfirmasi kandidat ini sudah kadaluarsa — tidak bisa diverifikasi lagi.';
            }

            $lockedCandidate->update([
                'status' => 'verified',
                'verified_at' => now(),
                'verification_method' => $request->method
            ]);

            // Create historical record
            DonorHistory::create([
                'user_id' => $lockedCandidate->user_id,
                'blood_request_id' => $lockedCandidate->blood_request_id,
                'donor_date' => now()->toDateString(),
                'location_name' => $lockedCandidate->bloodRequest->hospital_name,
                'verified_by' => auth()->id()
            ]);

            // Update user's last donor date to trigger their cooldown
            $lockedCandidate->user->update([
                'last_donor_date' => now()->toDateString(),
                'is_available' => false // Lock user from filter
            ]);

            // Auto-transition to fulfilled if quota of verified candidates met;
            // notify family requester via WA if triggered.
            if ($lockedCandidate->bloodRequest->checkAndAutoFulfill()) {
                app(\App\Services\WhatsAppService::class)->notifyRequesterFulfilled($lockedCandidate->bloodRequest);
            }

            return null;
        });

        if ($error) {
            return $this->error($error, 400);
        }

        return $this->success(null, 'Candidate manually verified and history updated.');
    }

    public function verifyByCode(Request $request)
    {
        $request->validate([
            'kode_verifikasi' => 'required|string|exists:donor_candidates,kode_verifikasi',
        ]);

        $invalidMessage = 'Kode verifikasi tidak valid atau sudah kadaluarsa.';

        $result = \Illuminate\Support\Facades\DB::transaction(function () use ($request, $invalidMessage) {
            $candidate = DonorCandidate::with('user', 'bloodRequest')
                ->where('kode_verifikasi', strtoupper($request->kode_verifikasi))
                ->lockForUpdate()
                ->first();

            if (!$candidate) {
                return ['error' => 'Kode verifikasi tidak valid'];
            }

            if ($candidate->status === 'verified') {
                return ['error' => 'Kandidat sudah terverifikasi'];
            }

            if ($candidate->status !== 'confirmed') {
                return ['error' => "Status kandidat '{$candidate->status}' — belum bisa diverifikasi"];
            }

            if ($candidate->bloodRequest->status !== 'open') {
                return ['error' => "Permintaan ini berstatus '{$candidate->bloodRequest->status}' — kandidat tidak bisa diverifikasi lagi."];
            }

            $expiryMinutes = config('donorconnect.confirmation_expiry_minutes', 120);
            if ($candidate->confirmed_at && $candidate->confirmed_at->copy()->addMinutes($expiryMinutes)->isPast()) {
                return ['error' => $invalidMessage];
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

            // Auto-transition to fulfilled if quota of verified candidates met;
            // notify family requester via WA if triggered.
            if ($candidate->bloodRequest->checkAndAutoFulfill()) {
                app(\App\Services\WhatsAppService::class)->notifyRequesterFulfilled($candidate->bloodRequest);
            }

            return [
                'candidate' => [
                    'id' => $candidate->id,
                    'name' => $candidate->user->name,
                    'blood_type' => $candidate->user->blood_type,
                ],
                'message' => "Pendonor {$candidate->user->name} berhasil diverifikasi via kode."
            ];
        });

        if (isset($result['error'])) {
            return $this->error($result['error'], 400);
        }

        return $this->success(['candidate' => $result['candidate']], $result['message']);
    }
}
