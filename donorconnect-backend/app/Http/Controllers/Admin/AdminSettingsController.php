<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AdminSettingsController extends Controller
{
    public function index()
    {
        return view('admin.settings.index', [
            'fonnteConfigured' => config('services.fonnte.token') && config('services.fonnte.token') !== 'mock-token',
            'waveRanges' => [
                1 => ['min' => 0, 'max' => config('donorconnect.wave_1_km', 5)],
                2 => ['min' => config('donorconnect.wave_1_km', 5), 'max' => config('donorconnect.wave_2_km', 10)],
                3 => ['min' => config('donorconnect.wave_2_km', 10), 'max' => config('donorconnect.wave_3_km', 20)],
            ],
            'defaultHospital' => [
                'name' => config('donorconnect.default_hospital_name', 'UDD PMI Kota Padang'),
                'address' => config('donorconnect.default_hospital_address', 'Jl. Sisingamangarja No.34, Padang'),
                'lat' => config('donorconnect.default_lat', -0.9471),
                'lng' => config('donorconnect.default_lng', 100.4172),
            ],
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
            $response = Http::withHeaders([
                'Authorization' => $token,
            ])->post('https://api.fonnte.com/send', [
                'target' => '081234567890',
                'message' => 'Test koneksi Fonnte API dari DonorConnect. Pesan ini tidak akan terkirim.',
                'countryCode' => '62',
            ]);

            $data = $response->json();

            // Fonnte returns status:true even for invalid numbers if token is valid
            if (isset($data['status'])) {
                return response()->json([
                    'success' => true,
                    'message' => 'Koneksi Fonnte API berhasil. Token valid.',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Respons tidak terduga: ' . $response->body(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal koneksi: ' . $e->getMessage(),
            ]);
        }
    }
}
