<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\DriverRating;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DriverRating>
 */
class DriverRatingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => User::factory()->state(['role' => UserRole::Customer]),
            'order_id' => Order::factory(),
            'driver_user_id' => User::factory()->state(['role' => UserRole::Driver]),
            'score' => 5,
            'comment' => null,
        ];
    }
}
