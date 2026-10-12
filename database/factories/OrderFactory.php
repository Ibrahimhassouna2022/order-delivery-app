<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\DeliveryLocation;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
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
            'restaurant_id' => Restaurant::factory(),
            'delivery_location_id' => DeliveryLocation::factory(),
            'type' => 'food',
            'status' => OrderStatus::Placed,
            'idempotency_key' => fake()->uuid(),
            'currency_code' => 'ILS',
            'items_subtotal' => '0.000',
            'delivery_fee' => '0.000',
            'total' => '0.000',
            'placed_at' => now(),
            'cancelled_at' => null,
            'cancelled_by_user_id' => null,
        ];
    }

    /**
     * Indicate that the order was cancelled (FR-021).
     */
    public function cancelled(?User $by = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Cancelled,
            'cancelled_at' => now(),
            'cancelled_by_user_id' => $by?->getKey(),
        ]);
    }
}
