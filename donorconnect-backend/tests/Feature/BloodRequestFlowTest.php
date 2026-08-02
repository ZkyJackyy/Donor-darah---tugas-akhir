<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\BloodRequest;
use App\Models\DonorCandidate;

class BloodRequestFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_blood_request_flow()
    {
        // 1. Setup Admin & User
        $admin = User::factory()->create(['role' => 'admin']);
        $donor = User::factory()->create([
            'role' => 'user', 'is_available' => true
        ]);

        // 2. Admin Creates Blood Request
        $response = $this->actingAs($admin)->postJson('/api/admin-poll/blood-requests/store-mock', [
            // Using direct model creation for internal state setup to avoid heavy validation mocking
        ]);
        
        $request = BloodRequest::factory()->create([
            'admin_id' => $admin->id,
            'status' => 'open'
        ]);

        // 3. User becomes Candidate and completes the mandatory self-screening
        $candidate = DonorCandidate::create([
            'blood_request_id' => $request->id,
            'user_id' => $donor->id,
            'status' => 'notified',
            'distance_km' => 2.5
        ]);

        $screeningResponse = $this->actingAs($donor)->postJson('/api/donor/screening', [
            'donor_candidate_id' => $candidate->id,
            'health_status' => true,
            'min_weight' => true,
            'no_medicine' => true,
            'not_pregnant' => true,
        ]);

        $screeningResponse->assertStatus(200);
        $candidate->refresh();
        $this->assertEquals('screening_passed', $candidate->status);

        // 4. User confirms via API
        $response = $this->actingAs($donor)->postJson('/api/donor/confirm', [
            'donor_candidate_id' => $candidate->id,
            'status' => 'confirmed'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['data' => ['kode_verifikasi']]);

        // 5. Admin verifies using the donor's verification code
        $kodeVerifikasi = $response->json('data.kode_verifikasi');

        $verifyResponse = $this->actingAs($admin)->postJson('/api/verify/code', [
            'kode_verifikasi' => $kodeVerifikasi
        ]);

        $verifyResponse->assertStatus(200);

        // 6. Assert State
        $candidate->refresh();
        $this->assertEquals('verified', $candidate->status);

        $donor->refresh();
        $this->assertFalse((bool) $donor->is_available);
        $this->assertEquals(now()->toDateString(), $donor->last_donor_date->format('Y-m-d'));
    }

    public function test_donor_cannot_confirm_without_completing_screening()
    {
        $donor = User::factory()->create(['role' => 'user', 'is_available' => true]);
        $request = BloodRequest::factory()->create(['status' => 'open']);

        $candidate = DonorCandidate::create([
            'blood_request_id' => $request->id,
            'user_id' => $donor->id,
            'status' => 'notified',
            'distance_km' => 2.5,
        ]);

        $response = $this->actingAs($donor)->postJson('/api/donor/confirm', [
            'donor_candidate_id' => $candidate->id,
            'status' => 'confirmed',
        ]);

        $response->assertStatus(400);
        $candidate->refresh();
        $this->assertEquals('notified', $candidate->status);
        $this->assertNull($candidate->kode_verifikasi);
    }

    public function test_donor_cannot_confirm_once_request_is_fulfilled()
    {
        $donorA = User::factory()->create(['role' => 'user', 'is_available' => true]);
        $donorB = User::factory()->create(['role' => 'user', 'is_available' => true]);
        $admin = User::factory()->create(['role' => 'admin']);
        $request = BloodRequest::factory()->create(['status' => 'open', 'required_bags' => 1]);

        $candidateA = DonorCandidate::create([
            'blood_request_id' => $request->id,
            'user_id' => $donorA->id,
            'status' => 'screening_passed',
            'distance_km' => 2.5,
        ]);

        $confirmA = $this->actingAs($donorA)->postJson('/api/donor/confirm', [
            'donor_candidate_id' => $candidateA->id,
            'status' => 'confirmed',
        ]);
        $confirmA->assertStatus(200);

        $this->actingAs($admin)->postJson('/api/verify/code', [
            'kode_verifikasi' => $confirmA->json('data.kode_verifikasi'),
        ])->assertStatus(200);

        $this->assertEquals('fulfilled', $request->fresh()->status);

        // Candidate B was already screening_passed before the request closed —
        // confirming now must be rejected instead of minting another valid ticket.
        $candidateB = DonorCandidate::create([
            'blood_request_id' => $request->id,
            'user_id' => $donorB->id,
            'status' => 'screening_passed',
            'distance_km' => 3.1,
        ]);

        $confirmB = $this->actingAs($donorB)->postJson('/api/donor/confirm', [
            'donor_candidate_id' => $candidateB->id,
            'status' => 'confirmed',
        ]);

        $confirmB->assertStatus(400);
        $candidateB->refresh();
        $this->assertEquals('screening_passed', $candidateB->status);
        $this->assertNull($candidateB->kode_verifikasi);
    }
}
