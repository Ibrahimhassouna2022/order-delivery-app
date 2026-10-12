<?php

namespace App\Actions\Driver;

use App\Jobs\ExpireDriverOfferJob;
use App\Models\DriverAssignment;
use App\Models\Order;

/**
 * Creates the next driver offer by selecting the closest available driver and
 * scheduling its 30 s expiry (FR-015/FR-016/FR-017).
 */
class DispatchClosestDriverAction
{
    public function __construct(
        private FindClosestAvailableDriverAction $findClosestDriver,
        private CreateDriverOfferAction $createOffer,
    ) {}

    /**
     * @param  array<int, int>  $excludeDriverUserIds
     */
    public function execute(Order $order, array $excludeDriverUserIds = []): ?DriverAssignment
    {
        $restaurant = $order->restaurant;

        if ($restaurant->latitude === null || $restaurant->longitude === null) {
            return null;
        }

        $candidate = $this->findClosestDriver->execute(
            (float) $restaurant->latitude,
            (float) $restaurant->longitude,
            $excludeDriverUserIds,
        );

        if ($candidate === null) {
            return null;
        }

        ['assignment' => $assignment, 'created' => $created] = $this->createOffer->execute($order, [
            'driver_user_id' => $candidate['driver']->id,
            'distance_meters' => $candidate['distance_meters'],
            'fare' => $order->delivery_fee,
        ]);

        if ($created) {
            ExpireDriverOfferJob::dispatch($assignment->id)
                ->delay($assignment->expires_at ?? now());
        }

        return $assignment;
    }
}
