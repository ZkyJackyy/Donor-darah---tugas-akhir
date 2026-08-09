<?php

namespace App\Providers;

use App\Models\AdminAlert;
use App\Models\BloodRequest;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.admin', function ($view) {
            $view->with('openRequestsCount', BloodRequest::where('status', 'open')->count());
            $view->with('pendingReviewCount', BloodRequest::where('status', 'pending_review')->count());
            $view->with('unreadAlertCount', AdminAlert::unread()->count());
        });
    }
}
