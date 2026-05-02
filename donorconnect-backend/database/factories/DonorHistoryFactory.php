<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DonorHistory>
 */
class DonorHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'blood_request_id' => \App\Models\BloodRequest::factory(),
            'donor_date' => fake()->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
            'location_name' => fake()->company() . ' Hospital',
            'verified_by' => \App\Models\User::factory(),
        ];
    }
}
