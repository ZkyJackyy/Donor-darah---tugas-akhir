<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAlert;
use App\Models\BloodRequest;
use App\Models\User;
use App\Models\DonorHistory;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    use \App\Traits\ApiResponse;

    public function index()
    {
        // Compute stats for admin dash
        $totalDonors = User::where('role', 'user')->count();
        $activeRequestsCount = BloodRequest::whereIn('status', ['approved', 'open'])->count();
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
        $totalHospitals = $this->countDistinctHospitals();

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

    // Lightweight snapshot polled by the dashboard to detect changes worth a refresh.
    public function pollStats()
    {
        return $this->success([
            'active_requests' => BloodRequest::whereIn('status', ['approved', 'open'])->count(),
            'total_donors' => User::where('role', 'user')->count(),
            'total_donations' => DonorHistory::count(),
            'total_hospitals' => $this->countDistinctHospitals(),
            'latest_request_id' => BloodRequest::max('id'),
        ], 'Stats retrieved successfully');
    }

    // Sidebar badge counts, polled on every admin page via layouts.admin.
    public function pollSidebarCounts()
    {
        return $this->success([
            'open_requests' => BloodRequest::where('status', 'open')->count(),
            'pending_review' => BloodRequest::where('status', 'pending_review')->count(),
            'unread_alerts' => AdminAlert::unread()->count(),
        ], 'Sidebar counts retrieved successfully');
    }

    // hospital_name is free text typed by admins per request, so the same
    // hospital ends up spelled slightly differently across requests (case,
    // punctuation, extra spaces). Counting raw DISTINCT values overstates
    // how many partner hospitals actually exist; normalizing first collapses
    // those cosmetic variants together before counting.
    private function countDistinctHospitals(): int
    {
        return BloodRequest::whereNotNull('hospital_name')
            ->where('hospital_name', '!=', '')
            ->pluck('hospital_name')
            ->map(function (string $name) {
                $normalized = preg_replace('/[^\p{L}\p{N}\s]/u', '', $name);
                $normalized = preg_replace('/\s+/', ' ', $normalized ?? '');
                return trim(mb_strtolower($normalized ?? ''));
            })
            ->unique()
            ->filter()
            ->count();
    }
}
