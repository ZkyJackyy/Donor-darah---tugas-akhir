<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\CheckRequestStatusJob;
use App\Jobs\ExpireStaleConfirmationsJob;
use App\Jobs\UnlockDonorsJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Cek status request setiap 5 menit: auto-fulfill / auto-cancel
Schedule::job(new CheckRequestStatusJob)->everyFiveMinutes();

// Expire kandidat 'confirmed' yang no-show (kode kadaluarsa tanpa discan admin)
// supaya tidak mengunci kuota permintaan selamanya
Schedule::job(new ExpireStaleConfirmationsJob)->everyFiveMinutes();

// Unlock donor setiap hari (60 hari cooldown)
Schedule::job(new UnlockDonorsJob)->daily();
