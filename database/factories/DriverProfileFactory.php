<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\DriverProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DriverProfile>
 */
class DriverProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => UserRole::Driver]),
            'is_available' => false,
            'availability_updated_at' => null,
            'completed_deliveries_count' => 0,
        ];
    }
}
