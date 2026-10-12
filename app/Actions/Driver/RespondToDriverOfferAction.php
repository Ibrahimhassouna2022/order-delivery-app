<?php

namespace App\Actions\Driver;

use App\Actions\Notification\Notifier;
use App\Enums\DriverAssignmentStatus;
use App\Enums\OrderStatus;
use App\Models\DriverAssignment;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RespondToDriverOfferAction
{
    public function __construct(private Notifier $notifier) {}

    /**
     * @return array{assignment: DriverAssignment, changed: bool}
     */
    public function execute(User $driver, DriverAssignment $routeAssignment, bool $accept): array
    {
        $result = DB::transaction(function () use ($driver, $routeAssignment, $accept): array {
            $order = Order::query()->lockForUpdate()->findOrFail($routeAssignment->order_id);
            $assignment = DriverAssignment::query()
                ->whereBelongsTo($driver, 'driver')
                ->where('order_id', $order->id)
                ->lockForUpdate()
                ->findOrFail($routeAssignment->getKey());

            $targetStatus = $accept
                ? DriverAssignmentStatus::Accepted
                : DriverAssignmentStatus::Rejected;

            if ($assignment->status === $targetStatus) {
                return [
                    'assignment' => $assignment->load([
                        'order.restaurant',
                        'order.items',
                    ]),
                    'changed' => false,
                ];
            }

            if ($assignment->status !== DriverAssignmentStatus::Offered) {
                throw new DriverWorkflowConflictException('This offer can no longer be answered.');
            }

            if (! $accept) {
                $assignment->update([
                    'status' => DriverAssignmentStatus::Rejected,
                    'responded_at' => now(),
                ]);

                return [
                    'assignment' => $assignment->refresh()->load([
                        'order.restaurant',
                        'order.items',
                    ]),
                    'changed' => true,
                ];
            }

            if (! in_array($order->status, [OrderStatus::Placed, OrderStatus::ReadyForPickup], true)) {
                throw new DriverWorkflowConflictException('This order can no longer be accepted.');
            }

            $profile = $driver->driverProfile()->lockForUpdate()->first();

            if ($profile === null || ! $profile->is_available) {
                throw new DriverWorkflowConflictException('The driver is not available to accept this offer.');
            }

            if (
                $driver->driverAssignments()
                    ->where('id', '!=', $assignment->getKey())
                    ->whereIn('status', [
                        DriverAssignmentStatus::Offered,
                        DriverAssignmentStatus::Accepted,
                        DriverAssignmentStatus::PickedUp,
                    ])
                    ->exists()
            ) {
                throw new DriverWorkflowConflictException(
                    'A driver with another active offer or delivery cannot accept this offer.',
                );
            }

            $assignment->update([
                'status' => DriverAssignmentStatus::Accepted,
                'responded_at' => now(),
            ]);
            $order->update(['status' => OrderStatus::Assigned]);
            $profile->update([
                'is_available' => false,
                'availability_updated_at' => now(),
            ]);

            return [
                'assignment' => $assignment->refresh()->load([
                    'order.restaurant',
                    'order.deliveryLocation',
                    'order.items',
                ]),
                'changed' => true,
            ];
        }, attempts: 3);

        if ($accept && $result['changed']) {
            $this->notifier->orderStatusChanged($result['assignment']->order);
        }

        return $result;
    }
}
