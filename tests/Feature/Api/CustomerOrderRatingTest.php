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

/**
 * Post-delivery ratings (FR-013): score 1–5, one per order, only after delivery.
 */
class CustomerOrderRatingTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_rates_the_restaurant_and_driver_after_delivery(): void
    {
        ['customer' => $customer, 'order' => $order, 'driver' => $driver] = $this->deliveredOrder();
        $token = $customer->createToken('customer')->plainTextToken;

        $this->withToken($token)
            ->postJson("/api/v1/orders/{$order->id}/restaurant-rating", [
                'score' => 5,
                'comment' => 'Excellent hummus.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.activity_type', 'restaurant_rating');

        $this->withToken($token)
            ->postJson("/api/v1/orders/{$order->id}/driver-rating", [
                'score' => 4,
            ])
            ->assertCreated()
            ->assertJsonPath('data.activity_type', 'driver_rating');

        $this->assertDatabaseHas('restaurant_ratings', [
            'customer_id' => $customer->id,
            'order_id' => $order->id,
            'restaurant_id' => $order->restaurant_id,
            'score' => 5,
        ]);
        $this->assertDatabaseHas('driver_ratings', [
            'customer_id' => $customer->id,
            'order_id' => $order->id,
            'driver_user_id' => $driver->id,
            'score' => 4,
        ]);
    }

    public function test_rating_requires_a_score_between_1_and_5(): void
    {
        ['customer' => $customer, 'order' => $order] = $this->deliveredOrder();

        $this->withToken($customer->createToken('customer')->plainTextToken)
            ->postJson("/api/v1/orders/{$order->id}/restaurant-rating", ['score' => 6])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('score');
    }

    public function test_an_order_can_only_be_rated_once(): void
    {
        ['customer' => $customer, 'order' => $order] = $this->deliveredOrder();
        $token = $customer->createToken('customer')->plainTextToken;

        $this->withToken($token)
            ->postJson("/api/v1/orders/{$order->id}/restaurant-rating", ['score' => 4])
            ->assertCreated();

        $this->withToken($token)
            ->postJson("/api/v1/orders/{$order->id}/restaurant-rating", ['score' => 1])
            ->assertConflict();
    }

    public function test_undelivered_orders_cannot_be_rated(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);
        $order = Order::factory()->for($customer, 'customer')->create();

        $this->withToken($customer->createToken('customer')->plainTextToken)
            ->postJson("/api/v1/orders/{$order->id}/restaurant-rating", ['score' => 5])
            ->assertForbidden();
    }

    /**
     * @return array{customer: User, order: Order, driver: User}
     */
    private function deliveredOrder(): array
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);
        $driver = User::factory()->create(['role' => UserRole::Driver]);
        DriverProfile::factory()->for($driver)->create(['is_available' => true]);
        $order = Order::factory()->for($customer, 'customer')->create([
            'status' => OrderStatus::ReadyForPickup,
        ]);

        ['assignment' => $assignment] = app(CreateDriverOfferAction::class)->execute($order, [
            'driver_user_id' => $driver->id,
            'distance_meters' => 1800,
            'fare' => '6.500',
        ]);

        app(RespondToDriverOfferAction::class)->execute($driver, $assignment, true);

        $transition = app(TransitionDriverAssignmentAction::class);
        $transition->pickup($driver, $assignment->refresh());
        $transition->deliver($driver, $assignment->refresh());

        return [
            'customer' => $customer,
            'order' => $order->refresh(),
            'driver' => $driver,
        ];
    }
}
