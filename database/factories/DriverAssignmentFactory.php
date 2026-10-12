<?php

namespace Database\Factories;

use App\Enums\DriverAssignmentStatus;
use App\Enums\UserRole;
use App\Models\DriverAssignment;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DriverAssignment>
 */
class DriverAssignmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'driver_user_id' => User::factory()->state(['role' => UserRole::Driver]),
            'status' => DriverAssignmentStatus::Offered,
            'distance_meters' => null,
            'fare' => null,
            'currency_code' => null,
            'offered_at' => now(),
            'responded_at' => null,
            'picked_up_at' => null,
            'delivered_at' => null,
            'expires_at' => now()->addSeconds(30),
        ];
    }

    /**
     * Indicate that the offer's response window has already elapsed.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DriverAssignmentStatus::Offered,
            'offered_at' => now()->subMinutes(5),
            'expires_at' => now()->subSeconds(30),
        ]);
    }
}
