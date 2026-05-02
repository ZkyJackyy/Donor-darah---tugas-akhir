<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BloodRequest>
 */
class BloodRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'admin_id' => \App\Models\User::factory(),
            'blood_type' => fake()->randomElement(['A', 'B', 'AB', 'O']),
            'rhesus' => fake()->randomElement(['+', '-']),
            'urgency_level' => fake()->randomElement(['normal', 'urgent', 'critical']),
            'hospital_name' => fake()->company() . ' Hospital',
            'hospital_address' => fake()->address(),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'required_bags' => fake()->numberBetween(1, 10),
            'status' => fake()->randomElement(['open', 'fulfilled', 'cancelled']),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
