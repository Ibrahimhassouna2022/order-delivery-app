<?php

namespace App\Policies;

use App\Models\DriverRating;
use App\Models\User;

class DriverRatingPolicy
{
    /**
     * View a rating: the customer who wrote it or the rated driver.
     */
    public function view(User $user, DriverRating $rating): bool
    {
        return $rating->customer_id === $user->id
            || $rating->driver_user_id === $user->id;
    }
}
