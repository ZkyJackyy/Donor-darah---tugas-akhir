<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\BloodRequest;
use App\Services\DonorFilterService;

class HaversineCalculationTest extends TestCase
{
    use RefreshDatabase;

    public function test_haversine_calculates_distance_accurately()
    {
        // Skip for local SQLite in-memory tests since trig functions like ACOS/SIN don't exist
        if (DB::connection()->getDriverName() === 'sqlite') {
            $this->markTestSkipped('SQLite does not natively support geometric raw SQL functions used in Haversine.');
        }

        // Central Monas, Jakarta
        $reqLat = -6.175392;
        $reqLon = 106.827153;

        // Bundaran HI (Approx 2.2 KM away)
        $donorLat = -6.195023;
        $donorLon = 106.823027;

        $user = User::factory()->create([
            'latitude' => $donorLat,
            'longitude' => $donorLon,
            'is_available' => true,
            'weight' => 60,
            'birth_date' => now()->subYears(20)->toDateString(),
            'last_donor_date' => now()->subDays(100)->toDateString(),
            'blood_type' => 'O',
            'rhesus' => '+'
        ]);

        $request = BloodRequest::factory()->create([
            'admin_id' => $user->id,
            'blood_type' => 'O',
            'rhesus' => '+',
            'urgency_level' => 'normal',
            'hospital_name' => 'RS Pusat',
            'hospital_address' => 'Jakarta',
            'latitude' => $reqLat,
            'longitude' => $reqLon,
            'required_bags' => 1,
            'status' => 'open'
        ]);

        $service = app(DonorFilterService::class);
        $results = $service->filterEligibleDonors($request);

        $this->assertCount(1, $results);
        
        $distance = (float) $results->first()->distance_km;
        // Verify real-world bounding logic
        $this->assertGreaterThan(1.0, $distance);
        $this->assertLessThan(4.0, $distance);
    }
}
