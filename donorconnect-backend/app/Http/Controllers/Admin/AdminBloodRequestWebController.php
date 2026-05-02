<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BloodRequest;
use App\Models\DonorCandidate;
use App\Models\DonorHistory;
use App\Services\DonorFilterService;
use App\Services\WhatsAppService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class AdminBloodRequestWebController extends Controller
{
    public function index(Request $request)
    {
        $query = BloodRequest::query();

        if ($request->has('search') && $request->search != '') {
            $query->where('hospital_name', 'like', '%' . $request->search . '%')
                  ->orWhere('blood_type', 'like', '%' . $request->search . '%')
                  ->orWhere('status', 'like', '%' . $request->search . '%');
        }

        $bloodRequests = $query->orderBy('id', 'desc')->paginate(10);
        $bloodRequests->appends($request->all());

        return view('admin.blood-requests.index', compact('bloodRequests'));
    }

    public function create()
    {
        return view('admin.blood-requests.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'blood_type' => 'required|in:A,B,AB,O',
            'rhesus' => 'required|in:+,-',
            'urgency_level' => 'required|in:normal,urgent,critical',
            'hospital_name' => 'nullable|string|max:255',
            'hospital_address' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'required_bags' => 'required|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        // Default to UDD PMI Kota Padang as per AGENTS.md if no location provided
        if (empty($validated['hospital_name'])) {
            $validated['hospital_name'] = 'UDD PMI Kota Padang';
            $validated['hospital_address'] = 'Jl. Sisingamangaraja No.34, Padang';
            $validated['latitude'] = -0.9471;
            $validated['longitude'] = 100.4172;
        }

        $bloodRequest = BloodRequest::create([
            ...$validated,
            'admin_id' => auth()->id(),
            'status' => 'open'
        ]);

        // "On save: immediately run filter preview"
        return redirect()->route('admin.blood-requests.show', $bloodRequest->id)
            ->with('success', 'Blood request created successfully at UDD PMI Kota Padang!');
    }

    public function show($id)
    {
        $bloodRequest = BloodRequest::with('donorCandidates.user')->findOrFail($id);
        return view('admin.blood-requests.show', compact('bloodRequest'));
    }

    // Returning JSON specifically for Alpine/Web
    public function previewDonorsJson($id, DonorFilterService $filterService)
    {
        $bloodRequest = BloodRequest::findOrFail($id);
        $eligibleDonors = $filterService->filterEligibleDonors($bloodRequest);
        
        return response()->json($eligibleDonors);
    }

    // Refreshing active candidates table for the 30s Polling Loop
    public function pollCandidates($id)
    {
        $candidates = DonorCandidate::with('user')
            ->where('blood_request_id', $id)
            ->orderBy('id', 'desc')
            ->get();
            
        return response()->json($candidates);
    }

    public function notifyWeb($id, DonorFilterService $filterService, WhatsAppService $waService)
    {
        $bloodRequest = BloodRequest::findOrFail($id);
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

        return back()->with('success', "WhatsApp notifications queued for {$candidates->count()} eligible donors.");
    }

    public function verifyWeb($id, Request $request)
    {
        $candidate = DonorCandidate::with('user', 'bloodRequest')->findOrFail($id);
        
        if ($candidate->status === 'verified') {
            return back()->with('error', 'Candidate is already verified.');
        }

        $candidate->update([
            'status' => 'verified',
            'verified_at' => now(),
            'verification_method' => 'manual'
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

        return back()->with('success', 'Candidate manually verified successfully.');
    }

    public function exportPdf($id)
    {
        $bloodRequest = BloodRequest::with('donorCandidates.user')->findOrFail($id);
        $pdf = Pdf::loadView('admin.blood-requests.pdf', compact('bloodRequest'));
        return $pdf->download("blood-request-{$bloodRequest->id}-candidates.pdf");
    }
}
