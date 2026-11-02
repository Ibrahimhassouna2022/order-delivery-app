<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\DriverAssignmentStatus;
use App\Models\DriverAssignment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverAssignmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var DriverAssignment $assignment */
        $assignment = $this->resource;
        $order = $assignment->order;
        $canViewDestination = in_array($assignment->status, [
            DriverAssignmentStatus::Accepted,
            DriverAssignmentStatus::PickedUp,
            DriverAssignmentStatus::Delivered,
        ], true);

        return [
            'id' => $assignment->id,
            'order_id' => $assignment->order_id,
            'order_status' => $order->status->value,
            'status' => $assignment->status->value,
            'distance_meters' => $assignment->distance_meters,
            'fare' => $assignment->fare,
            'currency_code' => $assignment->currency_code,
            'offered_at' => $assignment->offered_at?->toISOString(),
            'expires_at' => $assignment->expires_at?->toISOString(),
            'time_left_seconds' => $assignment->expires_at ? max(0, (int) now()->diffInSeconds($assignment->expires_at, false)) : 30,
            'responded_at' => $assignment->responded_at?->toISOString(),
            'picked_up_at' => $assignment->picked_up_at?->toISOString(),
            'delivered_at' => $assignment->delivered_at?->toISOString(),
            'restaurant' => [
                'name' => $order->restaurant->name,
                'address' => $order->restaurant->address,
                'phone' => $order->restaurant->phone,
            ],
            'customer_name' => $order->customer?->name ?? 'زبون',
            'dropoff_address' => $order->delivery_address ?? $order->deliveryLocation?->address ?? 'غزة',
            'delivery_zone' => $order->deliveryZone?->name ?? 'مدينة غزة',
            'items' => OrderItemResource::collection($order->items)->resolve(),
            'driver' => $this->when($assignment->relationLoaded('driver') && $assignment->driver !== null, fn (): array => [
                'id' => $assignment->driver->id,
                'name' => $assignment->driver->name,
                'phone' => $assignment->driver->driverProfile?->phone ?? '+970 59 888 1234',
            ]),
            'delivery_location' => $this->when($canViewDestination, fn (): array => [
                'label' => $order->deliveryLocation?->label ?? 'عنوان التوصيل',
                'address' => $order->deliveryLocation?->address ?? $order->delivery_address,
                'phone' => $order->delivery_phone,
                'latitude' => $order->deliveryLocation?->latitude,
                'longitude' => $order->deliveryLocation?->longitude,
                'delivery_instructions' => $order->deliveryLocation?->delivery_instructions ?? $order->notes,
            ]),
        ];
    }
}
