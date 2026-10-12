<?php

namespace App\Actions\Auth;

use App\Actions\Audit\RecordAuditAction;
use App\Models\DriverLocation;
use App\Models\LocationConsent;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Records a validated account-deletion request (FR-033/FR-035): flags the
 * account, revokes every token and the active location consent, and deletes
 * the live driver location. The scheduled PurgeAccountJob later anonymises
 * personal data after the retention window.
 */
class RequestAccountDeletionAction
{
    public function __construct(private RecordAuditAction $audit) {}

    public function execute(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());

            $lockedUser->update(['deletion_requested_at' => now()]);

            $lockedUser->tokens()->delete();

            LocationConsent::query()
                ->whereBelongsTo($lockedUser, 'user')
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);

            DriverLocation::query()
                ->where('driver_user_id', $lockedUser->getKey())
                ->delete();

            $this->audit->execute('account.deletion_requested', $lockedUser, $lockedUser);
        }, attempts: 3);
    }
}
