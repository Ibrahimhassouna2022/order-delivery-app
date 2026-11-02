<?php

namespace App\Actions\Payment;

use App\Actions\Driver\DispatchLeastLoadedDriverAction;
use App\Actions\Notification\Notifier;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReviewBankTransferAction
{
    public function __construct(
        private Notifier $notifier,
        private DispatchLeastLoadedDriverAction $dispatchLeastLoadedDriver,
    ) {}

    public function execute(User $actor, Order $order, bool $approve, ?string $reason = null): PaymentAttempt
    {
        return DB::transaction(function () use ($actor, $order, $approve, $reason): PaymentAttempt {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->getKey());

            if (in_array($locked->status, [OrderStatus::Delivered, OrderStatus::Cancelled], true)) {
                throw new PaymentReviewConflictException('This order can no longer be reviewed.');
            }

            $attempt = $locked->paymentAttempts()
                ->where('method', PaymentMethod::BankTransfer)
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($attempt === null || $attempt->status !== PaymentStatus::Pending) {
                throw new PaymentReviewConflictException('There is no pending bank transfer to review.');
            }

            if ($attempt->proof_path === null) {
                throw new PaymentReviewConflictException('The customer has not uploaded a transfer receipt yet.');
            }

            $invoiceNumber = null;

            if ($approve) {
                $invoiceNumber = $locked->invoice_number ?? $this->invoiceNumber($locked);
            }

            $attempt->update([
                'status' => $approve ? PaymentStatus::Succeeded : PaymentStatus::Failed,
                'reviewed_by_user_id' => $actor->id,
                'reviewed_at' => now(),
                'rejection_reason' => $approve ? null : $reason,
                'provider_reference' => $approve ? $invoiceNumber : null,
            ]);

            $locked->update([
                'invoice_number' => $invoiceNumber,
                'payment_approved_at' => $approve ? now() : null,
            ]);

            $locked->refresh();
            $this->notifier->paymentReviewed($locked, $approve, $reason);

            if ($approve && $locked->fulfillment_type === 'delivery' && $this->dispatchLeastLoadedDriver !== null) {
                try {
                    $this->dispatchLeastLoadedDriver->execute($locked);
                } catch (\Throwable) {
                    // Ignore if no driver is available at this moment
                }
            }

            return $attempt->refresh()->load('reviewedBy');
        }, attempts: 3);
    }

    private function invoiceNumber(Order $order): string
    {
        return sprintf('INV-%s-%06d', now()->format('Ymd'), $order->id);
    }
}
