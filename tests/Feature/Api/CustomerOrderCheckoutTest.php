<?php

namespace Tests\Feature\Api;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\RestaurantStatus;
use App\Enums\UserRole;
use App\Models\DeliveryLocation;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerOrderCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_review_a_server_calculated_cod_quote(): void
    {
        config(['delivery.pricing.fixed_fees' => ['ILS' => '4.500']]);
        $context = $this->createCustomerOrderContext(['price' => '12.250']);

        $this->withToken($context['token'])
            ->postJson('/api/v1/orders/quote', $this->orderPayload($context, 2))
            ->assertOk()
            ->assertJsonPath('data.items_subtotal', '24.500')
            ->assertJsonPath('data.delivery_fee', '4.500')
            ->assertJsonPath('data.total', '29.000')
            ->assertJsonPath('data.currency_code', 'ILS')
            ->assertJsonPath('data.payment_method', 'cash_on_delivery')
            ->assertJsonPath('data.items.0.item_name', $context['menuItem']->name)
            ->assertJsonPath('data.items.0.quantity', 2);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_quote_is_unavailable_until_a_delivery_fee_is_configured_for_the_currency(): void
    {
        config(['delivery.pricing.fixed_fees' => []]);
        $context = $this->createCustomerOrderContext();

        $this->withToken($context['token'])
            ->postJson('/api/v1/orders/quote', $this->orderPayload($context))
            ->assertServiceUnavailable()
            ->assertJsonPath('message', 'Order pricing is not configured.');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_persists_price_snapshots_and_replays_idempotently(): void
    {
        config(['delivery.pricing.fixed_fees' => ['ILS' => '4.500']]);
        $context = $this->createCustomerOrderContext(['price' => '12.250']);
        $payload = $this->orderPayload($context, 2) + ['payment_method' => 'card'];
        $headers = ['Idempotency-Key' => 'checkout-1'];

        $firstResponse = $this->withHeaders($headers)
            ->withToken($context['token'])
            ->postJson('/api/v1/orders', $payload);

        $firstResponse->assertCreated()
            ->assertJsonPath('data.status', OrderStatus::Placed->value)
            ->assertJsonPath('data.public_status', OrderStatus::Placed->value)
            ->assertJsonPath('data.items_subtotal', '24.500')
            ->assertJsonPath('data.delivery_fee', '4.500')
            ->assertJsonPath('data.total', '29.000')
            ->assertJsonPath('data.payment_method', PaymentMethod::CashOnDelivery->value)
            ->assertJsonPath('data.items.0.item_name', $context['menuItem']->name)
            ->assertJsonPath('data.items.0.unit_price', '12.250')
            ->assertJsonPath('data.items.0.line_total', '24.500');

        $orderId = $firstResponse->json('data.id');
        $order = Order::with('items')->findOrFail($orderId);
        $this->assertModelExists($order);
        $this->assertSame($context['customer']->id, $order->customer_id);
        $this->assertSame('29.000', $order->total);
        $this->assertCount(1, $order->items);
        $this->assertDatabaseHas('payment_attempts', [
            'order_id' => $order->id,
            'method' => PaymentMethod::CashOnDelivery->value,
            'status' => PaymentStatus::Pending->value,
            'amount' => '29.000',
        ]);

        $this->withHeaders($headers)
            ->withToken($context['token'])
            ->postJson('/api/v1/orders', $payload)
            ->assertOk()
            ->assertJsonPath('data.id', $orderId);

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);
        $this->assertDatabaseCount('payment_attempts', 1);
    }

    public function test_checkout_rejects_reusing_an_idempotency_key_for_a_different_cart(): void
    {
        config(['delivery.pricing.fixed_fees' => ['ILS' => '4.500']]);
        $context = $this->createCustomerOrderContext();
        $headers = ['Idempotency-Key' => 'reused-checkout-key'];

        $this->withHeaders($headers)
            ->withToken($context['token'])
            ->postJson('/api/v1/orders', $this->orderPayload($context, 1))
            ->assertCreated();

        $this->withHeaders($headers)
            ->withToken($context['token'])
            ->postJson('/api/v1/orders', $this->orderPayload($context, 2))
            ->assertConflict();

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);
    }

    public function test_idempotent_retry_returns_the_saved_order_after_catalog_changes(): void
    {
        config(['delivery.pricing.fixed_fees' => ['ILS' => '4.500']]);
        $context = $this->createCustomerOrderContext();
        $headers = ['Idempotency-Key' => 'catalog-changed-retry'];
        $payload = $this->orderPayload($context);

        $firstResponse = $this->withHeaders($headers)
            ->withToken($context['token'])
            ->postJson('/api/v1/orders', $payload);
        $firstResponse->assertCreated();

        $context['restaurant']->update(['status' => RestaurantStatus::Pending]);
        $context['menuItem']->delete();

        $this->withHeaders($headers)
            ->withToken($context['token'])
            ->postJson('/api/v1/orders', $payload)
            ->assertOk()
            ->assertJsonPath('data.id', $firstResponse->json('data.id'));

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);
    }

    public function test_checkout_rejects_unavailable_menu_items_without_creating_an_order(): void
    {
        config(['delivery.pricing.fixed_fees' => ['ILS' => '4.500']]);
        $context = $this->createCustomerOrderContext(['is_available' => false]);

        $this->withHeaders(['Idempotency-Key' => 'unavailable-item'])
            ->withToken($context['token'])
            ->postJson('/api/v1/orders', $this->orderPayload($context))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('payment_attempts', 0);
    }

    public function test_quote_rejects_a_restaurant_that_is_not_active(): void
    {
        config(['delivery.pricing.fixed_fees' => ['ILS' => '4.500']]);
        $context = $this->createCustomerOrderContext();
        $context['restaurant']->update(['status' => RestaurantStatus::Pending]);

        $this->withToken($context['token'])
            ->postJson('/api/v1/orders/quote', $this->orderPayload($context))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('restaurant_id');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_rejects_items_from_a_different_restaurant(): void
    {
        config(['delivery.pricing.fixed_fees' => ['ILS' => '4.500']]);
        $context = $this->createCustomerOrderContext();
        $otherRestaurant = Restaurant::factory()->create(['status' => RestaurantStatus::Active]);
        $otherItem = MenuItem::factory()->for($otherRestaurant)->create();
        $payload = $this->orderPayload($context);
        $payload['items'][0]['menu_item_id'] = $otherItem->id;

        $this->withToken($context['token'])
            ->postJson('/api/v1/orders/quote', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_quote_rejects_menu_items_with_different_currencies(): void
    {
        config(['delivery.pricing.fixed_fees' => ['ILS' => '4.500', 'USD' => '1.500']]);
        $context = $this->createCustomerOrderContext();
        $usdItem = MenuItem::factory()
            ->for($context['restaurant'])
            ->create(['currency_code' => 'USD']);
        $payload = $this->orderPayload($context);
        $payload['items'][] = [
            'menu_item_id' => $usdItem->id,
            'quantity' => 1,
        ];

        $this->withToken($context['token'])
            ->postJson('/api/v1/orders/quote', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_rejects_a_delivery_location_owned_by_another_customer(): void
    {
        config(['delivery.pricing.fixed_fees' => ['ILS' => '4.500']]);
        $context = $this->createCustomerOrderContext();
        $otherLocation = DeliveryLocation::factory()->create();
        $payload = $this->orderPayload($context);
        $payload['delivery_location_id'] = $otherLocation->id;

        $this->withHeaders(['Idempotency-Key' => 'foreign-location'])
            ->withToken($context['token'])
            ->postJson('/api/v1/orders', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('delivery_location_id');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_requires_customer_authentication_and_an_idempotency_key(): void
    {
        $context = $this->createCustomerOrderContext();
        $payload = $this->orderPayload($context);

        $this->postJson('/api/v1/orders', $payload)->assertUnauthorized();

        $this->withToken($context['token'])
            ->postJson('/api/v1/orders', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('idempotency_key');
    }

    /**
     * @param  array<string, mixed>  $menuItemAttributes
     * @return array{
     *   customer: User,
     *   token: string,
     *   restaurant: Restaurant,
     *   menuItem: MenuItem,
     *   location: DeliveryLocation
     * }
     */
    private function createCustomerOrderContext(array $menuItemAttributes = []): array
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);
        $restaurant = Restaurant::factory()->create(['status' => RestaurantStatus::Active]);
        $menuItem = MenuItem::factory()
            ->for($restaurant)
            ->create($menuItemAttributes);
        $location = DeliveryLocation::factory()->for($customer)->create();

        return [
            'customer' => $customer,
            'token' => $customer->createToken('customer')->plainTextToken,
            'restaurant' => $restaurant,
            'menuItem' => $menuItem,
            'location' => $location,
        ];
    }

    /**
     * @param  array{
     *   restaurant: Restaurant,
     *   location: DeliveryLocation,
     *   menuItem: MenuItem
     * }  $context
     * @return array{
     *   restaurant_id: int,
     *   delivery_location_id: int,
     *   items: list<array{menu_item_id: int, quantity: int}>
     * }
     */
    private function orderPayload(array $context, int $quantity = 1): array
    {
        return [
            'restaurant_id' => $context['restaurant']->id,
            'delivery_location_id' => $context['location']->id,
            'items' => [
                [
                    'menu_item_id' => $context['menuItem']->id,
                    'quantity' => $quantity,
                ],
            ],
        ];
    }
}
