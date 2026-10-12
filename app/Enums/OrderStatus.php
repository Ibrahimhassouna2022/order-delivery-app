<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Placed = 'placed';
    case RestaurantAccepted = 'restaurant_accepted';
    case Preparing = 'preparing';
    case ReadyForPickup = 'ready_for_pickup';
    case Assigned = 'assigned';
    case PickedUp = 'picked_up';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    /**
     * The subset of statuses shown to customers and drivers.
     *
     * Internal restaurant-side sub-states collapse into the customer-facing
     * lifecycle (placed -> assigned -> picked_up -> delivered, plus cancelled),
     * so the internal status set can grow without changing the public contract.
     */
    public function publicStatus(): self
    {
        return match ($this) {
            self::Placed,
            self::RestaurantAccepted,
            self::Preparing,
            self::ReadyForPickup => self::Placed,
            self::Assigned => self::Assigned,
            self::PickedUp => self::PickedUp,
            self::Delivered => self::Delivered,
            self::Cancelled => self::Cancelled,
        };
    }
}
