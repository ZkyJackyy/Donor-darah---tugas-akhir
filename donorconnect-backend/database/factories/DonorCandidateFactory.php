<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DonorCandidate>
 */
class DonorCandidateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'blood_request_id' => \App\Models\BloodRequest::factory(),
            'user_id' => \App\Models\User::factory(),
            'distance_km' => fake()->randomFloat(2, 0.1, 15.0),
            'status' => fake()->randomElement(['pending', 'notified', 'confirmed', 'declined', 'verified', 'no_response']),
            'notified_at' => fake()->optional()->dateTime(),
            'confirmed_at' => fake()->optional()->dateTime(),
            'verified_at' => fake()->optional()->dateTime(),
            'verification_method' => fake()->optional()->randomElement(['qr', 'manual']),
        ];
    }
}
