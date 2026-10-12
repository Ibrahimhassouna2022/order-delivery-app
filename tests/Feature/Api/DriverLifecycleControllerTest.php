<?php

namespace Tests\Feature\Api;

use App\Enums\DriverAssignmentStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Enums\WalletEntryType;
use App\Models\DriverAssignment;
use App\Models\DriverProfile;
use App\Models\DriverWalletLedgerEntry;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverLifecycleControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_offer_a_ready_order_to_an_available_driver(): void
    {
        $context = $this->createDispatchContext();

        $response = $this->withToken($context['admin_token'])
            ->postJson("/api/v1/operations/orders/{$context['order']->id}/offers", [
                'driver_user_id' => $context['driver']->id,
                'distance_meters' => 2300,
                'fare' => '6.500',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.order_id', $context['order']->id)
            ->assertJsonPath('data.status', DriverAssignmentStatus::Offered->value)
            ->assertJsonPath('data.distance_meters', 2300)
            ->assertJsonPath('data.fare', '6.500')
            ->assertJsonPath('data.currency_code', 'ILS')
            ->assertJsonMissingPath('data.delivery_location');

        $this->assertDatabaseHas('driver_assignments', [
            'order_id' => $context['order']->id,
            'driver_user_id' => $context['driver']->id,
            'status' => DriverAssignmentStatus::Offered->value,
            'fare' => '6.500',
        ]);
    }

    public function test_repeating_the_same_offer_request_does_not_create_a_second_assignment(): void
    {
        $context = $this->createDispatchContext();
        $payload = [
            'driver_user_id' => $context['driver']->id,
            'distance_meters' => 2300,
            'fare' => '6.500',
        ];
        $url = "/api/v1/operations/orders/{$context['order']->id}/offers";

        $this->withToken($context['admin_token'])->postJson($url, $payload)->assertCreated();
        $this->withToken($context['admin_token'])->postJson($url, $payload)->assertOk();

        $this->assertDatabaseCount('driver_assignments', 1);
    }

    public function test_driver_only_sees_their_offers_and_the_offer_hides_customer_location(): void
    {
        $context = $this->createDispatchContext();
        $ownAssignment = $this->createOffer($context);
        $otherContext = $this->createDispatchContext();
        $this->createOffer($otherContext);

        $this->app['auth']->forgetGuards();
        $this->withToken($context['driver_token'])
            ->getJson('/api/v1/driver/offers')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ownAssignment->id)
            ->assertJsonPath('data.0.fare', '6.500')
            ->assertJsonMissingPath('data.0.delivery_location');
    }

    public function test_non_admin_cannot_offer_orders_to_drivers(): void
    {
        $context = $this->createDispatchContext();
        $customer = User::factory()->create(['role' => UserRole::Customer]);
        $token = $customer->createToken('customer')->plainTextToken;

        $this->withToken($token)
            ->postJson("/api/v1/operations/orders/{$context['order']->id}/offers", [
                'driver_user_id' => $context['driver']->id,
                'distance_meters' => 2300,
                'fare' => '6.500',
            ])->assertForbidden();

        $this->assertDatabaseCount('driver_assignments', 0);
    }

    public function test_admin_cannot_offer_an_order_to_an_unavailable_driver(): void
    {
        $context = $this->createDispatchContext(available: false);

        $this->withToken($context['admin_token'])
            ->postJson("/api/v1/operations/orders/{$context['order']->id}/offers", [
                'driver_user_id' => $context['driver']->id,
                'distance_meters' => 2300,
                'fare' => '6.500',
            ])->assertConflict();

        $this->assertDatabaseCount('driver_assignments', 0);
    }

    public function test_admin_cannot_offer_an_order_to_a_non_driver_account(): void
    {
        $context = $this->createDispatchContext();
        $customer = User::factory()->create(['role' => UserRole::Customer]);

        $this->withToken($context['admin_token'])
            ->postJson("/api/v1/operations/orders/{$context['order']->id}/offers", [
                'driver_user_id' => $customer->id,
                'distance_meters' => 2300,
                'fare' => '6.500',
            ])->assertUnprocessable()
            ->assertJsonValidationErrors('driver_user_id');

        $this->assertDatabaseCount('driver_assignments', 0);
    }

    public function test_driver_can_accept_an_offer_and_become_unavailable(): void
    {
        $context = $this->createDispatchContext();
        $assignment = $this->createOffer($context);

        $this->app['auth']->forgetGuards();
        $this->withToken($context['driver_token'])
            ->postJson("/api/v1/driver/offers/{$assignment->id}/accept")
            ->assertOk()
            ->assertJsonPath('data.status', DriverAssignmentStatus::Accepted->value)
            ->assertJsonPath('data.delivery_location.address', $context['order']->deliveryLocation->address);

        $this->assertDatabaseHas('driver_assignments', [
            'id' => $assignment->id,
            'status' => DriverAssignmentStatus::Accepted->value,
        ]);
        $this->assertDatabaseHas('orders', [
            'id' => $context['order']->id,
            'status' => OrderStatus::Assigned->value,
        ]);
        $this->assertDatabaseHas('driver_profiles', [
            'user_id' => $context['driver']->id,
            'is_available' => false,
        ]);
    }

    public function test_driver_cannot_access_another_drivers_offer(): void
    {
        $context = $this->createDispatchContext();
        $assignment = $this->createOffer($context);
        $otherDriver = User::factory()->create(['role' => UserRole::Driver]);
        $otherToken = $otherDriver->createToken('driver')->plainTextToken;

        $this->app['auth']->forgetGuards();
        $this->withToken($otherToken)
            ->postJson("/api/v1/driver/offers/{$assignment->id}/accept")
            ->assertNotFound();

        $this->assertDatabaseHas('driver_assignments', [
            'id' => $assignment->id,
            'status' => DriverAssignmentStatus::Offered->value,
        ]);
    }

    public function test_rejected_offer_does_not_advance_the_order_or_credit_the_wallet(): void
    {
        $context = $this->createDispatchContext();
        $assignment = $this->createOffer($context);

        $this->app['auth']->forgetGuards();
        $this->withToken($context['driver_token'])
            ->postJson("/api/v1/driver/offers/{$assignment->id}/reject")
            ->assertOk()
            ->assertJsonPath('data.status', DriverAssignmentStatus::Rejected->value);

        $this->withToken($context['driver_token'])
            ->postJson("/api/v1/driver/assignments/{$assignment->id}/pickup")
            ->assertConflict();

        $this->assertDatabaseHas('orders', [
            'id' => $context['order']->id,
            'status' => OrderStatus::ReadyForPickup->value,
        ]);
        $this->assertDatabaseCount('driver_wallet_ledger_entries', 0);
    }

    public function test_admin_can_expire_a_pending_offer_without_advancing_the_order(): void
    {
        $context = $this->createDispatchContext();
        $assignment = $this->createOffer($context);

        $this->withToken($context['admin_token'])
            ->postJson("/api/v1/operations/driver-assignments/{$assignment->id}/expire")
            ->assertOk()
            ->assertJsonPath('data.status', DriverAssignmentStatus::Expired->value);

        $this->assertDatabaseHas('orders', [
            'id' => $context['order']->id,
            'status' => OrderStatus::ReadyForPickup->value,
        ]);
        $this->assertDatabaseHas('driver_profiles', [
            'user_id' => $context['driver']->id,
            'is_available' => true,
        ]);
    }

    public function test_driver_cannot_deliver_before_pickup(): void
    {
        $context = $this->createDispatchContext();
        $assignment = $this->createOffer($context);

        $this->app['auth']->forgetGuards();
        $this->withToken($context['driver_token'])
            ->postJson("/api/v1/driver/offers/{$assignment->id}/accept")
            ->assertOk();

        $this->withToken($context['driver_token'])
            ->postJson("/api/v1/driver/assignments/{$assignment->id}/deliver")
            ->assertConflict();

        $this->assertDatabaseCount('driver_wallet_ledger_entries', 0);
        $this->assertDatabaseHas('orders', [
            'id' => $context['order']->id,
            'status' => OrderStatus::Assigned->value,
        ]);
    }

    public function test_pickup_and_delivery_are_ordered_and_delivery_credit_is_exactly_once(): void
    {
        $context = $this->createDispatchContext();
        $assignment = $this->createOffer($context);

        $this->app['auth']->forgetGuards();
        $this->withToken($context['driver_token'])
            ->postJson("/api/v1/driver/offers/{$assignment->id}/accept")
            ->assertOk();
        $this->withToken($context['driver_token'])
            ->postJson("/api/v1/driver/assignments/{$assignment->id}/pickup")
            ->assertOk()
            ->assertJsonPath('data.status', DriverAssignmentStatus::PickedUp->value);

        $this->withToken($context['driver_token'])
            ->postJson("/api/v1/driver/assignments/{$assignment->id}/deliver")
            ->assertOk()
            ->assertJsonPath('data.status', DriverAssignmentStatus::Delivered->value);
        $this->withToken($context['driver_token'])
            ->postJson("/api/v1/driver/assignments/{$assignment->id}/deliver")
            ->assertOk()
            ->assertJsonPath('data.status', DriverAssignmentStatus::Delivered->value);

        $this->assertDatabaseHas('orders', [
            'id' => $context['order']->id,
            'status' => OrderStatus::Delivered->value,
        ]);
        $this->assertDatabaseHas('driver_wallet_ledger_entries', [
            'driver_user_id' => $context['driver']->id,
            'delivery_credit_order_id' => $context['order']->id,
            'entry_type' => WalletEntryType::DeliveryCredit->value,
            'amount' => '6.500',
            'currency_code' => 'ILS',
        ]);
        $this->assertDatabaseCount('driver_wallet_ledger_entries', 1);
    }

    public function test_driver_wallet_only_reports_the_authenticated_drivers_ledger(): void
    {
        $context = $this->createDispatchContext();
        $assignment = $this->createOffer($context);
        $this->completeDelivery($context, $assignment);

        $otherDriver = User::factory()->create(['role' => UserRole::Driver]);
        DriverWalletLedgerEntry::factory()->for($otherDriver, 'driver')->create([
            'amount' => '99.000',
            'currency_code' => 'USD',
        ]);

        $this->withToken($context['driver_token'])
            ->getJson('/api/v1/driver/wallet')
            ->assertOk()
            ->assertJsonPath('data.delivery_credits.0.currency_code', 'ILS')
            ->assertJsonPath('data.delivery_credits.0.amount', '6.500')
            ->assertJsonCount(1, 'data.entries');
    }

    public function test_driver_can_set_availability_but_other_roles_cannot(): void
    {
        $context = $this->createDispatchContext(available: false);

        $this->withToken($context['driver_token'])
            ->putJson('/api/v1/driver/availability', ['is_available' => true])
            ->assertOk()
            ->assertJsonPath('data.is_available', true);

        $customer = User::factory()->create(['role' => UserRole::Customer]);
        $this->app['auth']->forgetGuards();
        $this->withToken($customer->createToken('customer')->plainTextToken)
            ->putJson('/api/v1/driver/availability', ['is_available' => true])
            ->assertForbidden();

        $this->assertDatabaseHas('driver_profiles', [
            'user_id' => $context['driver']->id,
            'is_available' => true,
        ]);
    }

    public function test_driver_can_repeat_the_current_availability_while_offer_is_pending(): void
    {
        $context = $this->createDispatchContext();
        $this->createOffer($context);

        $this->app['auth']->forgetGuards();
        $this->withToken($context['driver_token'])
            ->putJson('/api/v1/driver/availability', ['is_available' => true])
            ->assertOk()
            ->assertJsonPath('data.is_available', true);
    }

    public function test_driver_cannot_return_to_available_while_holding_an_active_assignment(): void
    {
        $context = $this->createDispatchContext();
        $assignment = $this->createOffer($context);

        $this->app['auth']->forgetGuards();
        $this->withToken($context['driver_token'])
            ->postJson("/api/v1/driver/offers/{$assignment->id}/accept")
            ->assertOk();

        $this->withToken($context['driver_token'])
            ->putJson('/api/v1/driver/availability', ['is_available' => true])
            ->assertConflict();

        $this->assertDatabaseHas('driver_profiles', [
            'user_id' => $context['driver']->id,
            'is_available' => false,
        ]);
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
    private function createDispatchContext(bool $available = true): array
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $driver = User::factory()->create(['role' => UserRole::Driver]);
        DriverProfile::factory()->for($driver)->create(['is_available' => $available]);
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

    /**
     * @param  array{driver_token: string}  $context
     */
    private function completeDelivery(array $context, DriverAssignment $assignment): void
    {
        $this->app['auth']->forgetGuards();
        $this->withToken($context['driver_token'])
            ->postJson("/api/v1/driver/offers/{$assignment->id}/accept")
            ->assertOk();
        $this->withToken($context['driver_token'])
            ->postJson("/api/v1/driver/assignments/{$assignment->id}/pickup")
            ->assertOk();
        $this->withToken($context['driver_token'])
            ->postJson("/api/v1/driver/assignments/{$assignment->id}/deliver")
            ->assertOk();
    }
}
