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
    
    // Radius selalu dipusatkan di lokasi PMI (bukan lokasi request) — pakai
    // koordinat PMI yang sama dengan config('donorconnect.default_lat'/'default_lng')
    // sebagai basis, supaya test tetap merepresentasikan perilaku nyata.
    private float $baseLat;
    private float $baseLon;

    protected function setUp(): void
    {
        parent::setUp();

        // Query DonorFilterService pakai fungsi trig raw SQL (ACOS/RADIANS) yang
        // hanya didukung MySQL, tidak ada di SQLite (dipakai untuk test lokal/CI).
        if (DB::connection()->getDriverName() === 'sqlite') {
            $this->markTestSkipped('SQLite does not natively support geometric raw SQL functions used in Haversine.');
        }

        $this->service = new DonorFilterService();
        $this->baseLat = (float) config('donorconnect.default_lat');
        $this->baseLon = (float) config('donorconnect.default_lng');

        // Request's own latitude/longitude is deliberately set far away (Jakarta
        // Monas) from PMI (Padang) to prove the filter ignores it — radius must
        // still be centered on PMI, not on this value.
        $this->request = BloodRequest::factory()->create([
            'blood_type' => 'O',
            'rhesus' => '+',
            'latitude' => -6.175392,
            'longitude' => 106.827153,
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

    public function test_radius_is_centered_on_pmi_not_on_request_location()
    {
        // Donor near PMI (the request's stored lat/lng is Jakarta, ~900km away)
        User::factory()->create([
            'name' => 'Near PMI',
            'birth_date' => '1990-01-01',
            'weight' => 70,
            'blood_type' => 'O',
            'rhesus' => '+',
            'is_available' => true,
            'latitude' => $this->baseLat + 0.01,
            'longitude' => $this->baseLon + 0.01,
        ]);

        // Donor near the request's own (patient hospital) coordinates — must
        // NOT be picked up, since donors never travel to the patient's hospital.
        User::factory()->create([
            'name' => 'Near Request Location',
            'birth_date' => '1990-01-01',
            'weight' => 70,
            'blood_type' => 'O',
            'rhesus' => '+',
            'is_available' => true,
            'latitude' => -6.175392 + 0.01,
            'longitude' => 106.827153 + 0.01,
        ]);

        $results = $this->service->filterEligibleDonors($this->request);

        $this->assertCount(1, $results);
        $this->assertEquals('Near PMI', $results->first()->name);
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
