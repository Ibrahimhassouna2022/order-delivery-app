<?php

namespace App\Actions\Driver;

use App\Actions\Notification\Notifier;
use App\Enums\DriverAssignmentStatus;
use App\Enums\OrderStatus;
use App\Enums\WalletEntryType;
use App\Models\DriverAssignment;
use App\Models\DriverWalletLedgerEntry;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TransitionDriverAssignmentAction
{
    public function __construct(private Notifier $notifier) {}

    /**
     * @return array{assignment: DriverAssignment, changed: bool}
     */
    public function pickup(User $driver, DriverAssignment $routeAssignment): array
    {
        return $this->notifyOrderStatusChanged($this->transition(
            $driver,
            $routeAssignment,
            DriverAssignmentStatus::Accepted,
            DriverAssignmentStatus::PickedUp,
            OrderStatus::Assigned,
            OrderStatus::PickedUp,
        ));
    }

    /**
     * @return array{assignment: DriverAssignment, changed: bool}
     */
    public function deliver(User $driver, DriverAssignment $routeAssignment): array
    {
        return $this->notifyOrderStatusChanged($this->transition(
            $driver,
            $routeAssignment,
            DriverAssignmentStatus::PickedUp,
            DriverAssignmentStatus::Delivered,
            OrderStatus::PickedUp,
            OrderStatus::Delivered,
        ));
    }

    /**
     * @param  array{assignment: DriverAssignment, changed: bool}  $result
     * @return array{assignment: DriverAssignment, changed: bool}
     */
    private function notifyOrderStatusChanged(array $result): array
    {
        if ($result['changed']) {
            $this->notifier->orderStatusChanged($result['assignment']->order);
        }

        return $result;
    }

    /**
     * @return array{assignment: DriverAssignment, changed: bool}
     */
    private function transition(
        User $driver,
        DriverAssignment $routeAssignment,
        DriverAssignmentStatus $requiredStatus,
        DriverAssignmentStatus $targetStatus,
        OrderStatus $requiredOrderStatus,
        OrderStatus $targetOrderStatus,
    ): array {
        return DB::transaction(function () use (
            $driver,
            $routeAssignment,
            $requiredStatus,
            $targetStatus,
            $requiredOrderStatus,
            $targetOrderStatus,
        ): array {
            $order = Order::query()->lockForUpdate()->findOrFail($routeAssignment->order_id);
            $assignment = DriverAssignment::query()
                ->whereBelongsTo($driver, 'driver')
                ->where('order_id', $order->id)
                ->lockForUpdate()
                ->findOrFail($routeAssignment->getKey());

            if ($assignment->status === $targetStatus && $order->status === $targetOrderStatus) {
                return [
                    'assignment' => $assignment->load([
                        'order.restaurant',
                        'order.deliveryLocation',
                        'order.items',
                    ]),
                    'changed' => false,
                ];
            }

            if (
                $assignment->status !== $requiredStatus
                || $order->status !== $requiredOrderStatus
            ) {
                throw new DriverWorkflowConflictException('This delivery step is out of sequence.');
            }

            $timestamp = now();
            $assignment->update([
                'status' => $targetStatus,
                $targetStatus === DriverAssignmentStatus::PickedUp ? 'picked_up_at' : 'delivered_at' => $timestamp,
            ]);
            $order->update(['status' => $targetOrderStatus]);

            if ($targetStatus === DriverAssignmentStatus::Delivered) {
                if ($assignment->fare === null || $assignment->currency_code === null) {
                    throw new DriverWorkflowConflictException('The delivery offer has no wallet credit amount.');
                }

                DriverWalletLedgerEntry::create([
                    'driver_user_id' => $driver->id,
                    'order_id' => $order->id,
                    'delivery_credit_order_id' => $order->id,
                    'entry_type' => WalletEntryType::DeliveryCredit,
                    'amount' => $assignment->fare,
                    'currency_code' => $assignment->currency_code,
                    'idempotency_key' => 'delivery-credit-order-'.$order->id,
                ]);

                $profile = $driver->driverProfile()->lockForUpdate()->first();

                if ($profile !== null) {
                    $profile->increment('completed_deliveries_count');
                }
            }

            return [
                'assignment' => $assignment->refresh()->load([
                    'order.restaurant',
                    'order.deliveryLocation',
                    'order.items',
                ]),
                'changed' => true,
            ];
        }, attempts: 3);
    }
}
