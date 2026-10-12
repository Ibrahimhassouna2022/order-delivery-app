<?php

namespace Tests\Feature\Notifications;

use App\Actions\Notification\LogNotifier;
use App\Actions\Notification\Notifier;
use App\Actions\Notification\NullNotifier;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\DriverAssignment;
use App\Models\DriverProfile;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class NotifierTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_notifier_is_resolved_from_configuration(): void
    {
        config(['delivery.notifications.driver' => 'log']);
        $this->assertInstanceOf(LogNotifier::class, $this->app->make(Notifier::class));

        config(['delivery.notifications.driver' => 'null']);
        $this->assertInstanceOf(NullNotifier::class, $this->app->make(Notifier::class));
    }

    public function test_creating_an_offer_notifies_the_driver(): void
    {
        $context = $this->createDispatchContext();

        $this->mock(Notifier::class, function (MockInterface $mock): void {
            $mock->shouldReceive('driverOfferCreated')->once();
            $mock->shouldReceive('orderStatusChanged')->never();
        });

        $this->withToken($context['admin_token'])
            ->postJson("/api/v1/operations/orders/{$context['order']->id}/offers", [
                'driver_user_id' => $context['driver']->id,
                'distance_meters' => 2300,
                'fare' => '6.500',
            ])
            ->assertCreated();
    }

    public function test_repeating_an_offer_does_not_notify_again(): void
    {
        $context = $this->createDispatchContext();
        $payload = [
            'driver_user_id' => $context['driver']->id,
            'distance_meters' => 2300,
            'fare' => '6.500',
        ];
        $url = "/api/v1/operations/orders/{$context['order']->id}/offers";

        $this->withToken($context['admin_token'])->postJson($url, $payload)->assertCreated();

        $this->mock(Notifier::class, function (MockInterface $mock): void {
            $mock->shouldReceive('driverOfferCreated')->never();
            $mock->shouldReceive('orderStatusChanged')->never();
        });

        $this->withToken($context['admin_token'])->postJson($url, $payload)->assertOk();
    }

    public function test_completing_delivery_notifies_each_order_status_change(): void
    {
        $context = $this->createDispatchContext();
        $assignment = $this->createOffer($context);

        $this->app['auth']->forgetGuards();
        $this->withToken($context['driver_token'])
            ->postJson("/api/v1/driver/offers/{$assignment->id}/accept")
            ->assertOk();

        $this->mock(Notifier::class, function (MockInterface $mock): void {
            $mock->shouldReceive('orderStatusChanged')->twice();
            $mock->shouldReceive('driverOfferCreated')->never();
        });

        $this->withToken($context['driver_token'])
            ->postJson("/api/v1/driver/assignments/{$assignment->id}/pickup")
            ->assertOk();
        $this->withToken($context['driver_token'])
            ->postJson("/api/v1/driver/assignments/{$assignment->id}/deliver")
            ->assertOk();
    }

    /**
     * @return array{
     *   admin: User,
     *   admin_token: string,
     *   driver: User,
     *   driver_token: string,
     *   order: Order
     * }
     */
    private function createDispatchContext(): array
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $driver = User::factory()->create(['role' => UserRole::Driver]);
        DriverProfile::factory()->for($driver)->create(['is_available' => true]);
        $order = Order::factory()->create([
            'status' => OrderStatus::ReadyForPickup,
            'currency_code' => 'ILS',
            'items_subtotal' => '24.500',
            'delivery_fee' => '4.500',
            'total' => '29.000',
        ]);

        return [
            'admin' => $admin,
            'admin_token' => $admin->createToken('admin')->plainTextToken,
            'driver' => $driver,
            'driver_token' => $driver->createToken('driver')->plainTextToken,
            'order' => $order,
        ];
    }

    /**
     * @param  array{admin_token: string, driver: User, order: Order}  $context
     */
    private function createOffer(array $context): DriverAssignment
    {
        $response = $this->withToken($context['admin_token'])
            ->postJson("/api/v1/operations/orders/{$context['order']->id}/offers", [
                'driver_user_id' => $context['driver']->id,
                'distance_meters' => 2300,
                'fare' => '6.500',
            ]);

        $response->assertCreated();

        return DriverAssignment::findOrFail($response->json('data.id'));
    }
}
