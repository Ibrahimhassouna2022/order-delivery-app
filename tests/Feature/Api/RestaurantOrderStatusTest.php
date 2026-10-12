<?php

namespace Tests\Feature\Api;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Restaurant status transitions accept → prepare → ready (research R-9).
 */
class RestaurantOrderStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_advances_an_order_through_accept_prepare_ready(): void
    {
        $owner = User::factory()->create(['role' => UserRole::Restaurant]);
        $restaurant = Restaurant::factory()->create();
        $owner->restaurants()->attach($restaurant, ['membership_role' => 'owner']);
        $order = Order::factory()->for($restaurant, 'restaurant')->create([
            'status' => OrderStatus::Placed,
        ]);
        $token = $owner->createToken('restaurant')->plainTextToken;

        $this->withToken($token)
            ->patchJson("/api/v1/restaurant/orders/{$order->id}/status", ['status' => 'restaurant_accepted'])
            ->assertOk()
            ->assertJsonPath('data.status', OrderStatus::RestaurantAccepted->value)
            ->assertJsonPath('data.public_status', OrderStatus::Placed->value);

        $this->withToken($token)
            ->patchJson("/api/v1/restaurant/orders/{$order->id}/status", ['status' => 'preparing'])
            ->assertOk()
            ->assertJsonPath('data.status', OrderStatus::Preparing->value);

        $this->withToken($token)
            ->patchJson("/api/v1/restaurant/orders/{$order->id}/status", ['status' => 'ready_for_pickup'])
            ->assertOk()
            ->assertJsonPath('data.status', OrderStatus::ReadyForPickup->value)
            ->assertJsonPath('data.public_status', OrderStatus::Placed->value);
    }

    public function test_out_of_sequence_transition_returns_conflict(): void
    {
        $owner = User::factory()->create(['role' => UserRole::Restaurant]);
        $restaurant = Restaurant::factory()->create();
        $owner->restaurants()->attach($restaurant, ['membership_role' => 'owner']);
        $order = Order::factory()->for($restaurant, 'restaurant')->create([
            'status' => OrderStatus::Placed,
        ]);

        $this->withToken($owner->createToken('restaurant')->plainTextToken)
            ->patchJson("/api/v1/restaurant/orders/{$order->id}/status", ['status' => 'preparing'])
            ->assertConflict();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Placed->value,
        ]);
    }

    public function test_non_member_cannot_advance_another_restaurants_order(): void
    {
        $stranger = User::factory()->create(['role' => UserRole::Restaurant]);
        $restaurant = Restaurant::factory()->create();
        $order = Order::factory()->for($restaurant, 'restaurant')->create([
            'status' => OrderStatus::Placed,
        ]);

        $this->withToken($stranger->createToken('restaurant')->plainTextToken)
            ->patchJson("/api/v1/restaurant/orders/{$order->id}/status", ['status' => 'restaurant_accepted'])
            ->assertForbidden();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Placed->value,
        ]);
    }
}
