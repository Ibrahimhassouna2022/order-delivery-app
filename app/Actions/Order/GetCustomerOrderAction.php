<?php

namespace App\Actions\Order;

use App\Models\Order;

/**
 * Eager-loads everything the tracking read needs in one pass so the customer
 * polling endpoint never triggers an N+1 (FR-012, research R-9).
 */
class GetCustomerOrderAction
{
    public function execute(Order $order): Order
    {
        return $order->load([
            'restaurant',
            'deliveryLocation',
            'items',
            'activeAssignment.driver.driverLocation',
        ]);
    }
}
