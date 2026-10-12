<?php

namespace Tests\Feature\Models;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_items_keep_a_price_and_name_snapshot(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);
        $restaurant = Restaurant::create([
            'name' => 'Olive Kitchen',
            'address' => 'Ramallah',
        ]);
        $location = $customer->deliveryLocations()->create([
            'label' => 'Home',
            'address' => 'Central Ramallah',
        ]);
        $order = Order::create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'delivery_location_id' => $location->id,
            'status' => OrderStatus::Placed,
            'idempotency_key' => 'order-request-001',
            'currency_code' => 'ILS',
            'items_subtotal' => '18.500',
            'delivery_fee' => '5.000',
            'total' => '23.500',
        ]);

        $item = $order->items()->create([
            'item_name' => 'Falafel Plate',
            'unit_price' => '18.500',
            'quantity' => 1,
            'line_total' => '18.500',
        ]);

        $this->assertSame(OrderStatus::Placed, $order->fresh()->status);
        $this->assertTrue($order->items->contains($item));
        $this->assertSame('18.500', $item->fresh()->unit_price);
        $this->assertSame($location->id, $order->fresh()->deliveryLocation->id);
    }
}
