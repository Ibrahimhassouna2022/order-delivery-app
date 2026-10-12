<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\DriverAssignmentStatus;
use App\Models\DriverAssignment;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Minimal live-tracking payload (FR-012/FR-027). Deliberately small so a
 * customer on a slow connection can poll it as a fallback when a broadcast is
 * missed (FR-040).
 */
class OrderTrackingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Order $order */
        $order = $this->resource;
        $assignment = $this->whenLoaded('activeAssignment');
        $assignment = $assignment instanceof DriverAssignment ? $assignment : null;

        $driver = null;

        if ($assignment !== null && in_array($assignment->status, [
            DriverAssignmentStatus::Accepted,
            DriverAssignmentStatus::PickedUp,
        ], true)) {
            $driverLocation = $assignment->driver->driverLocation;

            $driver = [
                'id' => $assignment->driver->id,
                'name' => $assignment->driver->name,
                'latitude' => $driverLocation?->latitude,
                'longitude' => $driverLocation?->longitude,
                'recorded_at' => $driverLocation?->recorded_at?->toISOString(),
            ];
        }

        return [
            'order_id' => $order->id,
            'status' => $order->status->publicStatus()->value,
            'restaurant' => $this->whenLoaded('restaurant', fn (): array => [
                'name' => $order->restaurant->name,
                'latitude' => $order->restaurant->latitude,
                'longitude' => $order->restaurant->longitude,
            ]),
            'delivery_location' => $this->whenLoaded('deliveryLocation', fn (): array => [
                'latitude' => $order->deliveryLocation->latitude,
                'longitude' => $order->deliveryLocation->longitude,
            ]),
            'driver' => $driver,
            'updated_at' => $order->updated_at?->toISOString(),
        ];
    }
}
