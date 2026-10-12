<?php

namespace App\Models;

use Database\Factories\DriverRatingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['customer_id', 'order_id', 'driver_user_id', 'score', 'comment'])]
class DriverRating extends Model
{
    /** @use HasFactory<DriverRatingFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'score' => 'integer',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
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
