<?php

namespace App\Actions\Notification;

use App\Models\DriverAssignment;
use App\Models\DriverWalletLedgerEntry;
use App\Models\Order;

class NullNotifier implements Notifier
{
    public function orderStatusChanged(Order $order): void
    {
        //
    }

    public function orderPlaced(Order $order): void
    {
        //
    }

    public function driverOfferCreated(DriverAssignment $assignment): void
    {
        //
    }

    public function driverOfferExpired(DriverAssignment $assignment): void
    {
        //
    }

    public function driverPayoutCompleted(DriverWalletLedgerEntry $entry): void
    {
        //
    }
}
