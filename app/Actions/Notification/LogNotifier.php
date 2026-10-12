<?php

namespace App\Actions\Notification;

use App\Models\DriverAssignment;
use App\Models\DriverWalletLedgerEntry;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class LogNotifier implements Notifier
{
    public function orderStatusChanged(Order $order): void
    {
        Log::info('Order status changed.', [
            'order_id' => $order->id,
            'status' => $order->status->value,
        ]);
    }

    public function orderPlaced(Order $order): void
    {
        Log::info('Order placed.', [
            'order_id' => $order->id,
            'restaurant_id' => $order->restaurant_id,
        ]);
    }

    public function driverOfferCreated(DriverAssignment $assignment): void
    {
        Log::info('Driver offer created.', [
            'assignment_id' => $assignment->id,
            'order_id' => $assignment->order_id,
            'driver_user_id' => $assignment->driver_user_id,
        ]);
    }

    public function driverOfferExpired(DriverAssignment $assignment): void
    {
        Log::info('Driver offer expired.', [
            'assignment_id' => $assignment->id,
            'order_id' => $assignment->order_id,
            'driver_user_id' => $assignment->driver_user_id,
        ]);
    }

    public function driverPayoutCompleted(DriverWalletLedgerEntry $entry): void
    {
        Log::info('Driver payout completed.', [
            'entry_id' => $entry->id,
            'driver_user_id' => $entry->driver_user_id,
            'amount' => $entry->amount,
            'currency_code' => $entry->currency_code,
        ]);
    }
}
