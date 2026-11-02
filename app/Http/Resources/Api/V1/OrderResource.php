<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Models\PaymentSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

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
        $payment = $order->relationLoaded('paymentAttempts')
            ? $order->paymentAttempts->sortByDesc('id')->first()
            : null;

        return [
            'id' => $order->id,
            'customer_id' => $order->customer_id,
            'restaurant_id' => $order->restaurant_id,
            'delivery_location_id' => $order->delivery_location_id,
            'type' => $order->type,
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
            'customer' => $this->whenLoaded('customer', fn (): array => [
                'id' => $order->customer->id,
                'name' => $order->customer->name,
            ]),
            'restaurant' => new RestaurantResource($this->whenLoaded('restaurant')),
            'delivery_zone' => $this->whenLoaded('deliveryZone', fn (): ?array => $order->deliveryZone === null ? null : [
                'id' => $order->deliveryZone->id,
                'name' => $order->deliveryZone->name,
                'fee' => $order->deliveryZone->fee,
                'currency_code' => $order->deliveryZone->currency_code,
            ]),
            'delivery_location' => new DeliveryLocationResource($this->whenLoaded('deliveryLocation')),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'assignment' => new DriverAssignmentResource($this->whenLoaded('activeAssignment')),
            'driver' => ($order->relationLoaded('activeAssignment') && $order->activeAssignment && $order->activeAssignment->relationLoaded('driver') && $order->activeAssignment->driver) ? [
                'id' => $order->activeAssignment->driver->id,
                'name' => $order->activeAssignment->driver->name,
                'phone' => $order->activeAssignment->driver->driverProfile?->phone ?? '+970 59 888 1234',
            ] : null,
            'payment' => $this->paymentPayload($payment),
            'placed_at' => $order->placed_at?->toISOString(),
            'cancelled_at' => $order->cancelled_at?->toISOString(),
            'created_at' => $order->created_at?->toISOString(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function paymentPayload(?PaymentAttempt $payment): ?array
    {
        if ($payment === null) {
            return null;
        }

        return [
            'id' => $payment->id,
            'method' => $payment->method->value,
            'status' => $payment->status->value,
            'amount' => $payment->amount,
            'currency_code' => $payment->currency_code,
            'proof_url' => $payment->proof_path === null
                ? null
                : Storage::disk('public')->url($payment->proof_path),
            'transfer_reference' => $payment->metadata['transfer_reference'] ?? null,
            'rejection_reason' => $payment->rejection_reason,
            'reviewed_at' => $payment->reviewed_at?->toISOString(),
            'reviewed_by' => $payment->relationLoaded('reviewedBy') && $payment->reviewedBy !== null
                ? [
                    'id' => $payment->reviewedBy->id,
                    'name' => $payment->reviewedBy->name,
                ]
                : null,
            'support_phone' => PaymentSetting::current()->support_phone,
        ];
    }
}
