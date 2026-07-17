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

    'max_distance_km' => (int) env('DONORCONNECT_MAX_DISTANCE_KM', 25),

    'wave_distance_km' => [
        0,
        (int) env('DONORCONNECT_WAVE_1_KM', 5),
        (int) env('DONORCONNECT_WAVE_2_KM', 10),
        (int) env('DONORCONNECT_WAVE_3_KM', 20),
    ],

    /*
    |--------------------------------------------------------------------------
    | Donation Cooldown
    |--------------------------------------------------------------------------
    |
    | Jarak waktu minimal antar donor (hari).
    |
    */

    'donation_cooldown_days' => (int) env('DONORCONNECT_DONATION_COOLDOWN_DAYS', 56),

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
        'expiry_minutes' => (int) env('DONORCONNECT_QR_EXPIRY_MINUTES', 120),
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

    /*
    |--------------------------------------------------------------------------
    | Default Hospital Location
    |--------------------------------------------------------------------------
    |
    | Lokasi default (UDD PMI Kota Padang) yang dipakai saat admin membuat
    | permintaan darah tanpa mengisi lokasi rumah sakit, serta sebagai pusat
    | radius pada halaman Peta Donor.
    |
    */

    'default_hospital_name' => env('DONORCONNECT_DEFAULT_HOSPITAL_NAME', 'UDD PMI Kota Padang'),
    'default_hospital_address' => env('DONORCONNECT_DEFAULT_HOSPITAL_ADDRESS', 'Jl. Sawahan Dalam II No.12, Sawahan Tim., Kec. Padang Tim., Kota Padang, Sumatera Barat 25121'),
    'default_lat' => env('DONORCONNECT_DEFAULT_LAT', -0.944554954176654),
    'default_lng' => env('DONORCONNECT_DEFAULT_LNG', 100.3679109288369),

];
