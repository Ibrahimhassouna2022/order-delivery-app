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
 * Broadcast to operations and the order's restaurant members when an order is
 * placed (contracts/broadcasting-channels.md).
 */
class OrderPlaced implements ShouldBroadcast, ShouldDispatchAfterCommit
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
            new PrivateChannel('order.'.$this->order->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'OrderPlaced';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->order->id,
            'restaurant_id' => $this->order->restaurant_id,
            'total' => $this->order->total,
            'placed_at' => ($this->order->placed_at ?? now())->toIso8601String(),
        ];
    }
}
