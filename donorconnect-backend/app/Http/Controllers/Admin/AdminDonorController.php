<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AdminDonorController extends Controller
{
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
        return view('admin.donors.index', compact('donors'));
    }
}
