<?php

namespace Tests\Feature;

use App\Models\BloodRequest;
use App\Models\DonorCandidate;
use App\Models\DonorHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminVerifyDonorTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $donor;
    protected BloodRequest $bloodRequest;
    protected DonorCandidate $candidate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->donor = User::factory()->create(['role' => 'user', 'is_available' => true]);
        $this->bloodRequest = BloodRequest::factory()->create(['status' => 'open', 'required_bags' => 1]);
        $this->candidate = DonorCandidate::create([
            'blood_request_id' => $this->bloodRequest->id,
            'user_id' => $this->donor->id,
            'status' => 'confirmed',
            'confirmed_at' => now(),
            'kode_verifikasi' => 'ABC123',
            'distance_km' => 2.5,
        ]);
    }

    public function test_admin_can_verify_confirmed_candidate_via_button()
    {
        $response = $this->actingAs($this->admin)
            ->post("/admin/blood-requests/verify/{$this->candidate->id}");

        $response->assertRedirect();
        $this->candidate->refresh();
        $this->assertEquals('verified', $this->candidate->status);
        $this->assertDatabaseCount('donor_histories', 1);
    }

    public function test_verifying_same_candidate_twice_via_button_does_not_duplicate_history()
    {
        $this->actingAs($this->admin)->post("/admin/blood-requests/verify/{$this->candidate->id}");
        $second = $this->actingAs($this->admin)->post("/admin/blood-requests/verify/{$this->candidate->id}");

        $second->assertSessionHas('error');
        $this->assertDatabaseCount('donor_histories', 1);
    }

    public function test_admin_can_verify_candidate_via_kode_verifikasi()
    {
        $response = $this->actingAs($this->admin)
            ->post('/admin/verify', ['kode_verifikasi' => 'abc123']);

        $response->assertRedirect();
        $this->candidate->refresh();
        $this->assertEquals('verified', $this->candidate->status);
        $this->assertDatabaseCount('donor_histories', 1);
    }

    public function test_double_verification_across_both_admin_entry_points_does_not_duplicate_history()
    {
        // One admin verifies via the "Verifikasi" button on the request page...
        $this->actingAs($this->admin)->post("/admin/blood-requests/verify/{$this->candidate->id}");

        // ...while another admin, unaware, submits the same code on the
        // separate "Verifikasi Kode" page straight after.
        $second = $this->actingAs($this->admin)->post('/admin/verify', ['kode_verifikasi' => 'ABC123']);

        $second->assertSessionHas('error');
        $this->assertEquals(1, DonorHistory::where('user_id', $this->donor->id)->count());
    }
}
