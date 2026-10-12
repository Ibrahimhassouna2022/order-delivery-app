<?php

namespace App\Actions\Order;

use App\Actions\Audit\RecordAuditAction;
use App\Enums\DriverAssignmentStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Cancels an order per FR-041:
 * - customers may cancel only while no active assignment exists;
 * - admin/operations may cancel any order until delivery is confirmed.
 *
 * The caller is responsible for authorization (OrderPolicy@cancel or the
 * owning-role route middleware); this action enforces the workflow itself and
 * records an audit entry.
 */
class CancelOrderAction
{
    public function __construct(private RecordAuditAction $audit) {}

    public function execute(User $actor, Order $order): Order
    {
        return DB::transaction(function () use ($actor, $order): Order {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->getKey());

            if (in_array($locked->status, [OrderStatus::Delivered, OrderStatus::Cancelled], true)) {
                throw new OrderCancellationConflictException('The order can no longer be cancelled.');
            }

            $hasActiveAssignment = $locked->driverAssignments()
                ->whereIn('status', [
                    DriverAssignmentStatus::Offered,
                    DriverAssignmentStatus::Accepted,
                    DriverAssignmentStatus::PickedUp,
                ])
                ->exists();

            if ($hasActiveAssignment && $actor->role !== UserRole::Admin) {
                throw new OrderCancellationConflictException(
                    'The order can no longer be cancelled once a driver is assigned.',
                );
            }

            $locked->update([
                'status' => OrderStatus::Cancelled,
                'cancelled_at' => now(),
                'cancelled_by_user_id' => $actor->getKey(),
            ]);

            $this->audit->execute('order.cancelled', $locked, $actor);

            return $locked->refresh();
        }, attempts: 3);
    }
}
