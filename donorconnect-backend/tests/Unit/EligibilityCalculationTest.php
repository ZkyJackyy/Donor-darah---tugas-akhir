<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EligibilityCalculationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_under_weight_is_made_unavailable()
    {
        $user = User::factory()->create([
            'weight' => 50,
            'is_available' => true
        ]);

        $response = $this->actingAs($user)->putJson('/api/profile/update', [
            'weight' => 44
        ]);

        $response->assertStatus(200);
        $this->assertFalse($response->json('data.is_available') ?? false);
    }

    public function test_user_under_age_is_made_unavailable()
    {
        $user = User::factory()->create([
            'birth_date' => now()->subYears(20)->toDateString(),
            'is_available' => true
        ]);

        $response = $this->actingAs($user)->putJson('/api/profile/update', [
            'birth_date' => now()->subYears(16)->toDateString()
        ]);

        $response->assertStatus(200);
        
        // Refresh underlying model to assert persistence
        $user->refresh();
        $this->assertFalse((bool) $user->is_available);
    }
}
