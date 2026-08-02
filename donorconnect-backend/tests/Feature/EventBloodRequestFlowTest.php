<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\BloodRequest;
use App\Models\DonorCandidate;

class EventBloodRequestFlowTest extends TestCase
{
    use RefreshDatabase;

    private function eligibleDonor(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'user',
            'is_available' => true,
            'weight' => 60,
            'birth_date' => now()->subYears(25)->toDateString(),
            'last_donor_date' => null,
        ], $overrides));
    }

    public function test_user_can_self_register_to_open_event_without_blood_type_match()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $donor = $this->eligibleDonor(['blood_type' => 'AB', 'rhesus' => '-']);

        $event = BloodRequest::factory()->event()->create([
            'admin_id' => $admin->id,
            'status' => 'open',
        ]);

        $response = $this->actingAs($donor)->postJson("/api/user/blood-requests/{$event->id}/join");

        $response->assertStatus(200);
        $response->assertJsonStructure(['data' => ['kode_verifikasi', 'hospital_name']]);

        $candidate = DonorCandidate::where('blood_request_id', $event->id)
            ->where('user_id', $donor->id)
            ->first();

        $this->assertNotNull($candidate);
        $this->assertEquals('confirmed', $candidate->status);
        $this->assertNotNull($candidate->kode_verifikasi);
        $this->assertNull($candidate->distance_km);
    }

    public function test_user_cannot_join_twice()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $donor = $this->eligibleDonor();
        $event = BloodRequest::factory()->event()->create(['admin_id' => $admin->id, 'status' => 'open']);

        $this->actingAs($donor)->postJson("/api/user/blood-requests/{$event->id}/join")->assertStatus(200);
        $this->actingAs($donor)->postJson("/api/user/blood-requests/{$event->id}/join")->assertStatus(400);

        $this->assertEquals(1, DonorCandidate::where('blood_request_id', $event->id)->where('user_id', $donor->id)->count());
    }

    public function test_ineligible_user_cannot_join_event()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $tooYoung = $this->eligibleDonor(['birth_date' => now()->subYears(15)->toDateString()]);
        $event = BloodRequest::factory()->event()->create(['admin_id' => $admin->id, 'status' => 'open']);

        $response = $this->actingAs($tooYoung)->postJson("/api/user/blood-requests/{$event->id}/join");

        $response->assertStatus(400);
        $this->assertEquals(0, DonorCandidate::where('blood_request_id', $event->id)->count());
    }

    public function test_cannot_join_an_emergency_request()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $donor = $this->eligibleDonor();
        $emergency = BloodRequest::factory()->create(['admin_id' => $admin->id, 'status' => 'open']);

        $response = $this->actingAs($donor)->postJson("/api/user/blood-requests/{$emergency->id}/join");

        $response->assertStatus(403);
    }

    public function test_event_verification_reuses_existing_manual_code_verification()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $donor = $this->eligibleDonor();
        $event = BloodRequest::factory()->event()->create(['admin_id' => $admin->id, 'status' => 'open']);

        $joinResponse = $this->actingAs($donor)->postJson("/api/user/blood-requests/{$event->id}/join");
        $kode = $joinResponse->json('data.kode_verifikasi');

        $verifyResponse = $this->actingAs($admin)->postJson('/api/verify/code', [
            'kode_verifikasi' => $kode,
        ]);

        $verifyResponse->assertStatus(200);

        $donor->refresh();
        $this->assertFalse((bool) $donor->is_available);

        // Event tidak auto-fulfilled walau ada yang verified
        $event->refresh();
        $this->assertEquals('open', $event->status);
    }

    private function eventFormPayload(array $overrides = []): array
    {
        return array_merge([
            'type' => 'event',
            'hospital_name' => 'Lapangan Imam Bonjol',
            'hospital_address' => 'Jl. Imam Bonjol, Padang',
            'latitude' => -0.94,
            'longitude' => 100.36,
            'urgency_level' => 'normal',
            'event_starts_at' => now()->addDays(3)->format('Y-m-d H:i:s'),
            'deadline' => now()->addDays(3)->addHours(6)->format('Y-m-d H:i:s'),
            'notes' => 'Donor darah massal',
        ], $overrides);
    }

    public function test_admin_cannot_create_event_without_event_starts_at()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post('/admin/blood-requests', $this->eventFormPayload(['event_starts_at' => null]));

        $response->assertSessionHasErrors('event_starts_at');
        $this->assertDatabaseMissing('blood_requests', ['hospital_name' => 'Lapangan Imam Bonjol']);
    }

    public function test_admin_cannot_create_event_with_starts_at_after_deadline()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post('/admin/blood-requests', $this->eventFormPayload([
            'event_starts_at' => now()->addDays(5)->format('Y-m-d H:i:s'),
            'deadline' => now()->addDays(3)->format('Y-m-d H:i:s'),
        ]));

        $response->assertSessionHasErrors('event_starts_at');
        $this->assertDatabaseMissing('blood_requests', ['hospital_name' => 'Lapangan Imam Bonjol']);
    }

    public function test_admin_can_create_event_with_valid_schedule()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post('/admin/blood-requests', $this->eventFormPayload());

        $response->assertRedirect();
        $this->assertDatabaseHas('blood_requests', [
            'hospital_name' => 'Lapangan Imam Bonjol',
            'type' => 'event',
        ]);

        $event = BloodRequest::where('hospital_name', 'Lapangan Imam Bonjol')->first();
        $this->assertNotNull($event->event_starts_at);
        $this->assertTrue($event->event_starts_at->lessThan($event->deadline));
    }
}
