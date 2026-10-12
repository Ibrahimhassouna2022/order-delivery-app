<?php

namespace App\Actions\Notification;

use App\Events\DriverOfferExpired;
use App\Events\DriverOfferReceived;
use App\Events\DriverPayoutCompleted;
use App\Events\OrderPlaced;
use App\Events\OrderStatusChanged;
use App\Models\DriverAssignment;
use App\Models\DriverWalletLedgerEntry;
use App\Models\Order;

/**
 * Pushes order and dispatch updates to subscribed clients over the configured
 * broadcaster (Pusher Channels in production, log/null in dev/test).
 *
 * Each event also keeps the corresponding read endpoint authoritative, so a
 * client that misses a broadcast re-syncs on its next poll (FR-040).
 */
class BroadcastNotifier implements Notifier
{
    public function orderStatusChanged(Order $order): void
    {
        OrderStatusChanged::dispatch($order);
    }

    public function orderPlaced(Order $order): void
    {
        OrderPlaced::dispatch($order);
    }

    public function driverOfferCreated(DriverAssignment $assignment): void
    {
        DriverOfferReceived::dispatch($assignment);
    }

    public function driverOfferExpired(DriverAssignment $assignment): void
    {
        DriverOfferExpired::dispatch($assignment);
    }

    public function driverPayoutCompleted(DriverWalletLedgerEntry $entry): void
    {
        DriverPayoutCompleted::dispatch($entry);
    }
}
