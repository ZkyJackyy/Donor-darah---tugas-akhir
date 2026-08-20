<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DonorFilterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AdminSettingsController extends Controller
{
    public function index()
    {
        return view('admin.settings.index', [
            'fonnteConfigured' => config('services.fonnte.token') && config('services.fonnte.token') !== 'mock-token',
            'waveRanges' => DonorFilterService::waveRanges(),
            'defaultHospital' => [
                'name' => config('donorconnect.default_hospital_name'),
                'address' => config('donorconnect.default_hospital_address'),
                'lat' => config('donorconnect.default_lat'),
                'lng' => config('donorconnect.default_lng'),
            ],
            'cooldownDays' => config('donorconnect.donation_cooldown_days', 60),
        ]);
    }

    public function testFonnte()
    {
        $token = config('services.fonnte.token');

        if (!$token || $token === 'mock-token') {
            return response()->json([
                'success' => false,
                'message' => 'Fonnte API Key belum dikonfigurasi di .env (FONNTE_API_KEY)',
            ]);
        }

        try {
            // GET /device only reports the connected device's status, it doesn't send messages
            $response = Http::withHeaders([
                'Authorization' => $token,
            ])->get('https://api.fonnte.com/device');

            $data = $response->json();

            if ($response->successful() && isset($data['status']) && $data['status'] === true) {
                $deviceStatus = $data['device_status'] ?? null;
                $message = $deviceStatus
                    ? "Koneksi Fonnte API berhasil. Token valid, status perangkat: {$deviceStatus}."
                    : 'Koneksi Fonnte API berhasil. Token valid.';

                return response()->json([
                    'success' => true,
                    'message' => $message,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Token tidak valid atau respons tidak terduga: ' . $response->body(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal koneksi: ' . $e->getMessage(),
            ]);
        }
    }
}
