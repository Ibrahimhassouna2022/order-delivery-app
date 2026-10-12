<?php

namespace App\Actions\Restaurant;

use App\Actions\Notification\Notifier;
use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

/**
 * Advances an order through the restaurant-facing lifecycle
 * accept → prepare → ready (research R-9). These internal sub-states collapse
 * into the customer's `placed` projection via OrderStatus::publicStatus().
 */
class AdvanceRestaurantOrderStatusAction
{
    public function __construct(private Notifier $notifier) {}

    public function execute(Order $order, string $target): Order
    {
        return DB::transaction(function () use ($order, $target): Order {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->getKey());

            if (in_array($locked->status, [OrderStatus::Cancelled, OrderStatus::Delivered], true)) {
                throw new OrderStatusConflictException('This order can no longer be progressed.');
            }

            $targetStatus = OrderStatus::from($target);

            $allowedFrom = match ($targetStatus) {
                OrderStatus::RestaurantAccepted => OrderStatus::Placed,
                OrderStatus::Preparing => OrderStatus::RestaurantAccepted,
                OrderStatus::ReadyForPickup => OrderStatus::Preparing,
                default => null,
            };

            if ($allowedFrom === null || $locked->status !== $allowedFrom) {
                throw new OrderStatusConflictException('This order step is out of sequence.');
            }

            $locked->update(['status' => $targetStatus]);

            // Broadcasts after commit (ShouldDispatchAfterCommit) so restaurant
            // members and the customer never observe a rolled-back state.
            $this->notifier->orderStatusChanged($locked);

            return $locked->refresh();
        }, attempts: 3);
    }
}
