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
 * Broadcast when an offer's 30 s window elapses or ops expire it manually
 * (FR-016).
 */
class DriverOfferExpired implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public DriverAssignment $assignment) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('driver.'.$this->assignment->driver_user_id),
            new PrivateChannel('operations'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'DriverOfferExpired';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'assignment_id' => $this->assignment->id,
            'order_id' => $this->assignment->order_id,
            'expired_at' => ($this->assignment->responded_at ?? now())->toIso8601String(),
        ];
    }
}
