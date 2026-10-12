<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Enums\WalletEntryType;
use App\Models\DriverWalletLedgerEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DriverWalletLedgerEntry>
 */
class DriverWalletLedgerEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'driver_user_id' => User::factory()->state(['role' => UserRole::Driver]),
            'order_id' => null,
            'delivery_credit_order_id' => null,
            'entry_type' => WalletEntryType::Adjustment,
            'amount' => '4.000',
            'currency_code' => 'ILS',
            'idempotency_key' => fake()->uuid(),
        ];
    }
}
