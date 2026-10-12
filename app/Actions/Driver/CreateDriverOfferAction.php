<?php

namespace App\Actions\Driver;

use App\Actions\Notification\Notifier;
use App\Enums\DriverAssignmentStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\DriverAssignment;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use LogicException;

class CreateDriverOfferAction
{
    public function __construct(private Notifier $notifier) {}

    /**
     * @param  array{driver_user_id: int, distance_meters: int, fare: numeric-string|int|float}  $validatedData
     * @return array{assignment: DriverAssignment, created: bool}
     */
    public function execute(Order $order, array $validatedData): array
    {
        try {
            $result = DB::transaction(function () use ($order, $validatedData): array {
                $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->getKey());

                if (! in_array($lockedOrder->status, [OrderStatus::Placed, OrderStatus::ReadyForPickup], true)) {
                    throw new DriverWorkflowConflictException('This order is not available for dispatch.');
                }

                $driver = User::query()->lockForUpdate()->find($validatedData['driver_user_id']);

                if ($driver?->role !== UserRole::Driver) {
                    throw new DriverWorkflowConflictException('The selected user is not an eligible driver.');
                }

                $profile = $driver->driverProfile()->lockForUpdate()->first();

                if ($profile === null || ! $profile->is_available) {
                    throw new DriverWorkflowConflictException('The selected driver is not available.');
                }

                $existingAssignment = DriverAssignment::query()
                    ->where('order_id', $lockedOrder->id)
                    ->where('driver_user_id', $driver->id)
                    ->lockForUpdate()
                    ->first();

                if ($existingAssignment !== null) {
                    return $this->resolveExistingOffer($existingAssignment, $lockedOrder, $validatedData);
                }

                $hasActiveOrderOffer = DriverAssignment::query()
                    ->where('order_id', $lockedOrder->id)
                    ->whereIn('status', [
                        DriverAssignmentStatus::Offered,
                        DriverAssignmentStatus::Accepted,
                        DriverAssignmentStatus::PickedUp,
                    ])
                    ->exists();

                $hasActiveDriverOffer = $driver->driverAssignments()
                    ->whereIn('status', [
                        DriverAssignmentStatus::Offered,
                        DriverAssignmentStatus::Accepted,
                        DriverAssignmentStatus::PickedUp,
                    ])
                    ->exists();

                if ($hasActiveOrderOffer || $hasActiveDriverOffer) {
                    throw new DriverWorkflowConflictException(
                        'The order or driver already has an active offer or delivery.',
                    );
                }

                $assignment = $lockedOrder->driverAssignments()->create([
                    'driver_user_id' => $driver->id,
                    'status' => DriverAssignmentStatus::Offered,
                    'distance_meters' => $validatedData['distance_meters'],
                    'fare' => $validatedData['fare'],
                    'currency_code' => $lockedOrder->currency_code,
                    'offered_at' => now(),
                    'expires_at' => now()->addSeconds(
                        (int) config('delivery.dispatch.offer_window_seconds', 30),
                    ),
                ]);

                return [
                    'assignment' => $assignment->load([
                        'order.restaurant',
                        'order.items',
                    ]),
                    'created' => true,
                ];
            }, attempts: 3);
        } catch (UniqueConstraintViolationException $exception) {
            $existingAssignment = DriverAssignment::query()
                ->where('order_id', $order->getKey())
                ->where('driver_user_id', $validatedData['driver_user_id'])
                ->with(['order.restaurant', 'order.deliveryLocation', 'order.items'])
                ->first();

            if ($existingAssignment === null) {
                throw $exception;
            }

            $result = $this->resolveExistingOffer($existingAssignment, $order, $validatedData);
        }

        if ($result['created']) {
            $this->notifier->driverOfferCreated($result['assignment']);
        }

        return $result;
    }

    /**
     * @param  array{driver_user_id: int, distance_meters: int, fare: numeric-string|int|float}  $validatedData
     * @return array{assignment: DriverAssignment, created: bool}
     */
    private function resolveExistingOffer(
        DriverAssignment $assignment,
        Order $order,
        array $validatedData,
    ): array {
        if (
            $assignment->status !== DriverAssignmentStatus::Offered
            || $assignment->distance_meters !== $validatedData['distance_meters']
            || $this->toMilliunits((string) $assignment->fare) !== $this->toMilliunits((string) $validatedData['fare'])
            || $assignment->currency_code !== $order->currency_code
        ) {
            throw new DriverWorkflowConflictException(
                'This driver has already received an offer for the order.',
            );
        }

        return [
            'assignment' => $assignment->loadMissing([
                'order.restaurant',
                'order.items',
            ]),
            'created' => false,
        ];
    }

    private function toMilliunits(string $amount): int
    {
        if (! preg_match('/^\d{1,9}(?:\.\d{1,3})?$/D', $amount)) {
            throw new LogicException('Driver fare must be a decimal amount with at most three places.');
        }

        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return ((int) $whole * 1000) + (int) str_pad($fraction, 3, '0');
    }
}
