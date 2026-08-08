<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterFlowTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Andi Saputra',
            'nik' => '1371010101990001',
            'email' => 'andi.saputra@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '081234567890',
            'birth_date' => '1998-05-15',
            'weight' => 60,
            'blood_type' => 'O',
            'rhesus' => '+',
            'gender' => 'male',
        ], $overrides);
    }

    public function test_user_can_register_with_valid_nik()
    {
        $response = $this->postJson('/api/auth/register', $this->validPayload());

        $response->assertStatus(201);
        $response->assertJsonPath('status', true);
        $response->assertJsonPath('data.user.nik', '1371010101990001');
        $response->assertJsonStructure(['data' => ['access_token', 'user']]);

        $this->assertDatabaseHas('users', [
            'email' => 'andi.saputra@example.com',
            'nik' => '1371010101990001',
        ]);
    }

    public function test_registration_fails_without_nik()
    {
        $payload = $this->validPayload();
        unset($payload['nik']);

        $response = $this->postJson('/api/auth/register', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('nik');
    }

    public function test_registration_fails_when_nik_is_not_16_digits()
    {
        $response = $this->postJson('/api/auth/register', $this->validPayload(['nik' => '12345']));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('nik');
    }

    public function test_registration_fails_when_nik_contains_non_digits()
    {
        $response = $this->postJson('/api/auth/register', $this->validPayload(['nik' => 'abcd567890123456']));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('nik');
    }

    public function test_registration_fails_when_nik_already_registered()
    {
        User::factory()->create(['nik' => '1371010101990001']);

        $response = $this->postJson('/api/auth/register', $this->validPayload([
            'email' => 'lain@example.com',
        ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('nik');
    }
}
