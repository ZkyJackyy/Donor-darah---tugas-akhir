<?php

namespace App\Services;

use App\Models\BloodRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DonorFilterService
{
    /**
     * Wave definitions for gradual broadcast:
     * Wave 1: 0-5 km (immediate)
     * Wave 2: 5-10 km (if quota not met)
     * Wave 3: 10-20 km (if quota still not met)
     */
    protected const WAVE_RANGES = [
        1 => ['min' => 0, 'max' => 5],
        2 => ['min' => 5, 'max' => 10],
        3 => ['min' => 10, 'max' => 20],
    ];

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
        $distanceRange = $wave
            ? (self::WAVE_RANGES[$wave] ?? self::WAVE_RANGES[1])
            : ['min' => self::WAVE_RANGES[1]['min'], 'max' => self::WAVE_RANGES[3]['max']];

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
              AND (last_donor_date IS NULL OR DATEDIFF(CURRENT_DATE, last_donor_date) >= 56)
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
