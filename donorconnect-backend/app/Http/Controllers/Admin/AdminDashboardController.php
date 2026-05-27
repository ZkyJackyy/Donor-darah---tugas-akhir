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
        // 1. Monthly Trends (last 6 months) - Fixed sorting
        $trends = BloodRequest::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month_key, DATE_FORMAT(created_at, "%b") as month, count(*) as count')
            ->where('created_at', '>=', Carbon::now()->subMonths(6))
            ->groupBy('month_key', 'month')
            ->orderBy('month_key', 'asc')
            ->get();

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
        $totalCompletedRequests = BloodRequest::where('status', 'fulfilled')->count();

        return view('admin.dashboard', compact(
            'totalDonors', 
            'activeRequestsCount', 
            'donorsTodayCount', 
            'recentRequests',
            'trends',
            'distribution',
            'totalDonationsCount',
            'totalCompletedRequests'
        ));
    }
}
