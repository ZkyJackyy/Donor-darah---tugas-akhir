<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\CheckRequestStatusJob;
use App\Jobs\UnlockDonorsJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Cek status request setiap 5 menit: auto-fulfill / auto-cancel
Schedule::job(new CheckRequestStatusJob)->everyFiveMinutes();

// Unlock donor setiap hari (56 hari cooldown)
Schedule::job(new UnlockDonorsJob)->daily();
