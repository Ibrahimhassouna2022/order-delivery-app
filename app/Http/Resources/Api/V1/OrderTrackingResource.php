<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\DriverAssignmentStatus;
use App\Enums\OrderStatus;
use App\Models\DriverAssignment;
use App\Models\Order;
use App\Models\PaymentSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

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
        $payment = $order->relationLoaded('paymentAttempts')
            ? $order->paymentAttempts->sortByDesc('id')->first()
            : null;

        $driver = null;

        if ($assignment !== null && in_array($assignment->status, [
            DriverAssignmentStatus::Accepted,
            DriverAssignmentStatus::PickedUp,
        ], true)) {
            $driverLocation = $assignment->driver->driverLocation;

            $driver = [
                'id' => $assignment->driver->id,
                'name' => $assignment->driver->name,
                'phone' => $assignment->driver->driverProfile?->phone ?? '+970 59 888 1234',
                'latitude' => $driverLocation?->latitude,
                'longitude' => $driverLocation?->longitude,
                'recorded_at' => $driverLocation?->recorded_at?->toISOString(),
            ];
        }

        return [
            'order_id' => $order->id,
            'status' => $order->status->publicStatus()->value,
            'kitchen_status' => $order->status->value,
            'is_preparing' => in_array($order->status, [
                OrderStatus::Preparing,
                OrderStatus::ReadyForPickup,
                OrderStatus::Assigned,
                OrderStatus::PickedUp,
                OrderStatus::Delivered,
            ], true),
            'invoice_number' => $order->invoice_number,
            'payment_approved_at' => $order->payment_approved_at?->toISOString(),
            'payment' => $payment === null ? null : [
                'method' => $payment->method->value,
                'status' => $payment->status->value,
                'amount' => $payment->amount,
                'currency_code' => $payment->currency_code,
                'proof_url' => $payment->proof_path === null
                    ? null
                    : Storage::disk('public')->url($payment->proof_path),
                'rejection_reason' => $payment->rejection_reason,
                'reviewed_at' => $payment->reviewed_at?->toISOString(),
            ],
            'fulfillment_type' => $order->fulfillment_type,
            'delivery_address' => $order->delivery_address ?? $order->deliveryLocation?->address,
            'delivery_phone' => $order->delivery_phone,
            'delivery_zone' => $order->relationLoaded('deliveryZone') && $order->deliveryZone !== null
                ? [
                    'id' => $order->deliveryZone->id,
                    'name' => $order->deliveryZone->name,
                    'fee' => $order->deliveryZone->fee,
                    'currency_code' => $order->deliveryZone->currency_code,
                ]
                : null,
            'restaurant' => $this->whenLoaded('restaurant', fn (): array => [
                'name' => $order->restaurant->name,
                'latitude' => $order->restaurant->latitude,
                'longitude' => $order->restaurant->longitude,
            ]),
            'delivery_location' => $this->when($order->deliveryLocation !== null, fn (): array => [
                'latitude' => $order->deliveryLocation->latitude,
                'longitude' => $order->deliveryLocation->longitude,
            ]),
            'driver' => $driver,
            'support_phone' => PaymentSetting::current()->support_phone,
            'updated_at' => $order->updated_at?->toISOString(),
        ];
    }
}
