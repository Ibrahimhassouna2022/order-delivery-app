<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Notifies the customer, restaurant and operations that a transfer receipt
 * was approved or rejected.
 */
class PaymentReviewed implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Order $order,
        public bool $approved,
        public ?string $reason = null,
    ) {}

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
        return 'PaymentReviewed';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->order->id,
            'approved' => $this->approved,
            'invoice_number' => $this->order->invoice_number,
            'reason' => $this->reason,
            'reviewed_at' => now()->toIso8601String(),
        ];
    }
}
