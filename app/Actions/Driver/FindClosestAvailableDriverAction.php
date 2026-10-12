<?php

namespace App\Actions\Driver;

use App\Enums\DriverAssignmentStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Selects the nearest available driver to a pickup point using the Haversine
 * great-circle distance, computed in PHP so it stays portable across SQLite
 * (dev/test) and PostgreSQL (production) (research R-4).
 *
 * Candidate set: drivers with an available profile, a location recorded within
 * the freshness window, and no active assignment; capped to
 * config('delivery.dispatch.candidate_limit') before ranking. Ties on distance
 * are broken by the fewest completed deliveries (FR-017).
 */
class FindClosestAvailableDriverAction
{
    /**
     * @param  array<int, int>  $excludeDriverUserIds
     * @return array{driver: User, distance_meters: int}|null
     */
    public function execute(float $latitude, float $longitude, array $excludeDriverUserIds = []): ?array
    {
        $freshnessSeconds = (int) config('delivery.dispatch.location_freshness_seconds', 60);
        $candidateLimit = (int) config('delivery.dispatch.candidate_limit', 50);

        /** @var Collection<int, User> $candidates */
        $candidates = User::query()
            ->where('role', UserRole::Driver)
            ->whereHas('driverProfile', fn ($query) => $query->where('is_available', true))
            ->whereHas('driverLocation', fn ($query) => $query
                ->where('recorded_at', '>=', now()->subSeconds($freshnessSeconds)))
            ->whereDoesntHave('driverAssignments', fn ($query) => $query->whereIn('status', [
                DriverAssignmentStatus::Offered,
                DriverAssignmentStatus::Accepted,
                DriverAssignmentStatus::PickedUp,
            ]))
            ->when($excludeDriverUserIds !== [], fn ($query) => $query->whereNotIn('id', $excludeDriverUserIds))
            ->with(['driverLocation', 'driverProfile'])
            ->limit($candidateLimit)
            ->get();

        return $candidates
            ->map(fn (User $driver): array => [
                'driver' => $driver,
                'distance_meters' => $this->distanceInMeters(
                    $latitude,
                    $longitude,
                    (float) $driver->driverLocation->latitude,
                    (float) $driver->driverLocation->longitude,
                ),
            ])
            ->sortBy([
                ['distance_meters', 'asc'],
                fn (array $first, array $second): int => $first['driver']->driverProfile->completed_deliveries_count
                    <=> $second['driver']->driverProfile->completed_deliveries_count,
            ])
            ->first();
    }

    private function distanceInMeters(float $fromLat, float $fromLng, float $toLat, float $toLng): int
    {
        $earthRadiusMeters = 6371000.0;

        $latitudeDelta = deg2rad($toLat - $fromLat);
        $longitudeDelta = deg2rad($toLng - $fromLng);

        $a = sin($latitudeDelta / 2) ** 2
            + cos(deg2rad($fromLat)) * cos(deg2rad($toLat)) * sin($longitudeDelta / 2) ** 2;

        return (int) round($earthRadiusMeters * 2 * atan2(sqrt($a), sqrt(1 - $a)));
    }
}
