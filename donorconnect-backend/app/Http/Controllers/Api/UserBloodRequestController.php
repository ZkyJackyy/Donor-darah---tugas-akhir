<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BloodRequest;
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
        $requests = BloodRequest::where('status', 'open')
            ->orderBy('id', 'desc')
            ->get();

        return $this->success($requests, 'List of open blood requests fetched successfully');
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
        $qrToken = null;
        $candidateId = null;

        if ($bloodRequest->donorCandidates->isNotEmpty()) {
            $candidate = $bloodRequest->donorCandidates->first();
            $candidateStatus = $candidate->status;
            $qrToken = $candidate->qr_token;
            $candidateId = $candidate->id;
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
            'qr_token' => $qrToken
        ];
        
        $data['quota'] = [
            'required' => $bloodRequest->required_bags,
            'confirmed' => $confirmedCount,
            'is_full' => $confirmedCount >= $bloodRequest->required_bags
        ];

        return $this->success($data, 'Blood request details fetched successfully');
    }
}
