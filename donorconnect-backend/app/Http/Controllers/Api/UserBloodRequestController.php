<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BloodRequest;
use App\Models\DonorCandidate;
use App\Models\DonorHistory;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class UserBloodRequestController extends Controller
{
    use ApiResponse;

    /**
     * Get a list of open blood requests.
     */
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $requests = BloodRequest::with(['donorCandidates' => function ($query) use ($userId) {
            $query->where('user_id', $userId);
        }])->where('status', 'open')
            ->orderBy('id', 'desc')
            ->get();

        $formattedRequests = $requests->map(function ($bloodRequest) {
            $candidateStatus = null;
            $verifiedAt = null;
            if ($bloodRequest->donorCandidates->isNotEmpty()) {
                $candidate = $bloodRequest->donorCandidates->first();
                $candidateStatus = $candidate->status;
                $verifiedAt = $candidate->verified_at?->toIso8601String();
            }

            $data = $bloodRequest->toArray();
            unset($data['donor_candidates']);

            $data['user_candidate_info'] = [
                'is_candidate' => $candidateStatus !== null,
                'status' => $candidateStatus,
                'verified_at' => $verifiedAt,
            ];

            return $data;
        });

        return $this->success($formattedRequests, 'List of open blood requests fetched successfully');
    }

    /**
     * Show the details of a specific blood request, including the authenticated user's candidate status.
     */
    public function show($id, Request $request)
    {
        $userId = $request->user()->id;

        $bloodRequest = BloodRequest::with(['donorCandidates' => function ($query) use ($userId) {
            $query->where('user_id', $userId);
        }])->findOrFail($id);

        // Only the submitter, an actual candidate, or anyone (once it's
        // publicly open) may view this — otherwise a donor could enumerate
        // /user/blood-requests/{id} to read other families' pending_review
        // or rejected submissions (patient name, rejection reason, referral
        // letter URL).
        $isCandidate = $bloodRequest->donorCandidates->isNotEmpty();
        $isSubmitter = $bloodRequest->requested_by_user_id === $userId;
        $isPubliclyVisible = $bloodRequest->status === 'open';

        if (!$isPubliclyVisible && !$isCandidate && !$isSubmitter) {
            return $this->notFound('Permintaan darah tidak ditemukan');
        }

        // Inject specific candidate data for convenience in the mobile app
        $candidateStatus = null;
        $candidateId = null;
        $verifiedAt = null;
        $confirmedAt = null;
        $expiresAt = null;
        $kodeVerifikasi = null;

        if ($bloodRequest->donorCandidates->isNotEmpty()) {
            $candidate = $bloodRequest->donorCandidates->first();
            $candidateStatus = $candidate->status;
            $candidateId = $candidate->id;
            $verifiedAt = $candidate->verified_at?->toIso8601String();
            $confirmedAt = $candidate->confirmed_at?->toIso8601String();
            $expiresAt = $candidate->expires_at?->toIso8601String();
            $kodeVerifikasi = $candidate->kode_verifikasi;
        }

        // Count how many are currently confirmed to give the frontend an idea of the quota
        $confirmedCount = \App\Models\DonorCandidate::where('blood_request_id', $id)
            ->whereIn('status', ['confirmed', 'verified'])
            ->count();

        $data = $bloodRequest->toArray();
        unset($data['donor_candidates']); // Clean up to avoid raw relationship data
        
        $data['user_candidate_info'] = [
            'is_candidate' => $candidateStatus !== null,
            'candidate_id' => $candidateId,
            'status' => $candidateStatus,
            'verified_at' => $verifiedAt,
            'confirmed_at' => $confirmedAt,
            'expires_at' => $expiresAt,
            'kode_verifikasi' => $kodeVerifikasi,
        ];
        
        $data['quota'] = [
            'required' => $bloodRequest->required_bags,
            'confirmed' => $confirmedCount,
            'is_full' => $confirmedCount >= $bloodRequest->required_bags
        ];

        return $this->success($data, 'Blood request details fetched successfully');
    }

    /**
     * User mengajukan permintaan donor pengganti untuk keluarga.
     * Masuk sebagai 'pending_review' — belum trigger pencarian pendonor
     * sampai admin PMI memvalidasi (approve) lewat panel admin.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'blood_type' => 'required|in:A,B,AB,O',
            'rhesus' => 'required|in:+,-',
            'required_bags' => 'required|integer|min:1',
            'patient_name' => 'required|string|max:255',
            'patient_relationship' => 'required|string|max:100',
            'hospital_name' => 'required|string|max:255',
            'hospital_address' => 'required|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'urgency_level' => 'required|in:normal,urgent,critical',
            'deadline' => 'required|date|after:now',
            'notes' => 'nullable|string',
            'referral_letter' => 'required|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        if (empty($validated['latitude']) || empty($validated['longitude'])) {
            $validated['latitude'] = config('donorconnect.default_lat');
            $validated['longitude'] = config('donorconnect.default_lng');
        }

        $userId = $request->user()->id;

        // Guard against the request actually succeeding server-side while a
        // flaky mobile connection drops the response before it reaches the
        // app — the app shows an error and the user taps "Kirim" again,
        // otherwise producing two pending_review submissions for the same
        // patient. Check-then-create runs inside an atomic cache lock keyed
        // on every field that distinguishes one submission from another, so
        // a genuine retry serializes behind the in-flight/just-committed
        // original instead of racing past the SELECT before it commits.
        $deadlineForMatch = Carbon::parse($validated['deadline'])->format('Y-m-d H:i:s');

        $duplicateKey = 'user_blood_request_duplicate:' . $userId . ':' . md5(implode("\x1f", [
            $validated['blood_type'],
            $validated['rhesus'],
            $validated['required_bags'],
            $validated['patient_name'],
            $validated['patient_relationship'],
            $validated['hospital_name'],
            $validated['hospital_address'],
            $validated['urgency_level'],
            $deadlineForMatch,
            $validated['notes'] ?? '',
        ]));

        $duplicateWindowSeconds = config('donorconnect.user_blood_request_duplicate_window_seconds');

        try {
            $bloodRequest = Cache::lock($duplicateKey, 15)->block(5, function () use ($request, $validated, $userId, $deadlineForMatch, $duplicateWindowSeconds) {
                $duplicate = BloodRequest::where('requested_by_user_id', $userId)
                    ->where('blood_type', $validated['blood_type'])
                    ->where('rhesus', $validated['rhesus'])
                    ->where('required_bags', $validated['required_bags'])
                    ->where('patient_name', $validated['patient_name'])
                    ->where('patient_relationship', $validated['patient_relationship'])
                    ->where('hospital_name', $validated['hospital_name'])
                    ->where('hospital_address', $validated['hospital_address'])
                    ->where('urgency_level', $validated['urgency_level'])
                    ->where('deadline', $deadlineForMatch)
                    ->where('notes', $validated['notes'] ?? null)
                    ->where('created_at', '>=', now()->subSeconds($duplicateWindowSeconds))
                    ->latest()
                    ->first();

                if ($duplicate) {
                    return $duplicate;
                }

                // Disimpan di disk 'local' (bukan 'public') — surat rujukan berisi
                // info pasien, tidak boleh punya URL yang bisa diakses publik
                // tanpa login. Hanya bisa dilihat admin lewat route
                // admin.blood-requests.referral-letter.
                $referralLetterPath = $request->file('referral_letter')->store('referral-letters', 'local');

                return BloodRequest::create([
                    ...collect($validated)->except('referral_letter')->all(),
                    'type' => 'emergency',
                    'status' => 'pending_review',
                    'requested_by_user_id' => $userId,
                    'admin_id' => null,
                    'referral_letter_path' => $referralLetterPath,
                ]);
            });
        } catch (LockTimeoutException) {
            return $this->error('Pengajuan sedang diproses, silakan coba lagi.', 429);
        }

        return $this->success($bloodRequest, 'Pengajuan berhasil dikirim, menunggu persetujuan admin PMI', 201);
    }

    /**
     * Daftar pengajuan permintaan donor pengganti milik user (semua status).
     */
    public function mySubmissions(Request $request)
    {
        $submissions = BloodRequest::where('requested_by_user_id', $request->user()->id)
            ->orderByDesc('id')
            ->get();

        return $this->success($submissions, 'Daftar pengajuan berhasil diambil');
    }

    /**
     * Riwayat partisipasi user sebagai pendonor.
     * Menggabungkan: kandidat (notified/confirmed/verified/declined) + donor history (terverifikasi).
     */
    public function history(Request $request)
    {
        $userId = $request->user()->id;

        // Ambil semua kandidat user beserta blood request
        $candidates = DonorCandidate::with('bloodRequest')
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($candidate) {
                return [
                    'id' => $candidate->id,
                    'blood_request_id' => $candidate->blood_request_id,
                    'hospital_name' => $candidate->bloodRequest?->hospital_name ?? '-',
                    'blood_type' => $candidate->bloodRequest?->blood_type ?? '-',
                    'rhesus' => $candidate->bloodRequest?->rhesus ?? '-',
                    'status' => $candidate->status,
                    'distance_km' => $candidate->distance_km,
                    'created_at' => $candidate->created_at->toIso8601String(),
                ];
            });

        // Ambil donor history (yang sudah terverifikasi)
        $histories = DonorHistory::where('user_id', $userId)
            ->orderByDesc('donor_date')
            ->get()
            ->map(function ($history) {
                return [
                    'id' => $history->id,
                    'blood_request_id' => $history->blood_request_id,
                    'hospital_name' => $history->location_name,
                    'donor_date' => $history->donor_date->format('d M Y'),
                    'verified_by' => $history->verifier?->name ?? '-',
                    'type' => 'history',
                ];
            });

        return $this->success([
            'candidates' => $candidates,
            'histories' => $histories,
        ], 'Riwayat donor berhasil diambil');
    }
}
