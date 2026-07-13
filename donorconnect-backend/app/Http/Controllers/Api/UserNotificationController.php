<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WaLog;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class UserNotificationController extends Controller
{
    use ApiResponse;

    /**
     * Daftar notifikasi WA untuk user yang login.
     * Mengambil dari wa_logs berdasarkan phone number user.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $phone = $user->phone;

        if (!$phone) {
            return $this->success([], 'Tidak ada nomor telepon terdaftar');
        }

        $notifications = WaLog::where('phone', $phone)
            ->orderByDesc('created_at')
            ->paginate(20);

        return $this->success($notifications, 'Notifikasi berhasil diambil');
    }

    /**
     * Jumlah notifikasi belum dibaca (status pending atau success).
     */
    public function unreadCount(Request $request)
    {
        $user = $request->user();
        $phone = $user->phone;

        if (!$phone) {
            return $this->success(['count' => 0], 'Tidak ada nomor telepon terdaftar');
        }

        $count = WaLog::where('phone', $phone)
            ->where('status', '!=', 'failed')
            ->count();

        return $this->success(['count' => $count], 'Jumlah notifikasi berhasil diambil');
    }
}
