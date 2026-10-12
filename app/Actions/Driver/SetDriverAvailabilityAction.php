<?php

namespace App\Actions\Driver;

use App\Enums\DriverAssignmentStatus;
use App\Models\DriverProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SetDriverAvailabilityAction
{
    public function execute(User $driver, bool $isAvailable): DriverProfile
    {
        return DB::transaction(function () use ($driver, $isAvailable): DriverProfile {
            $lockedDriver = User::query()->lockForUpdate()->findOrFail($driver->getKey());
            $profile = $lockedDriver->driverProfile()->lockForUpdate()->first();

            if ($profile === null) {
                $profile = $lockedDriver->driverProfile()->create([
                    'is_available' => false,
                ]);
            }

            if (
                $isAvailable
                && ! $profile->is_available
                && $lockedDriver->driverAssignments()
                    ->whereIn('status', [
                        DriverAssignmentStatus::Offered,
                        DriverAssignmentStatus::Accepted,
                        DriverAssignmentStatus::PickedUp,
                    ])
                    ->exists()
            ) {
                throw new DriverWorkflowConflictException(
                    'A driver with an active offer or delivery cannot become available.',
                );
            }

            $profile->update([
                'is_available' => $isAvailable,
                'availability_updated_at' => now(),
            ]);

            return $profile->refresh();
        }, attempts: 3);
    }
}
