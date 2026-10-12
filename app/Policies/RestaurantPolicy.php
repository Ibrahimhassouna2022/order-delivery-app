<?php

namespace App\Policies;

use App\Models\Restaurant;
use App\Models\User;

class RestaurantPolicy
{
    /**
     * A restaurant user may only act on restaurants they are a member of
     * (FR-023).
     */
    public function manage(User $user, Restaurant $restaurant): bool
    {
        return $user->restaurants()->whereKey($restaurant->getKey())->exists();
    }

    /**
     * View a restaurant's incoming orders.
     */
    public function viewOrders(User $user, Restaurant $restaurant): bool
    {
        return $this->manage($user, $restaurant);
    }
}
