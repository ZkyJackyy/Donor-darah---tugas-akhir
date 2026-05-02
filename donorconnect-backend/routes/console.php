<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\BloodRequest;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    BloodRequest::where('status', 'open')
        ->where('created_at', '<=', now()->subHours(48))
        ->update(['status' => 'cancelled']);
})->hourly();
