<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AdminDonorController extends Controller
{
    use \App\Traits\ApiResponse;

    // Polled by the donor directory page to detect availability/roster changes
    // (e.g. UnlockDonorsJob flipping is_available, a new donor registering).
    public function pollStats()
    {
        return $this->success([
            'total' => User::where('role', 'user')->count(),
            'available' => User::where('role', 'user')->where('is_available', true)->count(),
        ], 'Donor stats retrieved successfully');
    }

    public function index(Request $request)
    {
        $query = User::where('role', 'user')->withCount('donorHistories');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('blood_type', 'like', "%{$search}%");
            });
        }

        if ($request->filled('blood_type')) {
            $query->where('blood_type', $request->blood_type);
        }

        $donors = $query->paginate(20);
        $donors->appends($request->all());

        $initialAvailableCount = User::where('role', 'user')->where('is_available', true)->count();

        return view('admin.donors.index', compact('donors', 'initialAvailableCount'));
    }
}
