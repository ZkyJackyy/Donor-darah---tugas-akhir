<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BloodRequest;
use App\Models\User;
use App\Models\DonorHistory;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    public function index()
    {
        // Compute stats for admin dash
        $totalDonors = User::where('role', 'user')->count();
        $activeRequestsCount = BloodRequest::where('status', 'open')->count();
        $donorsTodayCount = DonorHistory::whereDate('donor_date', Carbon::today())->count();

        // Data for Charts
        // 1. Monthly Trends (last 6 months)
        $trends = BloodRequest::selectRaw('created_at as raw_date, count(*) as count')
            ->where('created_at', '>=', Carbon::now()->subMonths(6))
            ->groupBy('raw_date')
            ->get()
            ->groupBy(fn ($item) => Carbon::parse($item->raw_date)->format('Y-m'))
            ->map(fn ($items, $key) => [
                'month_key' => $key,
                'month' => Carbon::parse($key . '-01')->format('M'),
                'count' => $items->sum('count'),
            ])
            ->values()
            ->sortBy('month_key')
            ->values();

        // 2. Blood Type Distribution - Ensure all types exist
        $bloodTypes = ['A', 'B', 'AB', 'O'];
        $rawDist = User::where('role', 'user')
            ->selectRaw('blood_type, count(*) as count')
            ->groupBy('blood_type')
            ->pluck('count', 'blood_type')
            ->toArray();

        $distribution = collect($bloodTypes)->map(function($type) use ($rawDist) {
            return [
                'blood_type' => $type,
                'count' => $rawDist[$type] ?? 0
            ];
        });

        // 5 most recent requests
        $recentRequests = BloodRequest::with('donorCandidates')
            ->orderBy('id', 'desc')
            ->take(5)
            ->get();

        // Additional stats for cards
        $totalDonationsCount = DonorHistory::count();
        $totalHospitals = BloodRequest::distinct('hospital_name')->count('hospital_name');

        return view('admin.dashboard', compact(
            'totalDonors',
            'activeRequestsCount',
            'donorsTodayCount',
            'recentRequests',
            'trends',
            'distribution',
            'totalDonationsCount',
            'totalHospitals'
        ));
    }
}
