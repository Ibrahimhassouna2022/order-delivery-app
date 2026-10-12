<?php

namespace App\Policies;

use App\Enums\DriverAssignmentStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OrderPolicy
{
    /**
     * View an order: its customer, an assigned/former driver, a member of its
     * restaurant, or an administrator (FR-014/FR-023/FR-029).
     */
    public function view(User $user, Order $order): bool
    {
        if ($user->role === UserRole::Admin) {
            return true;
        }

        if ($order->customer_id === $user->id) {
            return true;
        }

        if ($order->driverAssignments()
            ->where('driver_user_id', $user->id)
            ->whereIn('status', [
                DriverAssignmentStatus::Offered,
                DriverAssignmentStatus::Accepted,
                DriverAssignmentStatus::PickedUp,
                DriverAssignmentStatus::Delivered,
            ])
            ->exists()) {
            return true;
        }

        return $user->restaurants()->whereKey($order->restaurant_id)->exists();
    }

    /**
     * Cancel an order (FR-041). A customer may cancel only before any active
     * assignment exists; admin/operations may cancel until delivery.
     */
    public function cancel(User $user, Order $order): Response
    {
        if (in_array($order->status, [OrderStatus::Delivered, OrderStatus::Cancelled], true)) {
            return Response::deny('The order can no longer be cancelled.');
        }

        if ($user->role === UserRole::Admin) {
            return Response::allow();
        }

        if ($order->customer_id !== $user->id) {
            return Response::denyAsNotFound();
        }

        if ($order->driverAssignments()
            ->whereIn('status', [
                DriverAssignmentStatus::Offered,
                DriverAssignmentStatus::Accepted,
                DriverAssignmentStatus::PickedUp,
            ])
            ->exists()) {
            return Response::deny('The order can no longer be cancelled once a driver is assigned.');
        }

        return Response::allow();
    }

    /**
     * Rate a delivered order (FR-013).
     */
    public function rate(User $user, Order $order): Response
    {
        if ($order->customer_id !== $user->id) {
            return Response::denyAsNotFound();
        }

        if ($order->status !== OrderStatus::Delivered) {
            return Response::deny('Only delivered orders can be rated.');
        }

        return Response::allow();
    }
}
