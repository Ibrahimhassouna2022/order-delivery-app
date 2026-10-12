<?php

namespace Tests\Unit;

use App\Enums\OrderStatus;
use PHPUnit\Framework\TestCase;

class OrderStatusPublicStatusTest extends TestCase
{
    public function test_restaurant_side_sub_states_collapse_to_placed(): void
    {
        $this->assertSame(OrderStatus::Placed, OrderStatus::Placed->publicStatus());
        $this->assertSame(OrderStatus::Placed, OrderStatus::RestaurantAccepted->publicStatus());
        $this->assertSame(OrderStatus::Placed, OrderStatus::Preparing->publicStatus());
        $this->assertSame(OrderStatus::Placed, OrderStatus::ReadyForPickup->publicStatus());
    }

    public function test_delivery_lifecycle_statuses_map_to_themselves(): void
    {
        $this->assertSame(OrderStatus::Assigned, OrderStatus::Assigned->publicStatus());
        $this->assertSame(OrderStatus::PickedUp, OrderStatus::PickedUp->publicStatus());
        $this->assertSame(OrderStatus::Delivered, OrderStatus::Delivered->publicStatus());
        $this->assertSame(OrderStatus::Cancelled, OrderStatus::Cancelled->publicStatus());
    }

    public function test_every_status_has_a_public_projection(): void
    {
        $publicStatuses = [
            OrderStatus::Placed,
            OrderStatus::Assigned,
            OrderStatus::PickedUp,
            OrderStatus::Delivered,
            OrderStatus::Cancelled,
        ];

        foreach (OrderStatus::cases() as $status) {
            $this->assertContains($status->publicStatus(), $publicStatuses);
        }
    }
}
