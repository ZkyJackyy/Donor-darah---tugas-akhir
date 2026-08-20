<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAlert;

class AdminAlertController extends Controller
{
    use \App\Traits\ApiResponse;

    // Polled by the alerts page to detect newly created alerts worth a refresh.
    public function pollStats()
    {
        return $this->success([
            'latest_id' => AdminAlert::max('id'),
            'unread_count' => AdminAlert::unread()->count(),
        ], 'Alert stats retrieved successfully');
    }

    public function index()
    {
        $alerts = AdminAlert::with('bloodRequest')
            ->orderByDesc('created_at')
            ->paginate(15);

        $latestAlertId = AdminAlert::max('id');

        return view('admin.alerts.index', compact('alerts', 'latestAlertId'));
    }

    public function markRead(AdminAlert $alert)
    {
        if (!$alert->read_at) {
            $alert->update(['read_at' => now()]);
        }

        return back()->with('success', 'Peringatan ditandai sudah dibaca.');
    }

    public function markAllRead()
    {
        AdminAlert::unread()->update(['read_at' => now()]);

        return back()->with('success', 'Semua peringatan ditandai sudah dibaca.');
    }
}
