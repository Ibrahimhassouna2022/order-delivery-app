<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\DriverLocation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DriverLocation>
 */
class DriverLocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'driver_user_id' => User::factory()->state(['role' => UserRole::Driver]),
            'latitude' => '31.9000000',
            'longitude' => '35.2000000',
            'recorded_at' => now(),
        ];
    }
}
