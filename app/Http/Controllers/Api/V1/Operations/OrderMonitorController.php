<?php

namespace App\Http\Controllers\Api\V1\Operations;

use App\Actions\Operations\ListOrdersAction;
use App\Actions\Order\CancelOrderAction;
use App\Actions\Order\OrderCancellationConflictException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Operations\IndexOperationsOrderRequest;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class OrderMonitorController extends Controller
{
    public function index(
        IndexOperationsOrderRequest $request,
        ListOrdersAction $listOrders,
    ): AnonymousResourceCollection {
        return OrderResource::collection($listOrders->execute($request->user(), $request->validated()));
    }

    public function cancel(
        Request $request,
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
}
