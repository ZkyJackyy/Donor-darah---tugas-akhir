<?php

namespace Tests\Feature;

use App\Models\AdminAlert;
use App\Models\BloodRequest;
use App\Models\DonorCandidate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_alert_is_created_when_last_pending_candidate_declines()
    {
        $donorA = User::factory()->create(['role' => 'user']);
        $donorB = User::factory()->create(['role' => 'user']);
        $bloodRequest = BloodRequest::factory()->create(['status' => 'open', 'required_bags' => 1]);

        $candidateA = DonorCandidate::create([
            'blood_request_id' => $bloodRequest->id,
            'user_id' => $donorA->id,
            'status' => 'declined',
            'distance_km' => 2.5,
        ]);
        $candidateB = DonorCandidate::create([
            'blood_request_id' => $bloodRequest->id,
            'user_id' => $donorB->id,
            'status' => 'notified',
            'distance_km' => 3.0,
        ]);

        $this->actingAs($donorB, 'sanctum')
            ->postJson('/api/donor/confirm', [
                'donor_candidate_id' => $candidateB->id,
                'status' => 'declined',
            ])
            ->assertOk();

        $this->assertDatabaseCount('admin_alerts', 1);
        $alert = AdminAlert::first();
        $this->assertEquals('all_declined', $alert->type);
        $this->assertEquals($bloodRequest->id, $alert->blood_request_id);
        $this->assertNull($alert->read_at);
    }

    public function test_no_alert_when_a_candidate_is_still_pending()
    {
        $donorA = User::factory()->create(['role' => 'user']);
        $donorB = User::factory()->create(['role' => 'user']);
        $bloodRequest = BloodRequest::factory()->create(['status' => 'open', 'required_bags' => 1]);

        DonorCandidate::create([
            'blood_request_id' => $bloodRequest->id,
            'user_id' => $donorB->id,
            'status' => 'notified',
            'distance_km' => 3.0,
        ]);
        $candidateA = DonorCandidate::create([
            'blood_request_id' => $bloodRequest->id,
            'user_id' => $donorA->id,
            'status' => 'notified',
            'distance_km' => 2.5,
        ]);

        $this->actingAs($donorA, 'sanctum')
            ->postJson('/api/donor/confirm', [
                'donor_candidate_id' => $candidateA->id,
                'status' => 'declined',
            ])
            ->assertOk();

        $this->assertDatabaseCount('admin_alerts', 0);
    }

    public function test_admin_can_view_and_mark_alert_read()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $bloodRequest = BloodRequest::factory()->create(['status' => 'open']);
        $alert = AdminAlert::create([
            'type' => 'all_declined',
            'message' => 'Seluruh kandidat menolak',
            'blood_request_id' => $bloodRequest->id,
        ]);

        $this->actingAs($admin)->get('/admin/alerts')->assertOk()->assertSee('Seluruh kandidat menolak');

        $this->actingAs($admin)->post("/admin/alerts/{$alert->id}/read")->assertRedirect();
        $this->assertNotNull($alert->fresh()->read_at);
    }
}
