<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BloodRequest;
use App\Models\DonorHistory;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminReportController extends Controller
{
    public function index(Request $request)
    {
        $data = $this->buildReportData($request);

        return view('admin.reports.index', $data);
    }

    public function exportPdf(Request $request)
    {
        $data = $this->buildReportData($request);

        $pdf = Pdf::loadView('admin.reports.pdf', $data);

        return $pdf->download("laporan-donorconnect-{$data['month']}-{$data['year']}.pdf");
    }

    private function buildReportData(Request $request): array
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
        $completedRequests = BloodRequest::where('status', 'fulfilled')
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->count();

        // Breakdown donasi sukses per golongan darah
        $bloodTypeBreakdown = DonorHistory::join('users', 'users.id', '=', 'donor_histories.user_id')
            ->whereMonth('donor_histories.donor_date', $month)
            ->whereYear('donor_histories.donor_date', $year)
            ->selectRaw("concat(users.blood_type, users.rhesus) as blood_type, count(*) as count")
            ->groupBy('users.blood_type', 'users.rhesus')
            ->orderByDesc('count')
            ->get();

        // Breakdown permintaan per tingkat urgensi
        $urgencyBreakdown = BloodRequest::whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->selectRaw('urgency_level, count(*) as count')
            ->groupBy('urgency_level')
            ->orderByDesc('count')
            ->get();

        return compact(
            'histories',
            'totalSuccessfulDonors',
            'totalRequests',
            'totalBagsRequested',
            'completedRequests',
            'bloodTypeBreakdown',
            'urgencyBreakdown',
            'month',
            'year'
        );
    }
}
