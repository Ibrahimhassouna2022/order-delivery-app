<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Driver\DriverWorkflowConflictException;
use App\Actions\Driver\TransitionDriverAssignmentAction;
use App\Enums\DriverAssignmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DriverAssignmentResource;
use App\Models\DriverAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class DriverAssignmentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $assignments = DriverAssignment::query()
            ->whereBelongsTo($request->user(), 'driver')
            ->whereIn('status', [
                DriverAssignmentStatus::Accepted,
                DriverAssignmentStatus::PickedUp,
                DriverAssignmentStatus::Delivered,
            ])
            ->with(['order.restaurant', 'order.deliveryLocation', 'order.items'])
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(20);

        return DriverAssignmentResource::collection($assignments);
    }

    public function pickup(
        Request $request,
        DriverAssignment $driverAssignment,
        TransitionDriverAssignmentAction $transitionAssignment,
    ): JsonResponse {
        try {
            ['assignment' => $assignment] = $transitionAssignment->pickup(
                $request->user(),
                $driverAssignment,
            );
        } catch (DriverWorkflowConflictException $exception) {
            return response()->json(
                ['message' => $exception->getMessage()],
                Response::HTTP_CONFLICT,
            );
        }

        return DriverAssignmentResource::make($assignment)->response();
    }

    public function deliver(
        Request $request,
        DriverAssignment $driverAssignment,
        TransitionDriverAssignmentAction $transitionAssignment,
    ): JsonResponse {
        try {
            ['assignment' => $assignment] = $transitionAssignment->deliver(
                $request->user(),
                $driverAssignment,
            );
        } catch (DriverWorkflowConflictException $exception) {
            return response()->json(
                ['message' => $exception->getMessage()],
                Response::HTTP_CONFLICT,
            );
        }

        return DriverAssignmentResource::make($assignment)->response();
    }
}
