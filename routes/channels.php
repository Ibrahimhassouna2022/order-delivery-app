<?php

use App\Enums\DriverAssignmentStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Channel authorization for the private channels described in
| specs/001-delivery-distribution-platform/contracts/broadcasting-channels.md.
| Authorization fails closed: a closure must return true; anything else
| results in a 403.
|
*/

Broadcast::channel('App.Models.User.{userId}', function (User $user, int $userId): bool {
    return $user->id === $userId;
});

Broadcast::channel('order.{orderId}', function (User $user, int $orderId): bool {
    $order = Order::query()->find($orderId);

    if ($order === null) {
        return false;
    }

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
        ])
        ->exists()) {
        return true;
    }

    return $user->restaurants()->whereKey($order->restaurant_id)->exists();
});

Broadcast::channel('driver.{driverUserId}', function (User $user, int $driverUserId): bool {
    return $user->role === UserRole::Driver && $user->id === $driverUserId;
});

Broadcast::channel('operations', function (User $user): bool {
    return $user->role === UserRole::Admin;
});
