<?php

namespace App\Events;

use App\Models\DriverLocation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast to the live order channel while a driver is on an active
 * assignment (contracts/broadcasting-channels.md).
 */
class DriverLocationUpdated implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $orderId,
        public DriverLocation $location,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('order.'.$this->orderId)];
    }

    public function broadcastAs(): string
    {
        return 'DriverLocationUpdated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->orderId,
            'driver_user_id' => $this->location->driver_user_id,
            'latitude' => (float) $this->location->latitude,
            'longitude' => (float) $this->location->longitude,
            'recorded_at' => ($this->location->recorded_at ?? now())->toIso8601String(),
        ];
    }
}
