<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAlert;

class AdminAlertController extends Controller
{
    public function index()
    {
        $alerts = AdminAlert::with('bloodRequest')
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('admin.alerts.index', compact('alerts'));
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
