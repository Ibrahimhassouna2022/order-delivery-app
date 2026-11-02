<?php

namespace App\Actions\Payment;

use App\Actions\Notification\Notifier;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SubmitPaymentProofAction
{
    public function __construct(private Notifier $notifier) {}

    public function execute(
        User $customer,
        Order $order,
        UploadedFile $proof,
        ?string $transferReference = null,
    ): PaymentAttempt {
        return DB::transaction(function () use ($customer, $order, $proof, $transferReference): PaymentAttempt {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->getKey());

            if ($locked->customer_id !== $customer->id) {
                throw new PaymentReviewConflictException('This order does not belong to you.');
            }

            if (in_array($locked->status, [OrderStatus::Delivered, OrderStatus::Cancelled], true)) {
                throw new PaymentReviewConflictException('The payment proof can no longer be submitted.');
            }

            $attempt = $locked->paymentAttempts()
                ->where('method', PaymentMethod::BankTransfer)
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($attempt === null) {
                throw new PaymentReviewConflictException('This order does not use bank transfer.');
            }

            if ($attempt->status === PaymentStatus::Succeeded) {
                throw new PaymentReviewConflictException('This payment has already been approved.');
            }

            $path = $proof->store('payment-proofs/'.$locked->id, 'public');

            if ($attempt->proof_path !== null && $attempt->proof_path !== $path) {
                Storage::disk('public')->delete($attempt->proof_path);
            }

            $attempt->update([
                'proof_path' => $path,
                'status' => PaymentStatus::Pending,
                'reviewed_by_user_id' => null,
                'reviewed_at' => null,
                'rejection_reason' => null,
                'metadata' => [
                    ...($attempt->metadata ?? []),
                    'transfer_reference' => $transferReference,
                    'original_name' => $proof->getClientOriginalName(),
                    'mime_type' => $proof->getMimeType(),
                    'size' => $proof->getSize(),
                    'submitted_at' => now()->toIso8601String(),
                ],
            ]);

            $locked->refresh();
            $this->notifier->paymentProofSubmitted($locked);

            return $attempt->refresh();
        }, attempts: 3);
    }
}
