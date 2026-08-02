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
            ->where('status', 'confirmed')
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
     * Self-registration donor untuk permintaan tipe 'event' (donor darah
     * terbuka) — tidak lewat wave/filter golongan darah seperti permintaan
     * 'emergency'. Langsung jadi kandidat 'confirmed' dengan kode verifikasi,
     * tanpa skrining mandiri. Verifikasi kehadiran tetap oleh admin di lokasi.
     */
    public function join(BloodRequest $bloodRequest, Request $request)
    {
        if (!$bloodRequest->isEvent()) {
            return $this->error('Permintaan ini bukan event donor terbuka.', 403);
        }

        if ($bloodRequest->status !== 'open') {
            return $this->error("Permintaan ini berstatus '{$bloodRequest->status}' — tidak bisa didaftarkan lagi.", 400);
        }

        $user = $request->user();

        $alreadyCandidate = DonorCandidate::where('blood_request_id', $bloodRequest->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($alreadyCandidate) {
            return $this->error('Anda sudah terdaftar sebagai kandidat pendonor untuk permintaan ini.', 400);
        }

        if (!$this->isMedicallyEligible($user)) {
            return $this->error('Anda belum memenuhi syarat kesehatan dasar untuk mendonor saat ini (usia, berat badan, atau masa jeda donor terakhir).', 400);
        }

        $confirmedAt = now();
        $expiresAt = $confirmedAt->copy()->addMinutes(config('donorconnect.confirmation_expiry_minutes', 120));
        $kodeVerifikasi = DonorCandidate::generateVerificationCode();

        $candidate = DonorCandidate::create([
            'blood_request_id' => $bloodRequest->id,
            'user_id' => $user->id,
            'distance_km' => null,
            'status' => 'confirmed',
            'notified_at' => $confirmedAt,
            'confirmed_at' => $confirmedAt,
            'kode_verifikasi' => $kodeVerifikasi,
        ]);

        return $this->success([
            'candidate_id' => $candidate->id,
            'status' => $candidate->status,
            'kode_verifikasi' => $kodeVerifikasi,
            'hospital_name' => $bloodRequest->hospital_name,
            'expires_at' => $expiresAt->toIso8601String(),
        ], 'Berhasil mendaftar sebagai pendonor. Tunjukkan kode verifikasi di lokasi.');
    }

    /**
     * Syarat medis dasar (bukan pencocokan golongan darah/jarak) — sama
     * dengan kondisi non-blood-type di DonorFilterService, dicek langsung
     * di PHP karena ini untuk satu user, bukan query massal.
     */
    private function isMedicallyEligible(\App\Models\User $user): bool
    {
        if (!$user->is_available || $user->weight === null || $user->weight < 45 || $user->birth_date === null) {
            return false;
        }

        $age = $user->birth_date->age;
        if ($age < 17 || $age > 60) {
            return false;
        }

        if ($user->last_donor_date !== null) {
            $cooldownDays = config('donorconnect.donation_cooldown_days', 56);
            if (now()->diffInDays($user->last_donor_date) < $cooldownDays) {
                return false;
            }
        }

        return true;
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
