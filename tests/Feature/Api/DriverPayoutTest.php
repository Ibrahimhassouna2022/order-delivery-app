<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Enums\WalletEntryType;
use App\Models\DriverWalletLedgerEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Wallet payout completion (FR-022): a payout debit is recorded exactly once
 * and the balance decreases; retries with the same key never double-debit.
 */
class DriverPayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_payout_records_a_payout_debit_and_confirms_the_amount(): void
    {
        $driver = User::factory()->create(['role' => UserRole::Driver]);
        DriverWalletLedgerEntry::factory()->create([
            'driver_user_id' => $driver->id,
            'entry_type' => WalletEntryType::DeliveryCredit,
            'amount' => '12.500',
            'currency_code' => 'ILS',
            'idempotency_key' => 'credit-order-1',
        ]);

        $this->withToken($driver->createToken('driver')->plainTextToken)
            ->postJson('/api/v1/driver/payouts', [
                'currency_code' => 'ILS',
                'idempotency_key' => 'payout-1',
            ])
            ->assertCreated()
            ->assertJsonPath('data.entry_type', WalletEntryType::PayoutDebit->value)
            ->assertJsonPath('data.amount', '12.500')
            ->assertJsonPath('data.currency_code', 'ILS');

        $this->assertDatabaseHas('driver_wallet_ledger_entries', [
            'driver_user_id' => $driver->id,
            'entry_type' => WalletEntryType::PayoutDebit->value,
            'amount' => '12.500',
            'currency_code' => 'ILS',
            'idempotency_key' => 'payout-1',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'driver.payout_completed',
            'actor_user_id' => $driver->id,
        ]);
    }

    public function test_retrying_with_the_same_idempotency_key_does_not_double_debit(): void
    {
        $driver = User::factory()->create(['role' => UserRole::Driver]);
        DriverWalletLedgerEntry::factory()->create([
            'driver_user_id' => $driver->id,
            'entry_type' => WalletEntryType::DeliveryCredit,
            'amount' => '12.500',
            'currency_code' => 'ILS',
            'idempotency_key' => 'credit-order-1',
        ]);
        $token = $driver->createToken('driver')->plainTextToken;
        $payload = ['currency_code' => 'ILS', 'idempotency_key' => 'payout-1'];

        $first = $this->withToken($token)->postJson('/api/v1/driver/payouts', $payload);
        $first->assertCreated();

        $this->withToken($token)->postJson('/api/v1/driver/payouts', $payload)
            ->assertOk()
            ->assertJsonPath('data.id', $first->json('data.id'));

        $this->assertDatabaseCount('driver_wallet_ledger_entries', 2);
    }

    public function test_payout_with_no_balance_conflicts(): void
    {
        $driver = User::factory()->create(['role' => UserRole::Driver]);

        $this->withToken($driver->createToken('driver')->plainTextToken)
            ->postJson('/api/v1/driver/payouts', [
                'currency_code' => 'ILS',
                'idempotency_key' => 'payout-empty',
            ])
            ->assertConflict();
    }

    public function test_payout_requires_an_idempotency_key(): void
    {
        $driver = User::factory()->create(['role' => UserRole::Driver]);

        $this->withToken($driver->createToken('driver')->plainTextToken)
            ->postJson('/api/v1/driver/payouts', ['currency_code' => 'ILS'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('idempotency_key');
    }
}
