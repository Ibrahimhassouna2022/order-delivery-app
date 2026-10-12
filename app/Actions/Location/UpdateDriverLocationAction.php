<?php

namespace App\Actions\Location;

use App\Models\DriverLocation;
use App\Models\LocationConsent;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateDriverLocationAction
{
    /**
     * @param  array{latitude: numeric-string|int|float, longitude: numeric-string|int|float}  $validatedData
     */
    public function execute(User $driver, array $validatedData): DriverLocation
    {
        return DB::transaction(function () use ($driver, $validatedData): DriverLocation {
            $lockedDriver = User::query()->lockForUpdate()->findOrFail($driver->getKey());
            $activeConsentExists = LocationConsent::query()
                ->whereBelongsTo($lockedDriver, 'user')
                ->whereNull('revoked_at')
                ->lockForUpdate()
                ->exists();

            if (! $activeConsentExists) {
                throw new LocationConsentRequiredException('Location consent is required to share a driver location.');
            }

            $location = DriverLocation::query()
                ->where('driver_user_id', $lockedDriver->id)
                ->lockForUpdate()
                ->first();

            if ($location === null) {
                return DriverLocation::query()->create([
                    'driver_user_id' => $lockedDriver->id,
                    'latitude' => $validatedData['latitude'],
                    'longitude' => $validatedData['longitude'],
                    'recorded_at' => now(),
                ]);
            }

            $location->update([
                'latitude' => $validatedData['latitude'],
                'longitude' => $validatedData['longitude'],
                'recorded_at' => now(),
            ]);

            return $location->refresh();
        }, attempts: 3);
    }
}
