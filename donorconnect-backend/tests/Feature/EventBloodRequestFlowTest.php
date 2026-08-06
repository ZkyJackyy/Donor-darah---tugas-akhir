<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\BloodRequest;

class EventBloodRequestFlowTest extends TestCase
{
    use RefreshDatabase;

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
