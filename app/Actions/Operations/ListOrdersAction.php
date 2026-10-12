<?php

namespace App\Actions\Operations;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Filterable live-order listing for the operations console (FR-029). Filters
 * by internal status, restaurant, or delivery-city substring; relationships
 * are eager-loaded so the console never triggers an N+1.
 */
class ListOrdersAction
{
    /**
     * @param  array{status?: string, restaurant_id?: int, city?: string, per_page?: int}  $filters
     */
    public function execute(User $admin, array $filters): LengthAwarePaginator
    {
        return Order::query()
            ->with([
                'restaurant',
                'customer',
                'deliveryLocation',
                'activeAssignment.driver',
                'items',
            ])
            ->when(
                isset($filters['status']) && $filters['status'] !== '',
                fn (Builder $query) => $query->where('status', OrderStatus::from($filters['status'])),
            )
            ->when(
                isset($filters['restaurant_id']) && $filters['restaurant_id'] !== null,
                fn (Builder $query) => $query->where('restaurant_id', (int) $filters['restaurant_id']),
            )
            ->when(
                isset($filters['city']) && $filters['city'] !== '',
                fn (Builder $query) => $query->whereHas(
                    'deliveryLocation',
                    fn (Builder $location) => $location->where('address', 'like', '%'.$filters['city'].'%'),
                ),
            )
            ->latest('placed_at')
            ->paginate($filters['per_page'] ?? 25);
    }
}
