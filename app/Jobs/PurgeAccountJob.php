<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

/**
 * Anonymises a user's personal data once the retention window has elapsed
 * after a validated deletion request (FR-035/SC-011). The account row and the
 * financial/order records it is linked to are retained but de-identified; the
 * purge window is configurable via `delivery.retention.personal_data_purge_days`.
 */
class PurgeAccountJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $userId) {}

    public function handle(): void
    {
        $user = User::query()->find($this->userId);

        if ($user === null || ! $user->isDeletionPending()) {
            return;
        }

        $purgeCutoff = now()->subDays((int) config('delivery.retention.personal_data_purge_days'));

        if ($user->deletion_requested_at->gt($purgeCutoff)) {
            return;
        }

        DB::transaction(function () use ($user): void {
            $user->tokens()->delete();
            $user->locationConsents()->delete();
            $user->deliveryLocations()->delete();
            $user->driverLocation?->delete();

            $user->forceFill([
                'name' => 'Deleted User',
                'email' => null,
                'password' => null,
                'remember_token' => null,
                'email_verified_at' => null,
                'anonymized_at' => now(),
            ])->save();
        });
    }
}
