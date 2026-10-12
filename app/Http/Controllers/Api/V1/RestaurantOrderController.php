<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Restaurant\AdvanceRestaurantOrderStatusAction;
use App\Actions\Restaurant\ListRestaurantOrdersAction;
use App\Actions\Restaurant\OrderStatusConflictException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Restaurant\IndexRestaurantOrdersRequest;
use App\Http\Requests\Api\V1\Restaurant\UpdateRestaurantOrderStatusRequest;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class RestaurantOrderController extends Controller
{
    public function index(
        IndexRestaurantOrdersRequest $request,
        ListRestaurantOrdersAction $listOrders,
    ): AnonymousResourceCollection {
        $restaurantId = $request->integer('restaurant_id') ?: null;

        if ($restaurantId !== null) {
            $restaurant = Restaurant::findOrFail($restaurantId);
            Gate::authorize('viewOrders', $restaurant);
        }

        return OrderResource::collection($listOrders->execute(
            $request->user(),
            $restaurantId,
            $request->integer('per_page', 20) ?: 20,
        ));
    }

    public function update(
        UpdateRestaurantOrderStatusRequest $request,
        Order $order,
        AdvanceRestaurantOrderStatusAction $advance,
    ): JsonResponse {
        Gate::authorize('manage', $order->restaurant);

        try {
            $order = $advance->execute($order, $request->validated('status'));
        } catch (OrderStatusConflictException $exception) {
            return response()->json(
                ['message' => $exception->getMessage()],
                Response::HTTP_CONFLICT,
            );
        }

        return OrderResource::make($order)->response();
    }
}
