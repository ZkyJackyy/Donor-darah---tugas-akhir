<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Donor Distance Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi jarak maksimum pencarian donor dan wave distance.
    |
    */

    'max_distance_km' => env('DONORCONNECT_MAX_DISTANCE_KM', 25),

    'wave_distance_km' => [
        0, 5, 10, 20,
    ],

    /*
    |--------------------------------------------------------------------------
    | Donation Cooldown
    |--------------------------------------------------------------------------
    |
    | Jarak waktu minimal antar donor (hari).
    |
    */

    'donation_cooldown_days' => env('DONORCONNECT_DONATION_COOLDOWN_DAYS', 56),

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | Pengaturan notifikasi WhatsApp.
    |
    */

    'notification' => [
        'max_recipients_per_batch' => env('DONORCONNECT_MAX_RECIPIENTS_BATCH', 50),
        'delay_between_batches_seconds' => env('DONORCONNECT_DELAY_BATCHES', 2),
        'duplicate_guard_hours' => env('DONORCONNECT_DUPLICATE_GUARD_HOURS', 24),
    ],

    /*
    |--------------------------------------------------------------------------
    | QR Code Settings
    |--------------------------------------------------------------------------
    |
    | Pengaturan QR Code untuk verifikasi donor.
    |
    */

    'qr' => [
        'expiry_minutes' => env('DONORCONNECT_QR_EXPIRY_MINUTES', 60),
        'size' => 300,
    ],

    /*
    |--------------------------------------------------------------------------
    | Blood Types
    |--------------------------------------------------------------------------
    |
    | Tipe darah yang didukung.
    |
    */

    'blood_types' => ['A', 'B', 'AB', 'O'],
    'rhesus_types' => ['+', '-'],

];
