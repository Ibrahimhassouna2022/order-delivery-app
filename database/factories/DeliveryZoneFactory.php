<?php

namespace Database\Factories;

use App\Models\DeliveryZone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryZone>
 */
class DeliveryZoneFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->city(),
            'fee' => fake()->randomElement(['5.000', '6.000', '7.000', '8.000']),
            'currency_code' => 'ILS',
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 20),
        ];
    }
}
