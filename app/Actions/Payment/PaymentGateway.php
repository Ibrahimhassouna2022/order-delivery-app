<?php

namespace App\Actions\Payment;

use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\PaymentAttempt;

/**
 * A boundary contract for collecting payment for an order. Implementations
 * wrap a specific method (cash on delivery today, a licensed provider later).
 */
interface PaymentGateway
{
    public function method(): PaymentMethod;

    /**
     * Record the intent to collect payment for the order.
     */
    public function recordIntent(Order $order): PaymentAttempt;
}
