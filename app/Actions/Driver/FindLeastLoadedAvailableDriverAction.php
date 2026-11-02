<?php

namespace App\Actions\Driver;

use App\Enums\DriverAssignmentStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Selects the available driver with the fewest completed deliveries.
 *
 * Location freshness and geographic distance are intentionally not part of
 * the ranking: offers are distributed by workload only (FR-017).
 */
class FindLeastLoadedAvailableDriverAction
{
    /**
     * @param  array<int, int>  $excludeDriverUserIds
     * @return array{driver: User}|null
     */
    public function execute(array $excludeDriverUserIds = []): ?array
    {
        /** @var Collection<int, User> $candidates */
        $candidates = User::query()
            ->where('role', UserRole::Driver)
            ->whereHas('driverProfile', fn ($query) => $query->where('is_available', true))
            ->whereDoesntHave('driverAssignments', fn ($query) => $query->whereIn('status', [
                DriverAssignmentStatus::Offered,
                DriverAssignmentStatus::Accepted,
                DriverAssignmentStatus::PickedUp,
            ]))
            ->when($excludeDriverUserIds !== [], fn ($query) => $query->whereNotIn('id', $excludeDriverUserIds))
            ->with('driverProfile')
            ->get();

        $driver = $candidates
            ->sortBy([
                fn (User $first, User $second): int => $first->driverProfile->completed_deliveries_count
                    <=> $second->driverProfile->completed_deliveries_count,
                fn (User $first, User $second): int => $first->id <=> $second->id,
            ])
            ->first();

        return $driver === null ? null : ['driver' => $driver];
    }
}
