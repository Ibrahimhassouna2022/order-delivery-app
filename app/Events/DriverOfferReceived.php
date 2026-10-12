<?php

namespace App\Events;

use App\Models\DriverAssignment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast to a driver when a new offer is created (FR-015).
 */
class DriverOfferReceived implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public DriverAssignment $assignment) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('driver.'.$this->assignment->driver_user_id)];
    }

    public function broadcastAs(): string
    {
        return 'DriverOfferReceived';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'assignment_id' => $this->assignment->id,
            'order_id' => $this->assignment->order_id,
            'distance_meters' => $this->assignment->distance_meters,
            'fare' => $this->assignment->fare,
            'currency_code' => $this->assignment->currency_code,
            'expires_at' => $this->assignment->expires_at?->toIso8601String(),
        ];
    }
}
