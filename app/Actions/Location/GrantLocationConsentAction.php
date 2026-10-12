<?php

namespace App\Actions\Location;

use App\Actions\Audit\RecordAuditAction;
use App\Models\LocationConsent;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class GrantLocationConsentAction
{
    public function __construct(private RecordAuditAction $audit) {}

    /**
     * @return array{consent: LocationConsent, created: bool}
     */
    public function execute(User $user): array
    {
        return DB::transaction(function () use ($user): array {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());
            $activeConsent = LocationConsent::query()
                ->whereBelongsTo($lockedUser, 'user')
                ->whereNull('revoked_at')
                ->latest('granted_at')
                ->lockForUpdate()
                ->first();

            if ($activeConsent !== null) {
                return [
                    'consent' => $activeConsent,
                    'created' => false,
                ];
            }

            $consent = LocationConsent::query()->create([
                'user_id' => $lockedUser->id,
                'granted_at' => now(),
                'policy_version' => config('delivery.privacy.policy_version'),
            ]);

            $this->audit->execute('location_consent.granted', $consent, $lockedUser);

            return [
                'consent' => $consent,
                'created' => true,
            ];
        }, attempts: 3);
    }
}
