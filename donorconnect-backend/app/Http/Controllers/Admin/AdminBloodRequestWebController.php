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
        } else {
            // Pengajuan yang belum divalidasi admin punya halaman sendiri
            // (Pengajuan Keluarga), jadi tidak nyampur di listing utama.
            $query->whereNotIn('status', ['pending_review', 'rejected']);
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

    public function pendingIndex()
    {
        $bloodRequests = BloodRequest::where('status', 'pending_review')
            ->with('requestedBy')
            ->orderBy('id', 'desc')
            ->paginate(10);

        return view('admin.blood-requests.pending', compact('bloodRequests'));
    }

    public function approve($id)
    {
        $bloodRequest = BloodRequest::findOrFail($id);

        if (!$bloodRequest->isPendingReview()) {
            return back()->with('error', "Pengajuan ini sudah berstatus '{$bloodRequest->status}' — tidak bisa disetujui lagi.");
        }

        $bloodRequest->update([
            'status' => 'open',
            'admin_id' => auth()->id(),
        ]);

        return redirect()->route('admin.blood-requests.show', $bloodRequest->id)
            ->with('success', 'Pengajuan disetujui dan masuk alur pencarian pendonor.');
    }

    public function reject($id, Request $request)
    {
        $bloodRequest = BloodRequest::findOrFail($id);

        if (!$bloodRequest->isPendingReview()) {
            return back()->with('error', "Pengajuan ini sudah berstatus '{$bloodRequest->status}' — tidak bisa ditolak lagi.");
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $bloodRequest->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        return back()->with('success', 'Pengajuan ditolak.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:emergency,event',
            'blood_type' => 'required_if:type,emergency|nullable|in:A,B,AB,O',
            'rhesus' => 'required_if:type,emergency|nullable|in:+,-',
            'urgency_level' => 'required|in:normal,urgent,critical',
            'hospital_name' => 'nullable|string|max:255',
            'hospital_address' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'required_bags' => 'required_if:type,emergency|nullable|integer|min:1',
            'event_starts_at' => 'required_if:type,event|nullable|date|after:now|before:deadline',
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
        $bloodRequest = BloodRequest::with('donorCandidates.user', 'admin', 'requestedBy')->findOrFail($id);
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

    // Total count for the current index filters — used to detect newly
    // created requests that wouldn't show up in a per-id status poll.
    public function pollIndexCount(Request $request)
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
        } else {
            $query->whereNotIn('status', ['pending_review', 'rejected']);
        }

        return response()->json(['count' => $query->count()]);
    }

    // Count of pending_review submissions — polled by the "Pengajuan Keluarga" page.
    public function pollPendingCount()
    {
        return response()->json(['count' => BloodRequest::where('status', 'pending_review')->count()]);
    }

    public function notifyWeb($id, DonorFilterService $filterService, WhatsAppService $waService)
    {
        $bloodRequest = BloodRequest::findOrFail($id);

        if ($bloodRequest->status !== 'open') {
            return back()->with('error', "Permintaan ini berstatus '{$bloodRequest->status}' — tidak bisa mengirim broadcast WA lagi.");
        }

        if ($bloodRequest->isEvent()) {
            $donors = \App\Models\User::where('is_available', true)->where('role', 'user')->get();
            $waService->announceEvent($donors, $bloodRequest);

            return back()->with('success', "Pengumuman event berhasil dikirim ke {$donors->count()} pendonor.");
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
        // beberapa menit kemudian (durasi tergantung urgency_level permintaan)
        $confirmedCount = DonorCandidate::where('blood_request_id', $bloodRequest->id)
            ->whereIn('status', ['confirmed', 'verified'])
            ->count();

        $delayMinutes = config('donorconnect.wave_delay_minutes')[$bloodRequest->urgency_level]
            ?? config('donorconnect.wave_delay_minutes.normal');

        if ($confirmedCount < $bloodRequest->required_bags) {
            WaveChainJob::dispatch($bloodRequest->id, 2)
                ->delay(now()->addMinutes($delayMinutes));
        }

        return back()->with('success', "WhatsApp notifications queued for {$candidates->count()} eligible donors. Wave 2 akan otomatis berjalan dalam {$delayMinutes} menit jika kuota belum terpenuhi.");
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

        if ($candidate->status !== 'confirmed') {
            $message = "Status kandidat '{$candidate->status}' — belum bisa diverifikasi. Kandidat harus mengkonfirmasi kehadiran terlebih dahulu.";
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['message' => $message], 400);
            }
            return back()->with('error', $message);
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
}
