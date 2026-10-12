<?php

namespace App\Models;

use App\Enums\RestaurantOnboardingMethod;
use App\Enums\RestaurantStatus;
use Database\Factories\RestaurantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'description',
    'address',
    'phone',
    'latitude',
    'longitude',
    'status',
    'onboarding_method',
    'assisted_onboarding_completed_at',
])]
class Restaurant extends Model
{
    /** @use HasFactory<RestaurantFactory> */
    use HasFactory;

    protected $attributes = [
        'status' => 'pending',
        'onboarding_method' => 'assisted',
    ];

    protected function casts(): array
    {
        return [
            'status' => RestaurantStatus::class,
            'onboarding_method' => RestaurantOnboardingMethod::class,
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'assisted_onboarding_completed_at' => 'datetime',
        ];
    }

    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('membership_role')
            ->withTimestamps();
    }
}
