<?php

namespace App\Actions\Restaurant;

use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Lists orders for the restaurants the actor is a member of (FR-023).
 *
 * Membership scoping happens here even when no `restaurant_id` filter is
 * supplied, so a restaurant user can never observe another tenant's orders.
 */
class ListRestaurantOrdersAction
{
    public function execute(User $user, ?int $restaurantId = null, int $perPage = 20): LengthAwarePaginator
    {
        $restaurantIds = $user->restaurants()->pluck('restaurants.id');

        return Order::query()
            ->with(['customer', 'items', 'restaurant'])
            ->whereIn('restaurant_id', $restaurantIds)
            ->when($restaurantId !== null, fn (Builder $query) => $query
                ->where('restaurant_id', $restaurantId))
            ->latest('placed_at')
            ->paginate($perPage);
    }
}
