<?php

namespace Tests\Feature\Api;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\DeliveryLocation;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Operations order monitoring (FR-029): filterable live list + pre-delivery
 * cancellation, admin only.
 */
class OperationsOrderMonitorTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_a_filterable_live_order_list(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $restaurant = Restaurant::factory()->create();

        Order::factory()->for($restaurant, 'restaurant')->create([
            'status' => OrderStatus::Placed,
        ]);
        Order::factory()->for($restaurant, 'restaurant')->create([
            'status' => OrderStatus::Assigned,
        ]);

        $this->withToken($admin->createToken('admin')->plainTextToken)
            ->getJson('/api/v1/operations/orders?status=placed')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', OrderStatus::Placed->value)
            ->assertJsonStructure([
                'data',
                'links',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    public function test_admin_can_filter_orders_by_restaurant_and_city(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $restaurant = Restaurant::factory()->create();
        $cityLocation = DeliveryLocation::factory()->create(['address' => 'Main St, Nazareth']);
        $otherLocation = DeliveryLocation::factory()->create(['address' => 'King St, Tel Aviv']);

        Order::factory()->for($restaurant, 'restaurant')->create([
            'delivery_location_id' => $cityLocation->id,
        ]);
        Order::factory()->for($restaurant, 'restaurant')->create([
            'delivery_location_id' => $otherLocation->id,
        ]);

        $this->withToken($admin->createToken('admin')->plainTextToken)
            ->getJson("/api/v1/operations/orders?restaurant_id={$restaurant->id}&city=Nazareth")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_operations_orders_are_admin_only(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);

        $this->withToken($customer->createToken('customer')->plainTextToken)
            ->getJson('/api/v1/operations/orders')
            ->assertForbidden();
    }

    public function test_admin_can_cancel_a_pre_delivery_order(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $order = Order::factory()->create(['status' => OrderStatus::ReadyForPickup]);

        $this->withToken($admin->createToken('admin')->plainTextToken)
            ->postJson("/api/v1/operations/orders/{$order->id}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', OrderStatus::Cancelled->value)
            ->assertJsonPath('data.public_status', OrderStatus::Cancelled->value);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Cancelled->value,
            'cancelled_by_user_id' => $admin->id,
        ]);
    }

    public function test_admin_cannot_cancel_a_delivered_order(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $order = Order::factory()->create(['status' => OrderStatus::Delivered]);

        $this->withToken($admin->createToken('admin')->plainTextToken)
            ->postJson("/api/v1/operations/orders/{$order->id}/cancel")
            ->assertConflict();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Delivered->value,
        ]);
    }
}
