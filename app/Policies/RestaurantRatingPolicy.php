<?php

namespace App\Policies;

use App\Models\RestaurantRating;
use App\Models\User;

class RestaurantRatingPolicy
{
    /**
     * View a rating: the customer who wrote it or a member of the restaurant.
     */
    public function view(User $user, RestaurantRating $rating): bool
    {
        return $rating->customer_id === $user->id
            || $user->restaurants()->whereKey($rating->restaurant_id)->exists();
    }
}
