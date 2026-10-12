<?php

namespace Tests\Feature\Api;

use App\Actions\Driver\DispatchClosestDriverAction;
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

        $near = $this->createAvailableDriverAt(31.9005, 35.2005);
        $far = $this->createAvailableDriverAt(31.9500, 35.2500);

        $assignment = app(DispatchClosestDriverAction::class)->execute($order);

        $this->assertNotNull($assignment);
        $this->assertSame($near->id, $assignment->driver_user_id);
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
            'driver_user_id' => $far->id,
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
        $this->createAvailableDriverAt(31.9005, 35.2005);

        $assignment = app(DispatchClosestDriverAction::class)->execute($order);

        // The sync queue runs the job immediately; it must no-op until due.
        $this->assertDatabaseHas('driver_assignments', [
            'id' => $assignment->id,
            'status' => DriverAssignmentStatus::Offered->value,
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
