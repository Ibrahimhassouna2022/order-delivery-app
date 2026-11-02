<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Payment\PaymentReviewConflictException;
use App\Actions\Payment\ReviewBankTransferAction;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ReviewPaymentRequest;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class PaymentReviewController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Order::query()
            ->with([
                'restaurant',
                'customer',
                'deliveryLocation',
                'deliveryZone',
                'items',
                'paymentAttempts.reviewedBy',
            ])
            ->whereHas('paymentAttempts', fn ($attempts) => $attempts
                ->where('method', PaymentMethod::BankTransfer)
                ->where('status', PaymentStatus::Pending)
                ->whereNotNull('proof_path'))
            ->latest('placed_at');

        if ($request->user()->role !== UserRole::Admin) {
            $query->whereIn('restaurant_id', $request->user()->restaurants()->pluck('restaurants.id'));
        }

        return OrderResource::collection($query->paginate(50));
    }

    public function store(
        ReviewPaymentRequest $request,
        Order $order,
        ReviewBankTransferAction $reviewPayment,
    ): JsonResponse {
        Gate::authorize('reviewPayment', $order);

        try {
            $reviewPayment->execute(
                $request->user(),
                $order,
                $request->validated('decision') === 'approved',
                $request->validated('rejection_reason'),
            );
        } catch (PaymentReviewConflictException $exception) {
            return response()->json(
                ['message' => $exception->getMessage()],
                Response::HTTP_CONFLICT,
            );
        }

        $order = $order->refresh()->load([
            'restaurant',
            'customer',
            'deliveryLocation',
            'deliveryZone',
            'items',
            'paymentAttempts.reviewedBy',
        ]);

        return OrderResource::make($order)->response();
    }
}
