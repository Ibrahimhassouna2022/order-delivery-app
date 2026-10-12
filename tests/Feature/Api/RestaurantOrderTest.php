<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Restaurant order scoping (FR-023): members see only their restaurants'
 * orders, never another restaurant's.
 */
class RestaurantOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_restaurant_member_sees_only_their_restaurants_orders(): void
    {
        $owner = User::factory()->create(['role' => UserRole::Restaurant]);
        $restaurantA = Restaurant::factory()->create();
        $restaurantB = Restaurant::factory()->create();
        $owner->restaurants()->attach($restaurantA, ['membership_role' => 'owner']);

        $orderA = Order::factory()->for($restaurantA, 'restaurant')->create();
        Order::factory()->for($restaurantB, 'restaurant')->create();

        $this->withToken($owner->createToken('restaurant')->plainTextToken)
            ->getJson('/api/v1/restaurant/orders')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $orderA->id);
    }

    public function test_member_can_filter_by_one_of_their_restaurants(): void
    {
        $owner = User::factory()->create(['role' => UserRole::Restaurant]);
        $restaurantA = Restaurant::factory()->create();
        $restaurantB = Restaurant::factory()->create();
        $owner->restaurants()->attach($restaurantA, ['membership_role' => 'owner']);
        $owner->restaurants()->attach($restaurantB, ['membership_role' => 'owner']);

        $orderA = Order::factory()->for($restaurantA, 'restaurant')->create();
        Order::factory()->for($restaurantB, 'restaurant')->create();

        $this->withToken($owner->createToken('restaurant')->plainTextToken)
            ->getJson("/api/v1/restaurant/orders?restaurant_id={$restaurantA->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $orderA->id);
    }

    public function test_filtering_by_a_restaurant_outside_membership_is_forbidden(): void
    {
        $owner = User::factory()->create(['role' => UserRole::Restaurant]);
        $restaurantA = Restaurant::factory()->create();
        $restaurantB = Restaurant::factory()->create();
        $owner->restaurants()->attach($restaurantA, ['membership_role' => 'owner']);

        $this->withToken($owner->createToken('restaurant')->plainTextToken)
            ->getJson("/api/v1/restaurant/orders?restaurant_id={$restaurantB->id}")
            ->assertForbidden();
    }

    public function test_customers_have_no_access_to_the_restaurant_console(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);

        $this->withToken($customer->createToken('customer')->plainTextToken)
            ->getJson('/api/v1/restaurant/orders')
            ->assertForbidden();
    }
}
