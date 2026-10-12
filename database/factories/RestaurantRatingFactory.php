<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\RestaurantRating;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RestaurantRating>
 */
class RestaurantRatingFactory extends Factory
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
            'restaurant_id' => Restaurant::factory(),
            'score' => 5,
            'comment' => null,
        ];
    }
}
