<?php

namespace Database\Factories;

use App\Models\MenuItem;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MenuItem>
 */
class MenuItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'restaurant_id' => Restaurant::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'photo_path' => null,
            'price' => '10.000',
            'currency_code' => 'ILS',
            'is_available' => true,
        ];
    }
}
