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
            'status' => $assignment->status->value,
            'distance_meters' => $assignment->distance_meters,
            'fare' => $assignment->fare,
            'currency_code' => $assignment->currency_code,
            'offered_at' => $assignment->offered_at?->toISOString(),
            'responded_at' => $assignment->responded_at?->toISOString(),
            'picked_up_at' => $assignment->picked_up_at?->toISOString(),
            'delivered_at' => $assignment->delivered_at?->toISOString(),
            'restaurant' => [
                'name' => $order->restaurant->name,
                'address' => $order->restaurant->address,
            ],
            'items' => OrderItemResource::collection($order->items)->resolve(),
            'delivery_location' => $this->when($canViewDestination, fn (): array => [
                'label' => $order->deliveryLocation->label,
                'address' => $order->deliveryLocation->address,
                'latitude' => $order->deliveryLocation->latitude,
                'longitude' => $order->deliveryLocation->longitude,
                'delivery_instructions' => $order->deliveryLocation->delivery_instructions,
            ]),
        ];
    }
}
