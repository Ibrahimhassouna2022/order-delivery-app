<?php

namespace App\Actions\Location;

use App\Actions\Audit\RecordAuditAction;
use App\Models\DriverLocation;
use App\Models\LocationConsent;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RevokeLocationConsentAction
{
    public function __construct(private RecordAuditAction $audit) {}

    public function execute(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());
            $activeConsent = LocationConsent::query()
                ->whereBelongsTo($lockedUser, 'user')
                ->whereNull('revoked_at')
                ->latest('granted_at')
                ->lockForUpdate()
                ->first();

            if ($activeConsent !== null) {
                $activeConsent->update(['revoked_at' => now()]);

                $this->audit->execute('location_consent.revoked', $activeConsent, $lockedUser);
            }

            DriverLocation::query()
                ->where('driver_user_id', $lockedUser->id)
                ->delete();
        }, attempts: 3);
    }
}
