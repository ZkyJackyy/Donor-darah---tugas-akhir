<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WaLog;
use Illuminate\Http\Request;

class AdminBroadcastController extends Controller
{
    use \App\Traits\ApiResponse;

    // Polled by the broadcast history page to detect newly logged WA sends.
    public function pollStats()
    {
        return $this->success([
            'latest_id' => WaLog::max('id'),
        ], 'Broadcast stats retrieved successfully');
    }

    public function index()
    {
        $broadcasts = WaLog::query()
            ->selectRaw('DATE(created_at) as broadcast_date, phone, message, status, count(*) as recipient_count')
            ->groupBy('broadcast_date', 'phone', 'message', 'status')
            ->orderByDesc('broadcast_date')
            ->paginate(15);

        $totalSent = WaLog::where('status', 'success')->count();
        $totalFailed = WaLog::where('status', 'failed')->count();
        $totalPending = WaLog::where('status', 'pending')->count();
        $latestLogId = WaLog::max('id');

        return view('admin.broadcast.index', compact('broadcasts', 'totalSent', 'totalFailed', 'totalPending', 'latestLogId'));
    }
}
