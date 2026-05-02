<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\User;
use App\Models\BloodRequest;
use App\Models\DonorCandidate;

class BloodRequestFlowTest extends TestCase
{
    use DatabaseTransactions;

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

        // 3. User becomes Candidate
        $candidate = DonorCandidate::create([
            'blood_request_id' => $request->id,
            'user_id' => $donor->id,
            'status' => 'notified',
            'distance_km' => 2.5
        ]);

        // 4. User confirms via API
        $response = $this->actingAs($donor)->postJson('/api/donor/confirm', [
            'donor_candidate_id' => $candidate->id,
            'status' => 'confirmed'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['qr_token']);

        // 5. Admin scans and verifies QR manually tracking QR bounds
        $qrToken = $response->json('qr_token');
        
        $verifyResponse = $this->actingAs($admin)->postJson('/api/verify/qr', [
            'token' => $qrToken
        ]);

        $verifyResponse->assertStatus(200);

        // 6. Assert State
        $candidate->refresh();
        $this->assertEquals('verified', $candidate->status);

        $donor->refresh();
        $this->assertFalse((bool) $donor->is_available);
        $this->assertEquals(now()->toDateString(), $donor->last_donor_date);
    }
}
