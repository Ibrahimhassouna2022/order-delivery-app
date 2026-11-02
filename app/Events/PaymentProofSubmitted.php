<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/**
 * Notifies the restaurant owner and operations that a customer uploaded a
 * bank-transfer receipt that needs review.
 */
class PaymentProofSubmitted implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Order $order) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('operations'),
            new PrivateChannel('restaurant.'.$this->order->restaurant_id),
            new PrivateChannel('order.'.$this->order->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'PaymentProofSubmitted';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $attempt = $this->order->paymentAttempts()->latest('id')->first();

        return [
            'order_id' => $this->order->id,
            'restaurant_id' => $this->order->restaurant_id,
            'customer_name' => $this->order->customer?->name,
            'amount' => $attempt?->amount,
            'currency_code' => $attempt?->currency_code,
            'proof_url' => $attempt?->proof_path === null
                ? null
                : Storage::disk('public')->url($attempt->proof_path),
            'submitted_at' => $attempt?->updated_at?->toIso8601String(),
        ];
    }
}
