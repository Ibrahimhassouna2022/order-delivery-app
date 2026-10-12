<?php

namespace Tests\Feature\Models;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Enums\WalletEntryType;
use App\Models\DriverWalletLedgerEntry;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverWalletLedgerEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivery_credit_is_unique_without_limiting_other_wallet_entries(): void
    {
        $driver = User::factory()->create(['role' => UserRole::Driver]);
        $customer = User::factory()->create(['role' => UserRole::Customer]);
        $restaurant = Restaurant::create([
            'name' => 'Olive Kitchen',
            'address' => 'Ramallah',
        ]);
        $location = $customer->deliveryLocations()->create([
            'label' => 'Home',
            'address' => 'Central Ramallah',
        ]);
        $order = Order::create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'delivery_location_id' => $location->id,
            'status' => OrderStatus::Delivered,
            'idempotency_key' => 'order-request-002',
            'currency_code' => 'ILS',
            'items_subtotal' => '18.500',
            'delivery_fee' => '5.000',
            'total' => '23.500',
        ]);

        DriverWalletLedgerEntry::create([
            'driver_user_id' => $driver->id,
            'order_id' => $order->id,
            'delivery_credit_order_id' => $order->id,
            'entry_type' => WalletEntryType::DeliveryCredit,
            'amount' => '4.000',
            'currency_code' => 'ILS',
            'idempotency_key' => 'delivery-credit-order-2',
        ]);

        DriverWalletLedgerEntry::create([
            'driver_user_id' => $driver->id,
            'order_id' => $order->id,
            'entry_type' => WalletEntryType::PayoutDebit,
            'amount' => '2.000',
            'currency_code' => 'ILS',
            'idempotency_key' => 'payout-order-2-a',
        ]);

        DriverWalletLedgerEntry::create([
            'driver_user_id' => $driver->id,
            'order_id' => $order->id,
            'entry_type' => WalletEntryType::PayoutDebit,
            'amount' => '2.000',
            'currency_code' => 'ILS',
            'idempotency_key' => 'payout-order-2-b',
        ]);

        $this->assertDatabaseCount('driver_wallet_ledger_entries', 3);

        try {
            DriverWalletLedgerEntry::create([
                'driver_user_id' => $driver->id,
                'order_id' => $order->id,
                'delivery_credit_order_id' => $order->id,
                'entry_type' => WalletEntryType::DeliveryCredit,
                'amount' => '4.000',
                'currency_code' => 'ILS',
                'idempotency_key' => 'duplicate-delivery-credit-order-2',
            ]);
            $this->fail('A duplicate delivery credit should violate the unique constraint.');
        } catch (QueryException) {
            $this->assertDatabaseCount('driver_wallet_ledger_entries', 3);
        }
    }
}
