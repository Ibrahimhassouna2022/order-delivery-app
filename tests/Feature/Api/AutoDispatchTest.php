<?php

namespace Tests\Feature\Api;

use App\Actions\Driver\FindLeastLoadedAvailableDriverAction;
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

    public function test_available_driver_with_fewest_completed_deliveries_is_selected(): void
    {
        $this->createAvailableDriver(completedDeliveries: 12);
        $leastLoaded = $this->createAvailableDriver(completedDeliveries: 2);

        $result = app(FindLeastLoadedAvailableDriverAction::class)->execute();

        $this->assertNotNull($result);
        $this->assertSame($leastLoaded->id, $result['driver']->id);
    }

    public function test_stale_driver_locations_do_not_affect_selection(): void
    {
        $stale = $this->createAvailableDriver(completedDeliveries: 1);
        $stale->driverLocation->update(['recorded_at' => now()->subMinutes(5)]);

        $this->createAvailableDriver(completedDeliveries: 6);

        $result = app(FindLeastLoadedAvailableDriverAction::class)->execute();

        $this->assertNotNull($result);
        $this->assertSame($stale->id, $result['driver']->id);
    }

    public function test_driver_location_is_not_required(): void
    {
        $driverWithoutLocation = $this->createAvailableDriver(
            completedDeliveries: 1,
            withLocation: false,
        );

        $result = app(FindLeastLoadedAvailableDriverAction::class)->execute();

        $this->assertNotNull($result);
        $this->assertSame($driverWithoutLocation->id, $result['driver']->id);
    }

    public function test_busy_drivers_are_not_candidates(): void
    {
        $busy = $this->createAvailableDriver(completedDeliveries: 1);

        $order = Order::factory()->create(['status' => OrderStatus::ReadyForPickup]);
        DriverAssignment::factory()->for($order)->for($busy, 'driver')->create([
            'status' => DriverAssignmentStatus::Offered,
        ]);

        $result = app(FindLeastLoadedAvailableDriverAction::class)->execute();

        $this->assertNull($result);
    }

    private function createAvailableDriver(
        int $completedDeliveries = 0,
        bool $withLocation = true,
    ): User {
        $driver = User::factory()->create(['role' => UserRole::Driver]);

        DriverProfile::factory()->for($driver)->create([
            'is_available' => true,
            'availability_updated_at' => now(),
            'completed_deliveries_count' => $completedDeliveries,
        ]);

        if ($withLocation) {
            DriverLocation::factory()->for($driver, 'driver')->create([
                'latitude' => 31.9000,
                'longitude' => 35.2000,
                'recorded_at' => now(),
            ]);
        }

        return $driver;
    }
}
