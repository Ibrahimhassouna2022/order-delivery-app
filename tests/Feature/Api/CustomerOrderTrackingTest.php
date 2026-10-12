<?php

namespace Tests\Feature\Api;

use App\Enums\DriverAssignmentStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\DriverProfile;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tracking read + public status projection (FR-012, research R-9).
 */
class CustomerOrderTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_internal_restaurant_statuses_project_to_placed_for_the_customer(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);
        $order = Order::factory()->for($customer, 'customer')->create([
            'status' => OrderStatus::Preparing,
        ]);

        $this->withToken($customer->createToken('customer')->plainTextToken)
            ->getJson("/api/v1/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('data.order_id', $order->id)
            ->assertJsonPath('data.status', OrderStatus::Placed->value)
            ->assertJsonPath('data.restaurant.name', $order->restaurant->name)
            ->assertJsonPath('data.driver', null);
    }

    public function test_assigned_order_exposes_the_active_driver_with_latest_coordinates(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);
        $driver = User::factory()->create(['role' => UserRole::Driver]);
        DriverProfile::factory()->for($driver)->create(['is_available' => true]);
        $order = Order::factory()->for($customer, 'customer')->create([
            'status' => OrderStatus::Assigned,
        ]);
        $order->driverAssignments()->create([
            'driver_user_id' => $driver->id,
            'status' => DriverAssignmentStatus::Accepted,
            'distance_meters' => 1000,
            'fare' => '6.500',
            'currency_code' => 'ILS',
            'offered_at' => now(),
            'expires_at' => now()->addSeconds(30),
            'responded_at' => now(),
        ]);
        $driverLocation = $driver->driverLocation()->create([
            'latitude' => 31.7679,
            'longitude' => 35.2155,
            'recorded_at' => now(),
        ]);

        $this->withToken($customer->createToken('customer')->plainTextToken)
            ->getJson("/api/v1/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('data.status', OrderStatus::Assigned->value)
            ->assertJsonPath('data.driver.id', $driver->id)
            ->assertJsonPath('data.driver.latitude', $driverLocation->latitude)
            ->assertJsonPath('data.driver.longitude', $driverLocation->longitude)
            ->assertJsonPath('data.delivery_location.latitude', $order->deliveryLocation->latitude);
    }

    public function test_customer_cannot_track_an_order_they_do_not_own(): void
    {
        $owner = User::factory()->create(['role' => UserRole::Customer]);
        $stranger = User::factory()->create(['role' => UserRole::Customer]);
        $order = Order::factory()->for($owner, 'customer')->create();

        $this->withToken($stranger->createToken('stranger')->plainTextToken)
            ->getJson("/api/v1/orders/{$order->id}")
            ->assertForbidden();
    }
}
