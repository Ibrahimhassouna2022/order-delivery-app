<?php

namespace App\Actions\Driver;

use App\Actions\Notification\Notifier;
use App\Enums\DriverAssignmentStatus;
use App\Enums\OrderStatus;
use App\Models\DriverAssignment;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class ExpireDriverOfferAction
{
    public function __construct(
        private Notifier $notifier,
        private DispatchClosestDriverAction $dispatchClosestDriver,
    ) {}

    public function execute(DriverAssignment $routeAssignment): DriverAssignment
    {
        $result = DB::transaction(function () use ($routeAssignment): array {
            Order::query()->lockForUpdate()->findOrFail($routeAssignment->order_id);
            $assignment = DriverAssignment::query()
                ->lockForUpdate()
                ->findOrFail($routeAssignment->getKey());

            if ($assignment->status === DriverAssignmentStatus::Expired) {
                return [
                    'assignment' => $assignment->load([
                        'order.restaurant',
                        'order.items',
                    ]),
                    'changed' => false,
                ];
            }

            if ($assignment->status !== DriverAssignmentStatus::Offered) {
                throw new DriverWorkflowConflictException('Only pending offers can be expired.');
            }

            $assignment->update([
                'status' => DriverAssignmentStatus::Expired,
                'responded_at' => now(),
            ]);

            return [
                'assignment' => $assignment->refresh()->load([
                    'order.restaurant',
                    'order.items',
                ]),
                'changed' => true,
            ];
        }, attempts: 3);

        if ($result['changed']) {
            $this->notifier->driverOfferExpired($result['assignment']);
            $this->reOffer($result['assignment']->order);
        }

        return $result['assignment'];
    }

    /**
     * Offer the order to the next closest driver, excluding every driver who
     * has already been offered it (FR-016).
     */
    private function reOffer(Order $order): void
    {
        if (! in_array($order->status, [OrderStatus::Placed, OrderStatus::ReadyForPickup], true)) {
            return;
        }

        $alreadyOfferedDriverIds = DriverAssignment::query()
            ->where('order_id', $order->id)
            ->pluck('driver_user_id')
            ->all();

        $this->dispatchClosestDriver->execute($order->refresh(), $alreadyOfferedDriverIds);
    }
}
