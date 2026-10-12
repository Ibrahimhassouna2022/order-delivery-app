<?php

namespace App\Models;

use Database\Factories\DriverProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'is_available', 'availability_updated_at', 'completed_deliveries_count'])]
class DriverProfile extends Model
{
    /** @use HasFactory<DriverProfileFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
            'availability_updated_at' => 'datetime',
            'completed_deliveries_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
