<?php

namespace App\Services;

use App\Models\BloodRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DonorFilterService
{
    /**
     * Wave definitions for gradual broadcast, derived from config('donorconnect.wave_distance_km')
     * (env DONORCONNECT_WAVE_1_KM/WAVE_2_KM/WAVE_3_KM):
     * Wave 1: 0-5 km (immediate)
     * Wave 2: 5-10 km (if quota not met)
     * Wave 3: 10-20 km (if quota still not met)
     */
    public static function waveRanges(): array
    {
        [$w0, $w1, $w2, $w3] = config('donorconnect.wave_distance_km', [0, 5, 10, 20]);

        return [
            1 => ['min' => $w0, 'max' => $w1],
            2 => ['min' => $w1, 'max' => $w2],
            3 => ['min' => $w2, 'max' => $w3],
        ];
    }

    /**
     * Filter eligible donors based on medical requirements, blood type, and geolocation.
     *
     * @param BloodRequest $request
     * @param int $wave Wave number (1, 2, or 3). If null, returns all waves.
     * @return Collection
     */
    public function filterEligibleDonors(BloodRequest $request, ?int $wave = 1): Collection
    {
        $lat = $request->latitude;
        $lon = $request->longitude;
        $bloodType = $request->blood_type;
        $rhesus = $request->rhesus;

        // Determine distance range based on wave. If no wave given, cover the
        // full range across all waves instead of leaving this null (a null
        // range would blow up the HAVING clause below).
        $waveRanges = self::waveRanges();
        $distanceRange = $wave
            ? ($waveRanges[$wave] ?? $waveRanges[1])
            : ['min' => $waveRanges[1]['min'], 'max' => $waveRanges[3]['max']];

        // Using parameterized PDO raw query for protection against SQL Injection
        // Calculating age via TIMESTAMPDIFF
        // Calculating distance via Haversine Formula (R = 6371)
        // Note: For local SQLite, trigonometry functions might fail. This is intended for MySQL connection.

        $sql = "
            SELECT
                id,
                name,
                phone,
                blood_type,
                rhesus,
                last_donor_date,
                (
                    6371 * ACOS(
                        COS(RADIANS(:lat1)) * COS(RADIANS(latitude)) * COS(RADIANS(longitude) - RADIANS(:lon)) +
                        SIN(RADIANS(:lat2)) * SIN(RADIANS(latitude))
                    )
                ) AS distance_km
            FROM users
            WHERE is_available = 1
              AND latitude IS NOT NULL
              AND longitude IS NOT NULL
              AND weight >= 45
              AND birth_date IS NOT NULL
              AND TIMESTAMPDIFF(YEAR, birth_date, CURRENT_DATE) >= 17
              AND TIMESTAMPDIFF(YEAR, birth_date, CURRENT_DATE) <= 60
              AND (last_donor_date IS NULL OR DATEDIFF(CURRENT_DATE, last_donor_date) >= :cooldown_days)
              AND blood_type = :blood_type
              AND rhesus = :rhesus
              AND id NOT IN (
                  SELECT user_id FROM donor_candidates WHERE blood_request_id = :existing_request_id
              )
            HAVING distance_km >= :min_distance AND distance_km <= :max_distance
            ORDER BY distance_km ASC
        ";

        $results = DB::select($sql, [
            'lat1' => $lat,
            'lon' => $lon,
            'lat2' => $lat,
            'blood_type' => $bloodType,
            'rhesus' => $rhesus,
            'existing_request_id' => $request->id,
            'cooldown_days' => config('donorconnect.donation_cooldown_days', 56),
            'min_distance' => $distanceRange['min'],
            'max_distance' => $distanceRange['max'],
        ]);

        return collect($results);
    }

    /**
     * Get all waves sequentially.
     * Returns array of collections keyed by wave number.
     *
     * @param BloodRequest $request
     * @return array<int, Collection>
     */
    public function filterAllWaves(BloodRequest $request): array
    {
        return [
            1 => $this->filterEligibleDonors($request, 1),
            2 => $this->filterEligibleDonors($request, 2),
            3 => $this->filterEligibleDonors($request, 3),
        ];
    }
}
