<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Order\BuildFoodOrderQuoteAction;
use App\Actions\Order\CancelOrderAction;
use App\Actions\Order\DeliveryPricingUnavailableException;
use App\Actions\Order\GetCustomerOrderAction;
use App\Actions\Order\IdempotencyKeyConflictException;
use App\Actions\Order\OrderCancellationConflictException;
use App\Actions\Order\PlaceFoodOrderAction;
use App\Actions\Order\RateDriverAction;
use App\Actions\Order\RateRestaurantAction;
use App\Actions\Order\RatingConflictException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Customer\CancelOrderRequest;
use App\Http\Requests\Api\V1\Customer\PlaceFoodOrderRequest;
use App\Http\Requests\Api\V1\Customer\QuoteFoodOrderRequest;
use App\Http\Requests\Api\V1\Customer\StoreDriverRatingRequest;
use App\Http\Requests\Api\V1\Customer\StoreRestaurantRatingRequest;
use App\Http\Resources\Api\V1\CustomerOrderResource;
use App\Http\Resources\Api\V1\OrderResource;
use App\Http\Resources\Api\V1\OrderTrackingResource;
use App\Http\Resources\Api\V1\RatingResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class CustomerOrderController extends Controller
{
    public function quote(
        QuoteFoodOrderRequest $request,
        BuildFoodOrderQuoteAction $buildQuote,
    ): JsonResponse {
        try {
            return response()->json([
                'data' => $buildQuote->execute($request->user(), $request->validated()),
            ]);
        } catch (DeliveryPricingUnavailableException $exception) {
            return response()->json(
                ['message' => $exception->getMessage()],
                Response::HTTP_SERVICE_UNAVAILABLE,
            );
        }
    }

    public function store(PlaceFoodOrderRequest $request, PlaceFoodOrderAction $placeOrder): JsonResponse
    {
        try {
            ['order' => $order, 'created' => $created] = $placeOrder->execute(
                $request->user(),
                $request->validated(),
            );
        } catch (DeliveryPricingUnavailableException $exception) {
            return response()->json(
                ['message' => $exception->getMessage()],
                Response::HTTP_SERVICE_UNAVAILABLE,
            );
        } catch (IdempotencyKeyConflictException $exception) {
            return response()->json(
                ['message' => $exception->getMessage()],
                Response::HTTP_CONFLICT,
            );
        }

        return CustomerOrderResource::make($order)
            ->response()
            ->setStatusCode($created ? Response::HTTP_CREATED : Response::HTTP_OK);
    }

    public function show(Order $order, GetCustomerOrderAction $getOrder): OrderTrackingResource
    {
        Gate::authorize('view', $order);

        return new OrderTrackingResource($getOrder->execute($order));
    }

    public function cancel(
        CancelOrderRequest $request,
        Order $order,
        CancelOrderAction $cancelOrder,
    ): JsonResponse {
        try {
            $order = $cancelOrder->execute($request->user(), $order);
        } catch (OrderCancellationConflictException $exception) {
            return response()->json(
                ['message' => $exception->getMessage()],
                Response::HTTP_CONFLICT,
            );
        }

        return OrderResource::make($order)->response();
    }

    public function rateRestaurant(
        StoreRestaurantRatingRequest $request,
        Order $order,
        RateRestaurantAction $rateRestaurant,
    ): JsonResponse {
        try {
            $rating = $rateRestaurant->execute(
                $request->user(),
                $order,
                $request->validated('score'),
                $request->validated('comment'),
            );
        } catch (RatingConflictException $exception) {
            return response()->json(
                ['message' => $exception->getMessage()],
                Response::HTTP_CONFLICT,
            );
        }

        return RatingResource::make($rating, 'restaurant_rating')
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function rateDriver(
        StoreDriverRatingRequest $request,
        Order $order,
        RateDriverAction $rateDriver,
    ): JsonResponse {
        try {
            $rating = $rateDriver->execute(
                $request->user(),
                $order,
                $request->validated('score'),
                $request->validated('comment'),
            );
        } catch (RatingConflictException $exception) {
            return response()->json(
                ['message' => $exception->getMessage()],
                Response::HTTP_CONFLICT,
            );
        }

        return RatingResource::make($rating, 'driver_rating')
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
