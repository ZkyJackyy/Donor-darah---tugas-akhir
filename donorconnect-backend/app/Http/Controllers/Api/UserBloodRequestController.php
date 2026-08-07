<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BloodRequest;
use App\Models\DonorCandidate;
use App\Models\DonorHistory;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

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

        // Inject specific candidate data for convenience in the mobile app
        $candidateStatus = null;
        $candidateId = null;
        $verifiedAt = null;
        $confirmedAt = null;
        $kodeVerifikasi = null;

        if ($bloodRequest->donorCandidates->isNotEmpty()) {
            $candidate = $bloodRequest->donorCandidates->first();
            $candidateStatus = $candidate->status;
            $candidateId = $candidate->id;
            $verifiedAt = $candidate->verified_at?->toIso8601String();
            $confirmedAt = $candidate->confirmed_at?->toIso8601String();
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
        ]);

        if (empty($validated['latitude']) || empty($validated['longitude'])) {
            $validated['latitude'] = config('donorconnect.default_lat');
            $validated['longitude'] = config('donorconnect.default_lng');
        }

        $bloodRequest = BloodRequest::create([
            ...$validated,
            'type' => 'emergency',
            'status' => 'pending_review',
            'requested_by_user_id' => $request->user()->id,
            'admin_id' => null,
        ]);

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
