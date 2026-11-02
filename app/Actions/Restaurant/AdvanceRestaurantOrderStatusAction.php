<?php

namespace App\Actions\Restaurant;

use App\Actions\Driver\DispatchLeastLoadedDriverAction;
use App\Actions\Notification\Notifier;
use App\Enums\DriverAssignmentStatus;
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
    public function __construct(
        private Notifier $notifier,
        private DispatchLeastLoadedDriverAction $dispatchLeastLoadedDriver,
    ) {}

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

            $usesBankTransfer = $locked->paymentAttempts()
                ->where('method', \App\Enums\PaymentMethod::BankTransfer)
                ->exists();

            if ($usesBankTransfer && $locked->payment_approved_at === null) {
                throw new OrderStatusConflictException('The bank transfer must be approved before accepting this order.');
            }

            $locked->update(['status' => $targetStatus]);

            // Broadcasts after commit (ShouldDispatchAfterCommit) so restaurant
            // members and the customer never observe a rolled-back state.
            $this->notifier->orderStatusChanged($locked);

            if (
                $locked->fulfillment_type === 'delivery'
                && $this->dispatchLeastLoadedDriver !== null
                && ! $locked->driverAssignments()->whereIn('status', [
                    DriverAssignmentStatus::Offered,
                    DriverAssignmentStatus::Accepted,
                    DriverAssignmentStatus::PickedUp,
                ])->exists()
            ) {
                try {
                    $this->dispatchLeastLoadedDriver->execute($locked);
                } catch (\Throwable) {
                    // Ignore if no eligible driver at this moment
                }
            }

            return $locked->refresh();
        }, attempts: 3);
    }
}
