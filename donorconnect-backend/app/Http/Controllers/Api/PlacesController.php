<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PlacesController extends Controller
{
    use ApiResponse;

    // Nominatim usage policy requires a descriptive User-Agent and caps
    // usage around 1 req/s — fine for this app's low volume, no API key needed.
    private const USER_AGENT = 'DonorConnect-PMI-Padang/1.0 (contact: admin@donorconnect.local)';

    // Bounding box sekitar Kota Padang (left, top, right, bottom) — dipakai
    // dengan bounded=1 supaya rekomendasi rumah sakit tidak melebar ke luar
    // kota, karena sistem ini memang khusus UDD PMI Kota Padang.
    private const PADANG_VIEWBOX = '100.20,-0.75,100.50,-1.15';

    /**
     * Proxy ke OpenStreetMap Nominatim — dipakai form pengajuan mobile untuk
     * memberi rekomendasi saat mengetik nama/alamat rumah sakit. Gratis,
     * tanpa API key. Nominatim sudah mengembalikan koordinat di hasil
     * pencarian, jadi tidak perlu request "details" terpisah lagi.
     */
    public function autocomplete(Request $request)
    {
        $validated = $request->validate([
            'input' => 'required|string|min:3|max:200',
        ]);

        $response = Http::withHeaders(['User-Agent' => self::USER_AGENT])
            ->get('https://nominatim.openstreetmap.org/search', [
                'q' => $validated['input'],
                'format' => 'json',
                'addressdetails' => 1,
                'countrycodes' => 'id',
                'limit' => 8,
                'accept-language' => 'id',
                'viewbox' => self::PADANG_VIEWBOX,
                'bounded' => 1,
            ]);

        if (!$response->successful()) {
            Log::warning('Nominatim search request failed', ['status' => $response->status()]);
            return $this->serverError('Gagal mengambil rekomendasi rumah sakit.');
        }

        $results = collect($response->json() ?? [])->map(fn ($r) => [
            'place_id' => (string) $r['place_id'],
            'description' => $r['display_name'],
            'name' => $r['name'] ?? null,
            'latitude' => isset($r['lat']) ? (float) $r['lat'] : null,
            'longitude' => isset($r['lon']) ? (float) $r['lon'] : null,
        ])->values();

        return $this->success($results, 'Rekomendasi rumah sakit berhasil diambil');
    }

    /**
     * Reverse geocode koordinat (dari tap di peta) menjadi alamat teks.
     */
    public function reverseGeocode(Request $request)
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $response = Http::withHeaders(['User-Agent' => self::USER_AGENT])
            ->get('https://nominatim.openstreetmap.org/reverse', [
                'lat' => $validated['latitude'],
                'lon' => $validated['longitude'],
                'format' => 'json',
                'accept-language' => 'id',
            ]);

        if (!$response->successful()) {
            Log::warning('Nominatim reverse geocode request failed', ['status' => $response->status()]);
            return $this->serverError('Gagal mengambil alamat lokasi.');
        }

        $body = $response->json();

        return $this->success([
            'address' => $body['display_name'] ?? null,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
        ], empty($body['display_name']) ? 'Alamat tidak ditemukan untuk lokasi ini' : 'Alamat berhasil diambil');
    }
}
