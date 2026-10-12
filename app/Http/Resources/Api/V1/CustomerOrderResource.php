<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Order $order */
        $order = $this->resource;

        return [
            'id' => $order->id,
            'restaurant_id' => $order->restaurant_id,
            'delivery_location_id' => $order->delivery_location_id,
            'status' => $order->status->value,
            'public_status' => $order->status->publicStatus()->value,
            'currency_code' => $order->currency_code,
            'items_subtotal' => $order->items_subtotal,
            'delivery_fee' => $order->delivery_fee,
            'total' => $order->total,
            'payment_method' => $order->paymentAttempts->first()?->method->value,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'placed_at' => $order->placed_at?->toISOString(),
        ];
    }
}
