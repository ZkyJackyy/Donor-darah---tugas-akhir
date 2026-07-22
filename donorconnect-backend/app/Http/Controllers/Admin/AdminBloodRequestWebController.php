<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BloodRequest;
use App\Models\DonorCandidate;
use App\Models\DonorHistory;
use App\Jobs\WaveChainJob;
use App\Services\DonorFilterService;
use App\Services\WhatsAppService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class AdminBloodRequestWebController extends Controller
{
    public function index(Request $request)
    {
        $query = BloodRequest::query();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('hospital_name', 'like', '%' . $request->search . '%')
                  ->orWhere('blood_type', 'like', '%' . $request->search . '%')
                  ->orWhere('status', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $bloodRequests = $query->withCount(['donorCandidates as verified_candidates_count' => function ($q) {
            $q->where('status', 'verified');
        }])->orderBy('id', 'desc')->paginate(10);
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
            'deadline' => 'required|date|after:now',
            'notes' => 'nullable|string',
        ]);

        // Default to UDD PMI Kota Padang as per AGENTS.md if no location provided
        if (empty($validated['hospital_name'])) {
            $validated['hospital_name'] = config('donorconnect.default_hospital_name');
            $validated['hospital_address'] = config('donorconnect.default_hospital_address');
            $validated['latitude'] = config('donorconnect.default_lat');
            $validated['longitude'] = config('donorconnect.default_lng');
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
        $bloodRequest = BloodRequest::with('donorCandidates.user', 'admin')->findOrFail($id);
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
        $candidates = DonorCandidate::with('user', 'screening')
            ->where('blood_request_id', $id)
            ->orderBy('id', 'desc')
            ->get();
            
        return response()->json($candidates);
    }

    // Polling status of a single request for the 30s Polling Loop
    public function pollStatus($id)
    {
        return response()->json(['status' => BloodRequest::findOrFail($id)->status]);
    }

    // Polling statuses of the requests currently listed on the index page
    public function pollStatuses(Request $request)
    {
        $ids = array_filter(explode(',', $request->query('ids', '')));

        return response()->json(BloodRequest::whereIn('id', $ids)->pluck('status', 'id'));
    }

    public function notifyWeb($id, DonorFilterService $filterService, WhatsAppService $waService)
    {
        $bloodRequest = BloodRequest::findOrFail($id);

        if ($bloodRequest->status !== 'open') {
            return back()->with('error', "Permintaan ini berstatus '{$bloodRequest->status}' — tidak bisa mengirim broadcast WA lagi.");
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

        // Chain wave 2/3 otomatis: jika kuota belum terpenuhi, wave berikutnya jalan 30 menit kemudian
        $confirmedCount = DonorCandidate::where('blood_request_id', $bloodRequest->id)
            ->where('status', 'confirmed')
            ->count();

        if ($confirmedCount < $bloodRequest->required_bags) {
            WaveChainJob::dispatch($bloodRequest->id, 2)
                ->delay(now()->addMinutes(30));
        }

        return back()->with('success', "WhatsApp notifications queued for {$candidates->count()} eligible donors. Wave 2 akan otomatis berjalan dalam 30 menit jika kuota belum terpenuhi.");
    }

    public function verifyWeb($id, Request $request)
    {
        $candidate = DonorCandidate::with('user', 'bloodRequest')->findOrFail($id);

        if ($candidate->status === 'verified') {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['message' => 'Candidate is already verified.'], 400);
            }
            return back()->with('error', 'Candidate is already verified.');
        }

        if ($candidate->bloodRequest->status !== 'open') {
            $message = "Permintaan ini berstatus '{$candidate->bloodRequest->status}' — kandidat tidak bisa diverifikasi lagi.";
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['message' => $message], 400);
            }
            return back()->with('error', $message);
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

        // Auto-transition to fulfilled if quota of verified candidates met
        $candidate->bloodRequest->checkAndAutoFulfill();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['message' => 'Candidate manually verified successfully.', 'status' => 'verified']);
        }

        return back()->with('success', 'Candidate manually verified successfully.');
    }

    public function updateStatus($id, Request $request)
    {
        $bloodRequest = BloodRequest::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:fulfilled,cancelled',
        ]);

        $oldStatus = $bloodRequest->status;
        $bloodRequest->update(['status' => $validated['status']]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'message' => "Status berhasil diubah dari '{$oldStatus}' menjadi '{$validated['status']}'",
                'status' => $validated['status'],
            ]);
        }

        return back()->with('success', "Status berhasil diubah dari '{$oldStatus}' menjadi '{$validated['status']}'");
    }

    public function exportPdf($id)
    {
        $bloodRequest = BloodRequest::with('donorCandidates.user', 'donorCandidates.screening')->findOrFail($id);
        $pdf = Pdf::loadView('admin.blood-requests.pdf', compact('bloodRequest'));
        return $pdf->download("blood-request-{$bloodRequest->id}-candidates.pdf");
    }

    public function verifyQrWeb(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string',
        ]);

        $token = $validated['token'];
        $parts = explode('|', $token);
        if (count($parts) !== 2) {
            return response()->json(['success' => false, 'message' => 'Format QR token tidak valid.'], 400);
        }

        [$signature, $base64Payload] = $parts;

        $expectedSignature = hash_hmac('sha256', base64_decode($base64Payload), config('app.key'));
        if (!hash_equals($expectedSignature, $signature)) {
            return response()->json(['success' => false, 'message' => 'QR token tidak valid atau sudah dirusak.'], 400);
        }

        $payload = json_decode(base64_decode($base64Payload), true);
        if (!$payload || !isset($payload['candidate_id'], $payload['expires_at'])) {
            return response()->json(['success' => false, 'message' => 'Payload QR tidak valid.'], 400);
        }

        if (now()->timestamp > $payload['expires_at']) {
            return response()->json(['success' => false, 'message' => 'QR token sudah kadaluarsa. Pendonor harus scan ulang.'], 400);
        }

        $candidate = DonorCandidate::with('user', 'bloodRequest')->find($payload['candidate_id']);
        if (!$candidate) {
            return response()->json(['success' => false, 'message' => 'Kandidat tidak ditemukan.'], 404);
        }

        if ($candidate->status === 'verified') {
            return response()->json(['success' => false, 'message' => "Pendonor {$candidate->user->name} sudah terverifikasi sebelumnya."], 400);
        }

        if ($candidate->status !== 'confirmed') {
            return response()->json(['success' => false, 'message' => "Status kandidat '{$candidate->status}' — belum bisa diverifikasi."], 400);
        }

        if ($candidate->bloodRequest->status !== 'open') {
            return response()->json(['success' => false, 'message' => "Permintaan ini berstatus '{$candidate->bloodRequest->status}' — kandidat tidak bisa diverifikasi lagi."], 400);
        }

        $candidate->update([
            'status' => 'verified',
            'verified_at' => now(),
            'verification_method' => 'qr'
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

        // Auto-transition to fulfilled if quota of verified candidates met
        $candidate->bloodRequest->checkAndAutoFulfill();

        return response()->json([
            'success' => true,
            'message' => "Pendonor {$candidate->user->name} berhasil diverifikasi via QR.",
            'candidate' => [
                'id' => $candidate->id,
                'name' => $candidate->user->name,
                'blood_type' => $candidate->user->blood_type,
            ]
        ]);
    }
}
