<?php

namespace App\Actions\Order;

use App\Actions\Audit\RecordAuditAction;
use App\Models\Order;
use App\Models\RestaurantRating;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Records a customer's restaurant rating for a delivered order (FR-013).
 *
 * The delivered/ownership gate is enforced by OrderPolicy@rate before this
 * action runs; this action enforces the one-per-order rule.
 */
class RateRestaurantAction
{
    public function __construct(private RecordAuditAction $audit) {}

    public function execute(
        User $customer,
        Order $order,
        int $score,
        ?string $comment = null,
    ): RestaurantRating {
        return DB::transaction(function () use ($customer, $order, $score, $comment): RestaurantRating {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->getKey());

            if ($locked->restaurantRating()->exists()) {
                throw new RatingConflictException('This order has already been rated.');
            }

            $rating = RestaurantRating::create([
                'customer_id' => $customer->id,
                'order_id' => $locked->id,
                'restaurant_id' => $locked->restaurant_id,
                'score' => $score,
                'comment' => $comment,
            ]);

            $this->audit->execute('order.restaurant_rated', $rating, $customer);

            return $rating;
        }, attempts: 3);
    }
}
