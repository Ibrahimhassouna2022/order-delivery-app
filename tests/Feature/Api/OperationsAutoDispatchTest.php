<?php

namespace Tests\Feature\Api;

use App\Enums\DriverAssignmentStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\DriverLocation;
use App\Models\DriverProfile;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationsAutoDispatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_auto_dispatch_an_order_to_the_closest_driver(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $near = $this->createAvailableDriverAt(31.9005, 35.2005);
        $this->createAvailableDriverAt(31.9500, 35.2500);

        $restaurant = Restaurant::factory()->create(['latitude' => 31.9000, 'longitude' => 35.2000]);
        $order = Order::factory()->create([
            'restaurant_id' => $restaurant->id,
            'status' => OrderStatus::ReadyForPickup,
            'currency_code' => 'ILS',
            'delivery_fee' => '6.500',
        ]);

        $this->withToken($admin->createToken('admin')->plainTextToken)
            ->postJson("/api/v1/operations/orders/{$order->id}/dispatch")
            ->assertCreated()
            ->assertJsonPath('data.order_id', $order->id)
            ->assertJsonPath('data.status', DriverAssignmentStatus::Offered->value);

        $this->assertDatabaseHas('driver_assignments', [
            'order_id' => $order->id,
            'driver_user_id' => $near->id,
            'status' => DriverAssignmentStatus::Offered->value,
        ]);
    }

    public function test_auto_dispatch_returns_conflict_when_no_driver_is_available(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $restaurant = Restaurant::factory()->create(['latitude' => 31.9000, 'longitude' => 35.2000]);
        $order = Order::factory()->create([
            'restaurant_id' => $restaurant->id,
            'status' => OrderStatus::ReadyForPickup,
        ]);

        $this->withToken($admin->createToken('admin')->plainTextToken)
            ->postJson("/api/v1/operations/orders/{$order->id}/dispatch")
            ->assertConflict();

        $this->assertDatabaseCount('driver_assignments', 0);
    }

    public function test_non_admin_cannot_auto_dispatch(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);
        $restaurant = Restaurant::factory()->create(['latitude' => 31.9000, 'longitude' => 35.2000]);
        $order = Order::factory()->create([
            'restaurant_id' => $restaurant->id,
            'status' => OrderStatus::ReadyForPickup,
        ]);

        $this->withToken($customer->createToken('customer')->plainTextToken)
            ->postJson("/api/v1/operations/orders/{$order->id}/dispatch")
            ->assertForbidden();
    }

    public function test_excluded_drivers_are_not_selected(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $near = $this->createAvailableDriverAt(31.9005, 35.2005);
        $far = $this->createAvailableDriverAt(31.9500, 35.2500);

        $restaurant = Restaurant::factory()->create(['latitude' => 31.9000, 'longitude' => 35.2000]);
        $order = Order::factory()->create([
            'restaurant_id' => $restaurant->id,
            'status' => OrderStatus::ReadyForPickup,
            'currency_code' => 'ILS',
            'delivery_fee' => '6.500',
        ]);

        $this->withToken($admin->createToken('admin')->plainTextToken)
            ->postJson("/api/v1/operations/orders/{$order->id}/dispatch", [
                'exclude_driver_user_ids' => [$near->id],
            ])
            ->assertCreated();

        $this->assertDatabaseHas('driver_assignments', [
            'order_id' => $order->id,
            'driver_user_id' => $far->id,
        ]);
    }

    private function createAvailableDriverAt(float $latitude, float $longitude): User
    {
        $driver = User::factory()->create(['role' => UserRole::Driver]);

        DriverProfile::factory()->for($driver)->create([
            'is_available' => true,
            'availability_updated_at' => now(),
        ]);

        DriverLocation::factory()->for($driver, 'driver')->create([
            'latitude' => $latitude,
            'longitude' => $longitude,
            'recorded_at' => now(),
        ]);

        return $driver;
    }
}
