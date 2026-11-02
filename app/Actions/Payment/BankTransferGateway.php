<?php

namespace App\Actions\Payment;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\PaymentAttempt;

/**
 * Records a manual bank-transfer intent. The customer transfers the order
 * value, uploads a receipt, and a merchant/admin approves it before the order
 * moves through the kitchen.
 */
class BankTransferGateway implements PaymentGateway
{
    public function method(): PaymentMethod
    {
        return PaymentMethod::BankTransfer;
    }

    public function recordIntent(Order $order): PaymentAttempt
    {
        return $order->paymentAttempts()->create([
            'method' => PaymentMethod::BankTransfer,
            'provider' => 'manual_bank_transfer',
            'status' => PaymentStatus::Pending,
            'amount' => $order->items_subtotal,
            'currency_code' => $order->currency_code,
            'idempotency_key' => 'bank-transfer',
        ]);
    }
}
