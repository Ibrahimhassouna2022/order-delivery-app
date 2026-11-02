<?php

namespace Tests\Feature\Api;

use App\Actions\Driver\DispatchLeastLoadedDriverAction;
use App\Enums\DriverAssignmentStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Jobs\ExpireDriverOfferJob;
use App\Models\DriverLocation;
use App\Models\DriverProfile;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OfferExpiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_offer_expires_at_the_configured_window_and_reoffers_to_the_next_driver(): void
    {
        Queue::fake();

        $restaurant = Restaurant::factory()->create(['latitude' => 31.9000, 'longitude' => 35.2000]);
        $order = Order::factory()->create([
            'restaurant_id' => $restaurant->id,
            'status' => OrderStatus::ReadyForPickup,
            'currency_code' => 'ILS',
            'delivery_fee' => '6.500',
        ]);

        $leastLoaded = $this->createAvailableDriver(completedDeliveries: 2);
        $nextDriver = $this->createAvailableDriver(completedDeliveries: 9);

        $assignment = app(DispatchLeastLoadedDriverAction::class)->execute($order);

        $this->assertNotNull($assignment);
        $this->assertSame($leastLoaded->id, $assignment->driver_user_id);
        $this->assertNotNull($assignment->expires_at);
        Queue::assertPushed(ExpireDriverOfferJob::class);

        $this->travel(31)->seconds();
        $this->artisan('delivery:expire-stale-offers')->assertSuccessful();

        $this->assertDatabaseHas('driver_assignments', [
            'id' => $assignment->id,
            'status' => DriverAssignmentStatus::Expired->value,
        ]);
        $this->assertDatabaseHas('driver_assignments', [
            'order_id' => $order->id,
            'driver_user_id' => $nextDriver->id,
            'status' => DriverAssignmentStatus::Offered->value,
        ]);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::ReadyForPickup->value,
        ]);
    }

    public function test_the_expiry_job_does_not_expire_an_offer_that_is_not_yet_due(): void
    {
        $restaurant = Restaurant::factory()->create(['latitude' => 31.9000, 'longitude' => 35.2000]);
        $order = Order::factory()->create([
            'restaurant_id' => $restaurant->id,
            'status' => OrderStatus::ReadyForPickup,
            'delivery_fee' => '6.500',
        ]);
        $this->createAvailableDriver(completedDeliveries: 1);

        $assignment = app(DispatchLeastLoadedDriverAction::class)->execute($order);

        // The sync queue runs the job immediately; it must no-op until due.
        $this->assertDatabaseHas('driver_assignments', [
            'id' => $assignment->id,
            'status' => DriverAssignmentStatus::Offered->value,
        ]);
    }

    private function createAvailableDriver(int $completedDeliveries = 0): User
    {
        $driver = User::factory()->create(['role' => UserRole::Driver]);

        DriverProfile::factory()->for($driver)->create([
            'is_available' => true,
            'availability_updated_at' => now(),
            'completed_deliveries_count' => $completedDeliveries,
        ]);

        DriverLocation::factory()->for($driver, 'driver')->create([
            'latitude' => 31.9000,
            'longitude' => 35.2000,
            'recorded_at' => now(),
        ]);

        return $driver;
    }
}
