<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BloodRequest;
use App\Models\DonorCandidate;
use App\Models\DonorHistory;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminVerifyController extends Controller
{
    public function index()
    {
        return view('admin.verify.index');
    }

    public function submit(Request $request)
    {
        $request->validate([
            'kode_verifikasi' => 'required|string',
        ]);

        $invalidMessage = 'Kode verifikasi tidak valid atau sudah kadaluarsa.';

        // Check-then-write must happen inside the same row lock — two
        // near-simultaneous verify attempts on the same candidate (e.g. this
        // page and the "Verifikasi" button on the request detail page) could
        // otherwise both pass the status check before either wrote,
        // producing two DonorHistory rows for one donation.
        $result = DB::transaction(function () use ($request, $invalidMessage) {
            $candidate = DonorCandidate::with('user', 'bloodRequest')
                ->where('kode_verifikasi', strtoupper($request->kode_verifikasi))
                ->lockForUpdate()
                ->first();

            if (!$candidate) {
                return ['error' => $invalidMessage];
            }

            if ($candidate->status === 'verified') {
                return ['error' => 'Kandidat sudah terverifikasi sebelumnya.'];
            }

            if ($candidate->status !== 'confirmed') {
                return ['error' => "Status kandidat '{$candidate->status}' — belum bisa diverifikasi. Kandidat harus mengkonfirmasi kehadiran terlebih dahulu."];
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
                'verification_method' => 'code',
            ]);

            DonorHistory::create([
                'user_id' => $candidate->user_id,
                'blood_request_id' => $candidate->blood_request_id,
                'donor_date' => now()->toDateString(),
                'location_name' => $candidate->bloodRequest->hospital_name,
                'verified_by' => auth()->id(),
            ]);

            $candidate->user->update([
                'last_donor_date' => now()->toDateString(),
                'is_available' => false,
            ]);

            $candidate->bloodRequest->checkAndAutoFulfill();

            return [
                'success' => "Pendonor {$candidate->user->name} berhasil diverifikasi.",
                'blood_request_id' => $candidate->blood_request_id,
            ];
        });

        if (isset($result['error'])) {
            return back()->with('error', $result['error']);
        }

        // Notify family requester via WA if this verification triggered auto-fulfillment
        $bloodRequest = BloodRequest::find($result['blood_request_id'] ?? null);
        if ($bloodRequest && $bloodRequest->status === 'fulfilled') {
            app(WhatsAppService::class)->notifyRequesterFulfilled($bloodRequest);
        }

        return back()->with('success', $result['success']);
    }
}
