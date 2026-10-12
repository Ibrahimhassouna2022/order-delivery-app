<?php

namespace Tests\Feature\Api;

use App\Actions\Driver\FindClosestAvailableDriverAction;
use App\Enums\DriverAssignmentStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\DriverAssignment;
use App\Models\DriverLocation;
use App\Models\DriverProfile;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutoDispatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_nearest_available_driver_is_selected(): void
    {
        $near = $this->createAvailableDriverAt(31.9005, 35.2005);
        $this->createAvailableDriverAt(31.9500, 35.2500);

        $result = app(FindClosestAvailableDriverAction::class)->execute(31.9000, 35.2000);

        $this->assertNotNull($result);
        $this->assertSame($near->id, $result['driver']->id);
        $this->assertLessThan(500, $result['distance_meters']);
    }

    public function test_ties_on_distance_are_broken_by_fewest_completed_deliveries(): void
    {
        $this->createAvailableDriverAt(31.9000, 35.2000, completedDeliveries: 12);
        $fresh = $this->createAvailableDriverAt(31.9000, 35.2000, completedDeliveries: 2);

        $result = app(FindClosestAvailableDriverAction::class)->execute(31.9000, 35.2000);

        $this->assertNotNull($result);
        $this->assertSame($fresh->id, $result['driver']->id);
    }

    public function test_stale_locations_and_busy_drivers_are_not_candidates(): void
    {
        $stale = $this->createAvailableDriverAt(31.9000, 35.2000);
        $stale->driverLocation->update(['recorded_at' => now()->subMinutes(5)]);

        $busy = $this->createAvailableDriverAt(31.9001, 35.2001);
        $order = Order::factory()->create(['status' => OrderStatus::ReadyForPickup]);
        DriverAssignment::factory()->for($order)->for($busy, 'driver')->create([
            'status' => DriverAssignmentStatus::Offered,
        ]);

        $result = app(FindClosestAvailableDriverAction::class)->execute(31.9000, 35.2000);

        $this->assertNull($result);
    }

    private function createAvailableDriverAt(
        float $latitude,
        float $longitude,
        int $completedDeliveries = 0,
    ): User {
        $driver = User::factory()->create(['role' => UserRole::Driver]);

        DriverProfile::factory()->for($driver)->create([
            'is_available' => true,
            'availability_updated_at' => now(),
            'completed_deliveries_count' => $completedDeliveries,
        ]);

        DriverLocation::factory()->for($driver, 'driver')->create([
            'latitude' => $latitude,
            'longitude' => $longitude,
            'recorded_at' => now(),
        ]);

        return $driver;
    }
}
