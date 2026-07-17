<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use App\Models\User;
use App\Models\BloodRequest;
use App\Services\DonorFilterService;
use Carbon\Carbon;

class DonorFilterServiceTest extends TestCase
{
    use RefreshDatabase;

    private DonorFilterService $service;
    private BloodRequest $request;
    
    // Test base coordinates (Jakarta Monas)
    private float $baseLat = -6.175392;
    private float $baseLon = 106.827153;

    protected function setUp(): void
    {
        parent::setUp();

        // Query DonorFilterService pakai fungsi trig raw SQL (ACOS/RADIANS) yang
        // hanya didukung MySQL, tidak ada di SQLite (dipakai untuk test lokal/CI).
        if (DB::connection()->getDriverName() === 'sqlite') {
            $this->markTestSkipped('SQLite does not natively support geometric raw SQL functions used in Haversine.');
        }

        $this->service = new DonorFilterService();

        // Setup a baseline request
        $this->request = BloodRequest::factory()->create([
            'blood_type' => 'O',
            'rhesus' => '+',
            'latitude' => $this->baseLat,
            'longitude' => $this->baseLon,
        ]);
    }

    public function test_medical_eligibility_filter()
    {
        // 1. Eligible User
        User::factory()->create([
            'birth_date' => Carbon::now()->subYears(20)->format('Y-m-d'),
            'weight' => 60,
            'last_donor_date' => Carbon::now()->subDays(90)->format('Y-m-d'),
            'is_available' => true,
            'blood_type' => 'O',
            'rhesus' => '+',
            'latitude' => $this->baseLat + 0.01, // ~1km
            'longitude' => $this->baseLon + 0.01,
        ]);

        // 2. Underage User (16 years)
        User::factory()->create([
            'birth_date' => Carbon::now()->subYears(16)->format('Y-m-d'),
            'weight' => 60,
            'last_donor_date' => null,
            'is_available' => true,
            'blood_type' => 'O',
            'rhesus' => '+',
            'latitude' => $this->baseLat + 0.01,
            'longitude' => $this->baseLon + 0.01,
        ]);

        // 3. Underweight User (40 kg)
        User::factory()->create([
            'birth_date' => Carbon::now()->subYears(20)->format('Y-m-d'),
            'weight' => 40,
            'last_donor_date' => null,
            'is_available' => true,
            'blood_type' => 'O',
            'rhesus' => '+',
            'latitude' => $this->baseLat + 0.01,
            'longitude' => $this->baseLon + 0.01,
        ]);

        // 4. Cooldown Active User (Donated 30 days ago)
        User::factory()->create([
            'birth_date' => Carbon::now()->subYears(20)->format('Y-m-d'),
            'weight' => 60,
            'last_donor_date' => Carbon::now()->subDays(30)->format('Y-m-d'),
            'is_available' => true,
            'blood_type' => 'O',
            'rhesus' => '+',
            'latitude' => $this->baseLat + 0.01,
            'longitude' => $this->baseLon + 0.01,
        ]);

        $results = $this->service->filterEligibleDonors($this->request);

        // Only user #1 should pass medical requirements
        $this->assertCount(1, $results);
    }

    public function test_blood_type_filter()
    {
        // Correct Type (O+)
        User::factory()->create([
            'birth_date' => '1990-01-01',
            'weight' => 70,
            'blood_type' => 'O',
            'rhesus' => '+',
            'is_available' => true,
            'latitude' => $this->baseLat + 0.01,
            'longitude' => $this->baseLon + 0.01,
        ]);

        // Wrong Blood Group (A+)
        User::factory()->create([
            'birth_date' => '1990-01-01',
            'weight' => 70,
            'blood_type' => 'A',
            'rhesus' => '+',
            'is_available' => true,
            'latitude' => $this->baseLat + 0.01,
            'longitude' => $this->baseLon + 0.01,
        ]);

        // Wrong Rhesus (O-)
        User::factory()->create([
            'birth_date' => '1990-01-01',
            'weight' => 70,
            'blood_type' => 'O',
            'rhesus' => '-',
            'is_available' => true,
            'latitude' => $this->baseLat + 0.01,
            'longitude' => $this->baseLon + 0.01,
        ]);

        $results = $this->service->filterEligibleDonors($this->request);

        $this->assertCount(1, $results);
        $this->assertEquals('O', $results->first()->blood_type);
    }

    public function test_geolocation_filter()
    {
        // Inside radius (approx 3km away)
        User::factory()->create([
            'name' => 'Inside Radius',
            'birth_date' => '1990-01-01',
            'weight' => 70,
            'blood_type' => 'O',
            'rhesus' => '+',
            'is_available' => true,
            'latitude' => $this->baseLat + 0.025,
            'longitude' => $this->baseLon + 0.025,
        ]);

        // Outside radius (approx 10km away)
        User::factory()->create([
            'name' => 'Outside Radius',
            'birth_date' => '1990-01-01',
            'weight' => 70,
            'blood_type' => 'O',
            'rhesus' => '+',
            'is_available' => true,
            'latitude' => $this->baseLat + 0.09,
            'longitude' => $this->baseLon + 0.09,
        ]);

        $results = $this->service->filterEligibleDonors($this->request);

        $this->assertCount(1, $results);
        $this->assertEquals('Inside Radius', $results->first()->name);
    }

    public function test_sorting_and_return_structure()
    {
        // User A - Close (~1km)
        User::factory()->create([
            'name' => 'User A',
            'birth_date' => '1990-01-01',
            'weight' => 70,
            'blood_type' => 'O',
            'rhesus' => '+',
            'is_available' => true,
            'last_donor_date' => null,
            'latitude' => $this->baseLat + 0.01,
            'longitude' => $this->baseLon + 0.01,
        ]);

        // User B - Closer (~0.5km)
        User::factory()->create([
            'name' => 'User B',
            'birth_date' => '1990-01-01',
            'weight' => 70,
            'blood_type' => 'O',
            'rhesus' => '+',
            'is_available' => true,
            'last_donor_date' => null,
            'latitude' => $this->baseLat + 0.005,
            'longitude' => $this->baseLon + 0.005,
        ]);

        $results = $this->service->filterEligibleDonors($this->request);

        $this->assertCount(2, $results);
        
        // Assert structure
        $firstResult = clone $results->first();
        $this->assertObjectHasProperty('id', $firstResult);
        $this->assertObjectHasProperty('name', $firstResult);
        $this->assertObjectHasProperty('phone', $firstResult);
        $this->assertObjectHasProperty('distance_km', $firstResult);
        $this->assertObjectHasProperty('last_donor_date', $firstResult);
        $this->assertObjectHasProperty('blood_type', $firstResult);

        // Assert sorting (User B should be first because they are closer)
        $this->assertEquals('User B', $results->first()->name);
        $this->assertEquals('User A', $results->last()->name);
    }
}
