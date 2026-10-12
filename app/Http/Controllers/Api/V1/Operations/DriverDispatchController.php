<?php

namespace App\Http\Controllers\Api\V1\Operations;

use App\Actions\Driver\CreateDriverOfferAction;
use App\Actions\Driver\DispatchClosestDriverAction;
use App\Actions\Driver\DriverWorkflowConflictException;
use App\Actions\Driver\ExpireDriverOfferAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Operations\DispatchOrderRequest;
use App\Http\Requests\Api\V1\Operations\StoreDriverOfferRequest;
use App\Http\Resources\Api\V1\DriverAssignmentResource;
use App\Models\DriverAssignment;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class DriverDispatchController extends Controller
{
    public function store(
        StoreDriverOfferRequest $request,
        Order $order,
        CreateDriverOfferAction $createOffer,
    ): JsonResponse {
        try {
            ['assignment' => $assignment, 'created' => $created] = $createOffer->execute(
                $order,
                $request->validated(),
            );
        } catch (DriverWorkflowConflictException $exception) {
            return response()->json(
                ['message' => $exception->getMessage()],
                Response::HTTP_CONFLICT,
            );
        }

        return DriverAssignmentResource::make($assignment)
            ->response()
            ->setStatusCode($created ? Response::HTTP_CREATED : Response::HTTP_OK);
    }

    public function expire(
        DriverAssignment $driverAssignment,
        ExpireDriverOfferAction $expireOffer,
    ): JsonResponse {
        try {
            $assignment = $expireOffer->execute($driverAssignment);
        } catch (DriverWorkflowConflictException $exception) {
            return response()->json(
                ['message' => $exception->getMessage()],
                Response::HTTP_CONFLICT,
            );
        }

        return DriverAssignmentResource::make($assignment)->response();
    }

    public function dispatch(
        DispatchOrderRequest $request,
        Order $order,
        DispatchClosestDriverAction $dispatchClosestDriver,
    ): JsonResponse {
        $assignment = $dispatchClosestDriver->execute(
            $order,
            $request->validated('exclude_driver_user_ids', []),
        );

        if ($assignment === null) {
            return response()->json(
                ['message' => 'No available driver could be found for this order.'],
                Response::HTTP_CONFLICT,
            );
        }

        return DriverAssignmentResource::make($assignment)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
