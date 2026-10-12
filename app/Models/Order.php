<?php

namespace App\Models;

use App\Enums\DriverAssignmentStatus;
use App\Enums\OrderStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'customer_id',
    'restaurant_id',
    'delivery_location_id',
    'type',
    'status',
    'idempotency_key',
    'idempotency_hash',
    'currency_code',
    'items_subtotal',
    'delivery_fee',
    'total',
    'placed_at',
    'cancelled_at',
    'cancelled_by_user_id',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected $attributes = [
        'type' => 'food',
        'status' => 'placed',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'items_subtotal' => 'decimal:3',
            'delivery_fee' => 'decimal:3',
            'total' => 'decimal:3',
            'placed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function deliveryLocation(): BelongsTo
    {
        return $this->belongsTo(DeliveryLocation::class);
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by_user_id');
    }

    /**
     * The assignment currently owning this order, if any (status not terminal).
     */
    public function activeAssignment(): HasOne
    {
        return $this->hasOne(DriverAssignment::class)
            ->whereIn('status', [
                DriverAssignmentStatus::Offered,
                DriverAssignmentStatus::Accepted,
                DriverAssignmentStatus::PickedUp,
            ])
            ->latestOfMany();
    }

    /**
     * Whether a driver may still be offered this order.
     *
     * @param  Builder<Order>  $query
     */
    public function scopeDispatchable(Builder $query): void
    {
        $query->where('status', OrderStatus::Placed)
            ->whereDoesntHave('driverAssignments', fn ($query) => $query->whereIn('status', [
                DriverAssignmentStatus::Offered,
                DriverAssignmentStatus::Accepted,
                DriverAssignmentStatus::PickedUp,
                DriverAssignmentStatus::Delivered,
            ]));
    }

    /**
     * Limit to orders that currently have an active (non-terminal) assignment.
     *
     * @param  Builder<Order>  $query
     */
    public function scopeActiveAssignment(Builder $query): void
    {
        $query->whereHas('driverAssignments', fn ($query) => $query->whereIn('status', [
            DriverAssignmentStatus::Offered,
            DriverAssignmentStatus::Accepted,
            DriverAssignmentStatus::PickedUp,
        ]));
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function driverAssignments(): HasMany
    {
        return $this->hasMany(DriverAssignment::class);
    }

    public function paymentAttempts(): HasMany
    {
        return $this->hasMany(PaymentAttempt::class);
    }

    public function restaurantRating(): HasOne
    {
        return $this->hasOne(RestaurantRating::class);
    }

    public function driverRating(): HasOne
    {
        return $this->hasOne(DriverRating::class);
    }
}
