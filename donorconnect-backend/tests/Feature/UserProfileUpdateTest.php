<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_updating_weight_below_minimum_locks_availability()
    {
        $user = User::factory()->create(['weight' => 60, 'is_available' => true, 'last_donor_date' => null]);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/profile/update', ['weight' => 40])
            ->assertOk();

        $this->assertFalse($user->fresh()->is_available);
    }

    public function test_correcting_weight_back_above_minimum_unlocks_availability()
    {
        $user = User::factory()->create(['weight' => 40, 'is_available' => false, 'last_donor_date' => null]);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/profile/update', ['weight' => 60])
            ->assertOk();

        $this->assertTrue($user->fresh()->is_available);
    }

    public function test_correcting_weight_does_not_unlock_while_still_in_donation_cooldown()
    {
        $user = User::factory()->create([
            'weight' => 40,
            'is_available' => false,
            'last_donor_date' => now()->subDays(10)->toDateString(),
        ]);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/profile/update', ['weight' => 60])
            ->assertOk();

        $this->assertFalse($user->fresh()->is_available);
    }

    public function test_explicit_is_available_false_is_not_overridden_by_eligible_weight()
    {
        $user = User::factory()->create(['weight' => 60, 'is_available' => true, 'last_donor_date' => null]);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/profile/update', ['is_available' => false])
            ->assertOk();

        $this->assertFalse($user->fresh()->is_available);
    }

    public function test_underage_birth_date_locks_availability_even_if_explicitly_requested_true()
    {
        $user = User::factory()->create([
            'birth_date' => now()->subYears(16)->toDateString(),
            'is_available' => true,
            'last_donor_date' => null,
        ]);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/profile/update', ['is_available' => true])
            ->assertOk();

        $this->assertFalse($user->fresh()->is_available);
    }
}
