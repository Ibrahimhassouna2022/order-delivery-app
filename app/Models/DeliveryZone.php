<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'fee',
    'estimated_time',
    'currency_code',
    'is_active',
    'sort_order',
])]
class DeliveryZone extends Model
{
    /** @use HasFactory<\Database\Factories\DeliveryZoneFactory> */
    use HasFactory;

    protected $attributes = [
        'currency_code' => 'ILS',
        'is_active' => true,
        'sort_order' => 0,
    ];

    protected function casts(): array
    {
        return [
            'fee' => 'decimal:3',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
