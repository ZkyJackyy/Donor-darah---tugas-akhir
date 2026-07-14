<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminDonorController;
use App\Http\Controllers\Admin\AdminBloodRequestWebController;
use App\Http\Controllers\Admin\AdminReportController;
use App\Http\Controllers\Admin\AdminMapController;
use App\Http\Controllers\Admin\AdminSettingsController;

use App\Http\Controllers\Admin\AdminBroadcastController;

Route::get('/', function () {
    return redirect()->route('admin.login');
});

// Admin Web Auth Routes
Route::get('/admin/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'login'])->name('admin.login.attempt');
Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

// Web Admin Pages
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/donors', [AdminDonorController::class, 'index'])->name('donors');
    
    // Blood Requests
    Route::get('/blood-requests', [AdminBloodRequestWebController::class, 'index'])->name('blood-requests.index');
    Route::get('/blood-requests/create', [AdminBloodRequestWebController::class, 'create'])->name('blood-requests.create');
    Route::post('/blood-requests', [AdminBloodRequestWebController::class, 'store'])->name('blood-requests.store');
    Route::get('/blood-requests/{id}', [AdminBloodRequestWebController::class, 'show'])->name('blood-requests.show');
    
    Route::post('/blood-requests/{id}/notify', [AdminBloodRequestWebController::class, 'notifyWeb'])->middleware('throttle:5,1')->name('blood-requests.notify');
    Route::post('/blood-requests/verify/{id}', [AdminBloodRequestWebController::class, 'verifyWeb'])->name('blood-requests.verify');
    Route::post('/blood-requests/verify-qr', [AdminBloodRequestWebController::class, 'verifyQrWeb'])->name('blood-requests.verify-qr');
    Route::patch('/blood-requests/{id}/status', [AdminBloodRequestWebController::class, 'updateStatus'])->name('blood-requests.update-status');
    Route::get('/blood-requests/{id}/pdf', [AdminBloodRequestWebController::class, 'exportPdf'])->name('blood-requests.pdf');

    // Reports
    Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');

    // Map
    Route::get('/map', [AdminMapController::class, 'index'])->name('map.index');

    // Settings
    Route::get('/settings', [AdminSettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings/test-fonnte', [AdminSettingsController::class, 'testFonnte'])->name('settings.test-fonnte');

    // Broadcast
    Route::get('/broadcast', [AdminBroadcastController::class, 'index'])->name('broadcast.index');
});

// Admin AJAX Polling Routes (using Web middleware for seamless session auth bypassing Sanctum headers)
Route::middleware(['auth', 'admin'])->prefix('api/admin-poll/blood-requests')->group(function () {
    Route::get('/{id}/preview', [AdminBloodRequestWebController::class, 'previewDonorsJson']);
    Route::get('/{id}/candidates', [AdminBloodRequestWebController::class, 'pollCandidates']);
    Route::get('/{id}/status', [AdminBloodRequestWebController::class, 'pollStatus']);
    Route::get('/statuses', [AdminBloodRequestWebController::class, 'pollStatuses']);
});

// Admin Map AJAX (donors JSON)
Route::middleware(['auth', 'admin'])->prefix('admin/map')->name('admin.map.')->group(function () {
    Route::get('/donors-json', [AdminMapController::class, 'donorsJson'])->name('donors-json');
});
