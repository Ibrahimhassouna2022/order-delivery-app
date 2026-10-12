<?php

namespace Tests\Feature\Api;

use App\Actions\Driver\CreateDriverOfferAction;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\DriverProfile;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Customer cancellation rules (FR-041).
 */
class CustomerOrderCancellationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_cancels_a_placed_order_before_assignment(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);
        $order = Order::factory()->for($customer, 'customer')->create();

        $this->withToken($customer->createToken('customer')->plainTextToken)
            ->postJson("/api/v1/orders/{$order->id}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', OrderStatus::Cancelled->value)
            ->assertJsonPath('data.public_status', OrderStatus::Cancelled->value);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Cancelled->value,
            'cancelled_by_user_id' => $customer->id,
        ]);
        $this->assertNotNull($order->refresh()->cancelled_at);
    }

    public function test_owner_cannot_cancel_once_a_driver_is_assigned(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);
        $driver = User::factory()->create(['role' => UserRole::Driver]);
        DriverProfile::factory()->for($driver)->create(['is_available' => true]);
        $order = Order::factory()->for($customer, 'customer')->create([
            'status' => OrderStatus::ReadyForPickup,
        ]);

        app(CreateDriverOfferAction::class)->execute($order, [
            'driver_user_id' => $driver->id,
            'distance_meters' => 1200,
            'fare' => '6.500',
        ]);

        $this->withToken($customer->createToken('customer')->plainTextToken)
            ->postJson("/api/v1/orders/{$order->id}/cancel")
            ->assertConflict();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::ReadyForPickup->value,
            'cancelled_at' => null,
        ]);
    }

    public function test_a_stranger_cannot_cancel_another_customers_order(): void
    {
        $owner = User::factory()->create(['role' => UserRole::Customer]);
        $stranger = User::factory()->create(['role' => UserRole::Customer]);
        $order = Order::factory()->for($owner, 'customer')->create();

        $this->withToken($stranger->createToken('stranger')->plainTextToken)
            ->postJson("/api/v1/orders/{$order->id}/cancel")
            ->assertForbidden();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Placed->value,
        ]);
    }
}
