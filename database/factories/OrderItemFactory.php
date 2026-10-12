<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
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
            'menu_item_id' => null,
            'item_name' => fake()->words(3, true),
            'unit_price' => '10.000',
            'quantity' => 1,
            'line_total' => '10.000',
        ];
    }
}
