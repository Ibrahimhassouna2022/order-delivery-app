<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the account is an anonymous guest (FR-030).
     */
    public function guest(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Guest',
            'email' => null,
            'email_verified_at' => null,
            'password' => null,
            'is_guest' => true,
        ]);
    }

    /**
     * Indicate that the account has a validated deletion request pending
     * the personal data purge window (FR-035 / SC-011).
     */
    public function pendingDeletion(): static
    {
        return $this->state(fn (array $attributes) => [
            'deletion_requested_at' => now(),
            'anonymized_at' => null,
        ]);
    }
}
