<?php

namespace App\Models;

use App\Enums\DriverAssignmentStatus;
use Database\Factories\DriverAssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'order_id',
    'driver_user_id',
    'status',
    'distance_meters',
    'fare',
    'currency_code',
    'offered_at',
    'responded_at',
    'picked_up_at',
    'delivered_at',
    'expires_at',
])]
class DriverAssignment extends Model
{
    /** @use HasFactory<DriverAssignmentFactory> */
    use HasFactory;

    protected $attributes = [
        'status' => 'offered',
    ];

    protected function casts(): array
    {
        return [
            'status' => DriverAssignmentStatus::class,
            'distance_meters' => 'integer',
            'fare' => 'decimal:3',
            'offered_at' => 'datetime',
            'responded_at' => 'datetime',
            'picked_up_at' => 'datetime',
            'delivered_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Limit to offers that are still open (not yet answered and not expired).
     *
     * @param  Builder<DriverAssignment>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->where('status', DriverAssignmentStatus::Offered)
            ->where(fn ($query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>', now()));
    }

    /**
     * Alias of {@see scopeOpen()} using the vocabulary of the task list.
     *
     * @param  Builder<DriverAssignment>  $query
     */
    public function scopePending(Builder $query): void
    {
        $this->scopeOpen($query);
    }

    /**
     * Limit to offers whose response window has elapsed.
     *
     * @param  Builder<DriverAssignment>  $query
     */
    public function scopeExpired(Builder $query): void
    {
        $query->where('status', DriverAssignmentStatus::Offered)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());
    }

    /**
     * Whether this offer can still be accepted or rejected.
     */
    public function isOpen(): bool
    {
        return $this->status === DriverAssignmentStatus::Offered
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_user_id');
    }
}
