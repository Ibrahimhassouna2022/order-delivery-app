<?php

namespace App\Events;

use App\Models\DriverWalletLedgerEntry;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Notifies the driver's private user channel when a payout is completed
 * (FR-022/FR-027). Useful for a future driver app; the pollable wallet read
 * stays authoritative (FR-040).
 */
class DriverPayoutCompleted implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public DriverWalletLedgerEntry $entry) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.'.$this->entry->driver_user_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'DriverPayoutCompleted';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'entry_id' => $this->entry->id,
            'driver_user_id' => $this->entry->driver_user_id,
            'entry_type' => $this->entry->entry_type->value,
            'amount' => $this->entry->amount,
            'currency_code' => $this->entry->currency_code,
        ];
    }
}
