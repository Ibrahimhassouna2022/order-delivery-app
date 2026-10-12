<?php

namespace Database\Factories;

use App\Enums\RestaurantOnboardingMethod;
use App\Enums\RestaurantStatus;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Restaurant>
 */
class RestaurantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'description' => fake()->paragraph(),
            'address' => fake()->address(),
            'phone' => fake()->phoneNumber(),
            'latitude' => null,
            'longitude' => null,
            'status' => RestaurantStatus::Pending,
            'onboarding_method' => RestaurantOnboardingMethod::Assisted,
            'assisted_onboarding_completed_at' => null,
        ];
    }
}
