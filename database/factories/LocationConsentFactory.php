<?php

namespace Database\Factories;

use App\Models\LocationConsent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LocationConsent>
 */
class LocationConsentFactory extends Factory
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
            'granted_at' => now(),
            'revoked_at' => null,
            'policy_version' => null,
        ];
    }
}
