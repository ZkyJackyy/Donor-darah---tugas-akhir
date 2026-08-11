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
    | Wave Delay
    |--------------------------------------------------------------------------
    |
    | Jeda waktu (menit) sebelum gelombang broadcast berikutnya berjalan,
    | berdasarkan urgency_level permintaan darah.
    |
    */

    'wave_delay_minutes' => [
        'critical' => (int) env('DONORCONNECT_WAVE_DELAY_CRITICAL', 10),
        'urgent' => (int) env('DONORCONNECT_WAVE_DELAY_URGENT', 20),
        'normal' => (int) env('DONORCONNECT_WAVE_DELAY_NORMAL', 30),
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
    | Blood Request Double-Submit Guard
    |--------------------------------------------------------------------------
    |
    | Jendela waktu (detik) untuk menganggap dua submission form permintaan
    | darah yang identik sebagai double-submit, bukan permintaan baru.
    |
    */

    'blood_request_duplicate_window_seconds' => (int) env('DONORCONNECT_BLOOD_REQUEST_DUPLICATE_WINDOW_SECONDS', 10),

    /*
    |--------------------------------------------------------------------------
    | User Blood Request Submission Guard
    |--------------------------------------------------------------------------
    |
    | Jendela waktu (detik) untuk menganggap dua pengajuan donor pengganti
    | dari user (mobile app) yang identik sebagai retry, bukan pengajuan
    | baru. Lebih lama dari guard admin karena retry di sini dipicu oleh
    | koneksi buruk — jeda sampai user menekan ulang tombol kirim bisa lebih
    | lama daripada double-click di panel admin.
    |
    */

    'user_blood_request_duplicate_window_seconds' => (int) env('DONORCONNECT_USER_BLOOD_REQUEST_DUPLICATE_WINDOW_SECONDS', 60),

    /*
    |--------------------------------------------------------------------------
    | Confirmation Expiry
    |--------------------------------------------------------------------------
    |
    | Berapa lama (menit) tiket konfirmasi donor berlaku sebelum kadaluarsa.
    |
    */

    'confirmation_expiry_minutes' => (int) env('DONORCONNECT_CONFIRMATION_EXPIRY_MINUTES', 120),

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
