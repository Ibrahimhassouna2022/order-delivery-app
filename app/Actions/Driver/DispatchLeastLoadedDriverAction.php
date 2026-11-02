<?php

namespace App\Actions\Driver;

use App\Jobs\ExpireDriverOfferJob;
use App\Models\DriverAssignment;
use App\Models\Order;

/**
 * Creates the next driver offer for the available driver with the fewest
 * completed deliveries and schedules its 30 s expiry (FR-015/FR-016/FR-017).
 */
class DispatchLeastLoadedDriverAction
{
    public function __construct(
        private FindLeastLoadedAvailableDriverAction $findLeastLoadedDriver,
        private CreateDriverOfferAction $createOffer,
    ) {}

    /**
     * @param  array<int, int>  $excludeDriverUserIds
     */
    public function execute(Order $order, array $excludeDriverUserIds = []): ?DriverAssignment
    {
        $candidate = $this->findLeastLoadedDriver->execute($excludeDriverUserIds);

        if ($candidate === null) {
            return null;
        }

        ['assignment' => $assignment, 'created' => $created] = $this->createOffer->execute($order, [
            'driver_user_id' => $candidate['driver']->id,
            'distance_meters' => null,
            'fare' => $order->delivery_fee,
        ]);

        if ($created) {
            ExpireDriverOfferJob::dispatch($assignment->id)
                ->delay($assignment->expires_at ?? now());
        }

        return $assignment;
    }
}
