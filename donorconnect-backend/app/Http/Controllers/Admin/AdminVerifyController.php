<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DonorCandidate;
use App\Models\DonorHistory;
use Illuminate\Http\Request;

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

        $candidate = DonorCandidate::with('user', 'bloodRequest')
            ->where('kode_verifikasi', strtoupper($request->kode_verifikasi))
            ->first();

        if (!$candidate) {
            return back()->with('error', $invalidMessage);
        }

        if ($candidate->status === 'verified') {
            return back()->with('error', 'Kandidat sudah terverifikasi sebelumnya.');
        }

        if ($candidate->status !== 'confirmed') {
            return back()->with('error', "Status kandidat '{$candidate->status}' — belum bisa diverifikasi. Kandidat harus mengkonfirmasi kehadiran terlebih dahulu.");
        }

        if ($candidate->bloodRequest->status !== 'open') {
            return back()->with('error', "Permintaan ini berstatus '{$candidate->bloodRequest->status}' — kandidat tidak bisa diverifikasi lagi.");
        }

        $expiryMinutes = config('donorconnect.confirmation_expiry_minutes', 120);
        if ($candidate->confirmed_at && $candidate->confirmed_at->copy()->addMinutes($expiryMinutes)->isPast()) {
            return back()->with('error', $invalidMessage);
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

        return back()->with('success', "Pendonor {$candidate->user->name} berhasil diverifikasi.");
    }
}
