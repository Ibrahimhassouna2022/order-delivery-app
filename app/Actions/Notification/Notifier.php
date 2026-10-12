<?php

namespace App\Actions\Notification;

use App\Models\DriverAssignment;
use App\Models\DriverWalletLedgerEntry;
use App\Models\Order;

/**
 * A boundary contract for pushing order and dispatch updates to users. The
 * concrete channel (log today, Pusher or WhatsApp later) is chosen by config.
 */
interface Notifier
{
    public function orderStatusChanged(Order $order): void;

    public function orderPlaced(Order $order): void;

    public function driverOfferCreated(DriverAssignment $assignment): void;

    public function driverOfferExpired(DriverAssignment $assignment): void;

    public function driverPayoutCompleted(DriverWalletLedgerEntry $entry): void;
}
