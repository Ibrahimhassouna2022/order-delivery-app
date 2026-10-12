<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'email_verified_at', 'is_guest', 'locale', 'deletion_requested_at', 'anonymized_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $attributes = [
        'role' => 'customer',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_guest' => 'boolean',
            'deletion_requested_at' => 'datetime',
            'anonymized_at' => 'datetime',
        ];
    }

    /**
     * Whether a validated account-deletion request is awaiting the personal
     * data purge window (FR-035 / SC-011).
     */
    public function isDeletionPending(): bool
    {
        return $this->deletion_requested_at !== null
            && $this->anonymized_at === null;
    }

    /**
     * Whether the account has already been anonymised by the retention job.
     */
    public function isAnonymized(): bool
    {
        return $this->anonymized_at !== null;
    }

    public function restaurants(): BelongsToMany
    {
        return $this->belongsToMany(Restaurant::class)
            ->withPivot('membership_role')
            ->withTimestamps();
    }

    public function deliveryLocations(): HasMany
    {
        return $this->hasMany(DeliveryLocation::class);
    }

    public function customerOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    public function driverProfile(): HasOne
    {
        return $this->hasOne(DriverProfile::class);
    }

    public function driverAssignments(): HasMany
    {
        return $this->hasMany(DriverAssignment::class, 'driver_user_id');
    }

    public function driverLocation(): HasOne
    {
        return $this->hasOne(DriverLocation::class, 'driver_user_id');
    }

    public function walletLedgerEntries(): HasMany
    {
        return $this->hasMany(DriverWalletLedgerEntry::class, 'driver_user_id');
    }

    public function locationConsents(): HasMany
    {
        return $this->hasMany(LocationConsent::class);
    }

    public function restaurantRatings(): HasMany
    {
        return $this->hasMany(RestaurantRating::class, 'customer_id');
    }

    public function driverRatings(): HasMany
    {
        return $this->hasMany(DriverRating::class, 'customer_id');
    }
}
