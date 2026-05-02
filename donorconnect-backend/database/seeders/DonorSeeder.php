<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class DonorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Central test coordinate: Jakarta Monas (-6.175392, 106.827153)
        // We'll treat this as the base requesting hospital's location
        $centerLat = -6.175392;
        $centerLon = 106.827153;

        // Generate 50 users around the center
        for ($i = 0; $i < 50; $i++) {
            // 1 degree is roughly 111km. 
            // So a 0.045 offset is ~5km.
            // Let's generate offsets between -0.08 and +0.08 (approx 8.8km bounding box)
            // This ensures a healthy mix of users inside and outside the 5km radius
            
            $latOffset = (mt_rand(-80000, 80000) / 1000000); 
            $lonOffset = (mt_rand(-80000, 80000) / 1000000);

            User::factory()->create([
                'latitude' => $centerLat + $latOffset,
                'longitude' => $centerLon + $lonOffset,
            ]);
        }
    }
}
