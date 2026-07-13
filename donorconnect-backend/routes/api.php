<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserProfileController;
use App\Http\Controllers\Api\DonorActionController;
use App\Http\Controllers\Api\AdminBloodRequestController;
use App\Http\Controllers\Api\UserBloodRequestController;
use App\Http\Controllers\Api\UserNotificationController;
use App\Http\Controllers\Api\DashboardController;

// Auth Routes
Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:60,1');
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:60,1');
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:10,1');
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->group(function () {
    // Authenticated Auth Routes
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);

    // User Routes
    Route::get('/profile', [UserProfileController::class, 'show']);
    Route::put('/profile/update', [UserProfileController::class, 'update']);
    Route::put('/location/update', [UserProfileController::class, 'updateLocation']);

    Route::post('/donor/screening', [DonorActionController::class, 'screening']);
    Route::post('/donor/confirm', [DonorActionController::class, 'confirm']);
    Route::get('/donor/history', [DonorActionController::class, 'history']);
    Route::get('/donor-candidates/{candidate}/qr-code', [DonorActionController::class, 'qrCode']);

    // User Blood Request Routes (Mobile App)
    Route::get('/user/blood-requests', [UserBloodRequestController::class, 'index']);
    Route::get('/user/blood-requests/history', [UserBloodRequestController::class, 'history']);
    Route::get('/user/blood-requests/{id}', [UserBloodRequestController::class, 'show']);

    // User Notifications (Mobile App)
    Route::get('/user/notifications', [UserNotificationController::class, 'index']);
    Route::get('/user/notifications/unread-count', [UserNotificationController::class, 'unreadCount']);

    // Admin Routes
    Route::middleware('admin')->group(function () {
        Route::post('/verify/qr', [AdminBloodRequestController::class, 'verifyQr']);
        Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

        Route::get('/blood-requests', [AdminBloodRequestController::class, 'index']);
        Route::post('/blood-requests', [AdminBloodRequestController::class, 'store']);
        Route::get('/blood-requests/{bloodRequest}', [AdminBloodRequestController::class, 'show']);

        Route::get('/blood-requests/{bloodRequest}/preview-donors', [AdminBloodRequestController::class, 'previewDonors']);
        Route::post('/blood-requests/{bloodRequest}/notify', [AdminBloodRequestController::class, 'notify']);

        Route::post('/donor-candidates/{candidate}/verify', [AdminBloodRequestController::class, 'verify']);
        Route::post('/verify/code', [AdminBloodRequestController::class, 'verifyByCode']);
    });
});
