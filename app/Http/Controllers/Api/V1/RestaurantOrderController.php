<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Driver\DispatchLeastLoadedDriverAction;
use App\Actions\Driver\TransitionDriverAssignmentAction;
use App\Actions\Notification\Notifier;
use App\Actions\Restaurant\AdvanceRestaurantOrderStatusAction;
use App\Actions\Restaurant\ListRestaurantOrdersAction;
use App\Actions\Restaurant\OrderStatusConflictException;
use App\Enums\DriverAssignmentStatus;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Restaurant\IndexRestaurantOrdersRequest;
use App\Http\Requests\Api\V1\Restaurant\UpdateRestaurantOrderStatusRequest;
use App\Http\Resources\Api\V1\DriverAssignmentResource;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\DriverAssignment;
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

        return OrderResource::make($order->load([
            'restaurant',
            'customer',
            'deliveryLocation',
            'deliveryZone',
            'items',
            'paymentAttempts.reviewedBy',
        ]))->response();
    }

    public function dispatch(
        Order $order,
        DispatchLeastLoadedDriverAction $dispatchDriver,
    ): JsonResponse {
        Gate::authorize('manage', $order->restaurant);

        if ($order->activeAssignment !== null && $order->activeAssignment->status === DriverAssignmentStatus::Accepted) {
            return response()->json([
                'message' => 'الطلب مسند ومقبول بالفعل من الكابتن ' . ($order->activeAssignment->driver?->name ?? ''),
            ], Response::HTTP_CONFLICT);
        }

        if ($order->activeAssignment !== null && $order->activeAssignment->isOpen()) {
            return DriverAssignmentResource::make($order->activeAssignment)
                ->response()
                ->setStatusCode(Response::HTTP_OK);
        }

        // Clean up any stale/expired assignments so available drivers can be re-dispatched
        $staleAssignments = DriverAssignment::query()
            ->where('order_id', $order->id)
            ->whereIn('status', [DriverAssignmentStatus::Expired, DriverAssignmentStatus::Rejected])
            ->get();

        foreach ($staleAssignments as $stale) {
            $stale->delete();
        }

        try {
            $assignment = $dispatchDriver->execute($order);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        if ($assignment === null) {
            return response()->json(['message' => 'لا يوجد كابتن توصيل متاح حالياً.'], Response::HTTP_CONFLICT);
        }

        return DriverAssignmentResource::make($assignment)->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function handover(
        Order $order,
        TransitionDriverAssignmentAction $transition,
        Notifier $notifier,
    ): JsonResponse {
        Gate::authorize('manage', $order->restaurant);

        $assignment = $order->activeAssignment;
        if ($assignment !== null && $assignment->status === DriverAssignmentStatus::Accepted) {
            try {
                $transition->pickup($assignment->driver, $assignment);
            } catch (\Throwable) {
                $order->update(['status' => OrderStatus::PickedUp]);
                $notifier->orderStatusChanged($order);
            }
        } else {
            $order->update(['status' => OrderStatus::PickedUp]);
            if ($assignment !== null) {
                $assignment->update([
                    'status' => DriverAssignmentStatus::PickedUp,
                    'picked_up_at' => now(),
                ]);
            }
            $notifier->orderStatusChanged($order);
        }

        return OrderResource::make($order->fresh()->load([
            'restaurant',
            'customer',
            'deliveryLocation',
            'deliveryZone',
            'items',
            'paymentAttempts.reviewedBy',
        ]))->response();
    }
}
