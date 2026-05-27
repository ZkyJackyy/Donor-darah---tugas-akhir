<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BloodRequest;
use App\Models\DonorHistory;
use App\Models\User;
use Illuminate\Http\JsonResponse;

use App\Traits\ApiResponse;

class DashboardController extends Controller
{
    use ApiResponse;

    public function stats(): JsonResponse
    {
        $data = [
            'total_users' => User::where('role', 'user')->count(),
            'total_active_requests' => BloodRequest::where('status', 'open')->count(),
            'total_successful_donations' => DonorHistory::count(),
        ];
        
        return $this->success($data, 'Dashboard stats fetched successfully');
    }
}
