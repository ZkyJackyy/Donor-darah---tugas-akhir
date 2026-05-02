<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BloodRequest;
use App\Models\DonorHistory;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminReportController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);

        // Menghitung donasi yang sukses (diverifikasi)
        $histories = DonorHistory::with(['user', 'bloodRequest'])
            ->whereMonth('donor_date', $month)
            ->whereYear('donor_date', $year)
            ->orderBy('donor_date', 'desc')
            ->get();

        $totalSuccessfulDonors = $histories->count();
        
        // Total requests dibuat bulan ini
        $totalRequests = BloodRequest::whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->count();

        // Total kantong darah yang diminta bulan ini
        $totalBagsRequested = BloodRequest::whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->sum('required_bags');

        // Total completed requests
        $completedRequests = BloodRequest::where('status', 'completed')
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->count();

        return view('admin.reports.index', compact(
            'histories', 
            'totalSuccessfulDonors', 
            'totalRequests', 
            'totalBagsRequested',
            'completedRequests',
            'month', 
            'year'
        ));
    }
}
