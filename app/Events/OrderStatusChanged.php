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
 * Broadcast when an order changes status (contracts/broadcasting-channels.md).
 *
 * The public `status` projection is broadcast so customers never observe the
 * internal lifecycle (research R-9).
 */
class OrderStatusChanged implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Order $order) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('order.'.$this->order->id),
            new PrivateChannel('App.Models.User.'.$this->order->customer_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'OrderStatusChanged';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->order->id,
            'status' => $this->order->status->publicStatus()->value,
            'occurred_at' => ($this->order->updated_at ?? now())->toIso8601String(),
        ];
    }
}
