<?php

namespace App\Actions\Order;

use App\Actions\Audit\RecordAuditAction;
use App\Enums\DriverAssignmentStatus;
use App\Models\DriverRating;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Records a customer's driver rating for a delivered order (FR-013).
 *
 * The driver is resolved from the delivered assignment so the rating always
 * names the driver who actually completed the delivery.
 */
class RateDriverAction
{
    public function __construct(private RecordAuditAction $audit) {}

    public function execute(
        User $customer,
        Order $order,
        int $score,
        ?string $comment = null,
    ): DriverRating {
        return DB::transaction(function () use ($customer, $order, $score, $comment): DriverRating {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->getKey());

            if ($locked->driverRating()->exists()) {
                throw new RatingConflictException('This order has already been rated.');
            }

            $assignment = $locked->driverAssignments()
                ->where('status', DriverAssignmentStatus::Delivered)
                ->orderByDesc('id')
                ->first();

            if ($assignment === null) {
                throw new RatingConflictException('No completed delivery exists for this order.');
            }

            $rating = DriverRating::create([
                'customer_id' => $customer->id,
                'order_id' => $locked->id,
                'driver_user_id' => $assignment->driver_user_id,
                'score' => $score,
                'comment' => $comment,
            ]);

            $this->audit->execute('order.driver_rated', $rating, $customer);

            return $rating;
        }, attempts: 3);
    }
}
