<?php

namespace App\Jobs;

use App\Actions\Driver\ExpireDriverOfferAction;
use App\Enums\DriverAssignmentStatus;
use App\Models\DriverAssignment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Expires a single driver offer after its response window elapses and triggers
 * the re-offer to the next closest driver (FR-016).
 */
class ExpireDriverOfferJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $assignmentId) {}

    public function handle(ExpireDriverOfferAction $expireOffer): void
    {
        $assignment = DriverAssignment::query()->find($this->assignmentId);

        if ($assignment === null || $assignment->status !== DriverAssignmentStatus::Offered) {
            return;
        }

        // The sync queue ignores the delay, so guard against expiring an offer
        // that is not actually due. The scheduled safety net catches stragglers.
        if ($assignment->expires_at !== null && $assignment->expires_at->isFuture()) {
            return;
        }

        $expireOffer->execute($assignment);
    }
}
