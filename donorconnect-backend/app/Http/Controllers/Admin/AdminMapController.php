<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AdminMapController extends Controller
{
    public function index()
    {
        return view('admin.map.index');
    }

    public function donorsJson(Request $request)
    {
        $query = User::where('role', 'user')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        if ($request->filled('blood_type')) {
            $query->where('blood_type', $request->blood_type);
        }

        if ($request->filled('rhesus')) {
            $query->where('rhesus', $request->rhesus);
        }

        $donors = $query->select('id', 'name', 'phone', 'blood_type', 'rhesus', 'latitude', 'longitude', 'last_donor_date', 'is_available')
            ->get();

        return response()->json($donors);
    }
}
