<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Full order representation for the owning/authorized actor. The internal
 * `status` is exposed to authorized staff; `public_status` is the
 * customer/driver-facing projection (research R-9).
 */
class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Order $order */
        $order = $this->resource;

        return [
            'id' => $order->id,
            'customer_id' => $order->customer_id,
            'restaurant_id' => $order->restaurant_id,
            'delivery_location_id' => $order->delivery_location_id,
            'type' => $order->type,
            'status' => $order->status->value,
            'public_status' => $order->status->publicStatus()->value,
            'currency_code' => $order->currency_code,
            'items_subtotal' => $order->items_subtotal,
            'delivery_fee' => $order->delivery_fee,
            'total' => $order->total,
            'customer' => $this->whenLoaded('customer', fn (): array => [
                'id' => $order->customer->id,
                'name' => $order->customer->name,
            ]),
            'restaurant' => new RestaurantResource($this->whenLoaded('restaurant')),
            'delivery_location' => new DeliveryLocationResource($this->whenLoaded('deliveryLocation')),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'assignment' => new DriverAssignmentResource($this->whenLoaded('activeAssignment')),
            'payment' => $this->whenLoaded('paymentAttempts', fn (): ?array => $order->paymentAttempts->first() === null ? null : [
                'method' => $order->paymentAttempts->first()->method->value,
                'status' => $order->paymentAttempts->first()->status->value,
                'amount' => $order->paymentAttempts->first()->amount,
                'currency_code' => $order->paymentAttempts->first()->currency_code,
            ]),
            'placed_at' => $order->placed_at?->toISOString(),
            'cancelled_at' => $order->cancelled_at?->toISOString(),
            'created_at' => $order->created_at?->toISOString(),
        ];
    }
}
