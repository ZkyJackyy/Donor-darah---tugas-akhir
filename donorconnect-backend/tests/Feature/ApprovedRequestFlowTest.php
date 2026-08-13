<?php

namespace Tests\Feature;

use App\Models\BloodRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovedRequestFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_approving_a_family_submission_does_not_open_it_directly()
    {
        $bloodRequest = BloodRequest::factory()->create([
            'status' => 'pending_review',
            'requested_by_user_id' => User::factory()->create()->id,
        ]);

        $this->actingAs($this->admin)
            ->post("/admin/blood-requests/{$bloodRequest->id}/approve")
            ->assertRedirect(route('admin.blood-requests.show', $bloodRequest->id));

        $this->assertSame('approved', $bloodRequest->fresh()->status);
    }

    public function test_marking_an_approved_request_fulfilled_skips_open_entirely()
    {
        $bloodRequest = BloodRequest::factory()->create(['status' => 'approved']);

        $this->actingAs($this->admin)
            ->patch("/admin/blood-requests/{$bloodRequest->id}/status", ['status' => 'fulfilled'])
            ->assertSessionHasNoErrors();

        $this->assertSame('fulfilled', $bloodRequest->fresh()->status);
    }

    public function test_sending_wa_broadcast_transitions_approved_to_open()
    {
        $bloodRequest = BloodRequest::factory()->create([
            'status' => 'approved',
            'required_bags' => 1,
            'deadline' => now()->addDay(),
        ]);

        $this->actingAs($this->admin)
            ->post("/admin/blood-requests/{$bloodRequest->id}/notify");

        $this->assertSame('open', $bloodRequest->fresh()->status);
    }

    public function test_status_cannot_be_updated_once_already_fulfilled()
    {
        $bloodRequest = BloodRequest::factory()->create(['status' => 'fulfilled']);

        $this->actingAs($this->admin)
            ->patch("/admin/blood-requests/{$bloodRequest->id}/status", ['status' => 'cancelled'])
            ->assertSessionHas('error');

        $this->assertSame('fulfilled', $bloodRequest->fresh()->status);
    }
}
