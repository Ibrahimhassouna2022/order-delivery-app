<?php

namespace App\Models;

use Database\Factories\RestaurantRatingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['customer_id', 'order_id', 'restaurant_id', 'score', 'comment'])]
class RestaurantRating extends Model
{
    /** @use HasFactory<RestaurantRatingFactory> */
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

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}
