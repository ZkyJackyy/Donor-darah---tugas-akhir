<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminDonorController;
use App\Http\Controllers\Admin\AdminBloodRequestWebController;
use App\Http\Controllers\Admin\AdminReportController;

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
    
    Route::post('/blood-requests/{id}/notify', [AdminBloodRequestWebController::class, 'notifyWeb'])->name('blood-requests.notify');
    Route::post('/blood-requests/verify/{id}', [AdminBloodRequestWebController::class, 'verifyWeb'])->name('blood-requests.verify');
    Route::get('/blood-requests/{id}/pdf', [AdminBloodRequestWebController::class, 'exportPdf'])->name('blood-requests.pdf');

    // Reports
    Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
});

// Admin AJAX Polling Routes (using Web middleware for seamless session auth bypassing Sanctum headers)
Route::middleware(['auth', 'admin'])->prefix('api/admin-poll/blood-requests')->group(function () {
    Route::get('/{id}/preview', [AdminBloodRequestWebController::class, 'previewDonorsJson']);
    Route::get('/{id}/candidates', [AdminBloodRequestWebController::class, 'pollCandidates']);
});
