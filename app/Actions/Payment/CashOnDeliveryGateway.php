<?php

namespace App\Actions\Payment;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\PaymentAttempt;

class CashOnDeliveryGateway implements PaymentGateway
{
    public function method(): PaymentMethod
    {
        return PaymentMethod::CashOnDelivery;
    }

    public function recordIntent(Order $order): PaymentAttempt
    {
        return $order->paymentAttempts()->create([
            'method' => PaymentMethod::CashOnDelivery,
            'status' => PaymentStatus::Pending,
            'amount' => $order->total,
            'currency_code' => $order->currency_code,
            'idempotency_key' => 'cash-on-delivery',
        ]);
    }
}
