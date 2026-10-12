<?php

namespace Database\Factories;

use App\Models\DeliveryLocation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryLocation>
 */
class DeliveryLocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'label' => 'Home',
            'address' => fake()->address(),
            'latitude' => null,
            'longitude' => null,
            'delivery_instructions' => null,
            'is_default' => true,
        ];
    }
}
