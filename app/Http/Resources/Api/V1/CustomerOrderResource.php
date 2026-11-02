<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Order;
use App\Models\PaymentSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

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
        $payment = $order->paymentAttempts->sortByDesc('id')->first();

        return [
            'id' => $order->id,
            'restaurant_id' => $order->restaurant_id,
            'delivery_location_id' => $order->delivery_location_id,
            'status' => $order->status->value,
            'public_status' => $order->status->publicStatus()->value,
            'invoice_number' => $order->invoice_number,
            'payment_approved_at' => $order->payment_approved_at?->toISOString(),
            'fulfillment_type' => $order->fulfillment_type,
            'delivery_address' => $order->delivery_address,
            'delivery_phone' => $order->delivery_phone,
            'notes' => $order->notes,
            'currency_code' => $order->currency_code,
            'items_subtotal' => $order->items_subtotal,
            'delivery_fee' => $order->delivery_fee,
            'total' => $order->total,
            'payment_method' => $payment?->method->value,
            'payment_status' => $payment?->status->value,
            'payment_proof_url' => $payment?->proof_path === null
                ? null
                : Storage::disk('public')->url($payment->proof_path),
            'payment_rejection_reason' => $payment?->rejection_reason,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'restaurant' => new RestaurantResource($this->whenLoaded('restaurant')),
            'driver' => ($order->relationLoaded('activeAssignment') && $order->activeAssignment && $order->activeAssignment->relationLoaded('driver') && $order->activeAssignment->driver) ? [
                'id' => $order->activeAssignment->driver->id,
                'name' => $order->activeAssignment->driver->name,
                'phone' => $order->activeAssignment->driver->driverProfile?->phone ?? '+970 59 888 1234',
            ] : null,
            'delivery_zone' => $this->whenLoaded('deliveryZone', fn (): ?array => $order->deliveryZone === null ? null : [
                'id' => $order->deliveryZone->id,
                'name' => $order->deliveryZone->name,
                'fee' => $order->deliveryZone->fee,
                'currency_code' => $order->deliveryZone->currency_code,
            ]),
            'support_phone' => PaymentSetting::current()->support_phone,
            'placed_at' => $order->placed_at?->toISOString(),
        ];
    }
}
