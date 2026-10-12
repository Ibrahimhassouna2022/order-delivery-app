<?php

namespace Tests\Feature\Api;

use App\Actions\Driver\CreateDriverOfferAction;
use App\Actions\Driver\RespondToDriverOfferAction;
use App\Actions\Driver\TransitionDriverAssignmentAction;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\DriverProfile;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverCompletedDeliveriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_deliveries_count_increments_once_on_confirmed_delivery(): void
    {
        $driver = User::factory()->create(['role' => UserRole::Driver]);
        DriverProfile::factory()->for($driver)->create([
            'is_available' => true,
            'completed_deliveries_count' => 3,
        ]);
        $order = Order::factory()->create([
            'status' => OrderStatus::ReadyForPickup,
            'currency_code' => 'ILS',
            'delivery_fee' => '6.500',
        ]);

        ['assignment' => $assignment] = app(CreateDriverOfferAction::class)->execute($order, [
            'driver_user_id' => $driver->id,
            'distance_meters' => 2300,
            'fare' => '6.500',
        ]);

        app(RespondToDriverOfferAction::class)->execute($driver, $assignment, true);

        $transition = app(TransitionDriverAssignmentAction::class);
        $transition->pickup($driver, $assignment->refresh());
        $transition->deliver($driver, $assignment->refresh());
        $transition->deliver($driver, $assignment->refresh());

        $this->assertSame(4, $driver->driverProfile->refresh()->completed_deliveries_count);
    }
}
