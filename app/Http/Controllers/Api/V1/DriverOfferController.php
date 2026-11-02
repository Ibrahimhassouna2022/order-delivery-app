<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Driver\DriverWorkflowConflictException;
use App\Actions\Driver\ExpireDriverOfferAction;
use App\Actions\Driver\RespondToDriverOfferAction;
use App\Enums\DriverAssignmentStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DriverAssignmentResource;
use App\Models\DriverAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class DriverOfferController extends Controller
{
    public function index(
        Request $request,
        ExpireDriverOfferAction $expireOffer,
    ): AnonymousResourceCollection {
        $staleOffers = DriverAssignment::query()
            ->expired()
            ->get();

        foreach ($staleOffers as $stale) {
            try {
                $expireOffer->execute($stale);
            } catch (\Throwable) {
                // Ignore if cannot be expired
            }
        }

        $offers = DriverAssignment::query()
            ->whereBelongsTo($request->user(), 'driver')
            ->where('status', DriverAssignmentStatus::Offered)
            ->where(fn ($query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>', now()))
            ->with(['order.restaurant', 'order.items'])
            ->orderBy('offered_at')
            ->orderBy('id')
            ->paginate(20);

        return DriverAssignmentResource::collection($offers);
    }

    public function expire(
        Request $request,
        DriverAssignment $driverAssignment,
        ExpireDriverOfferAction $expireOffer,
    ): JsonResponse {
        if ($driverAssignment->driver_user_id !== $request->user()->id) {
            abort(Response::HTTP_FORBIDDEN, 'Unauthorized offer access.');
        }

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

    public function accept(
        Request $request,
        DriverAssignment $driverAssignment,
        RespondToDriverOfferAction $respondToOffer,
    ): JsonResponse {
        return $this->respond($request, $driverAssignment, $respondToOffer, accept: true);
    }

    public function reject(
        Request $request,
        DriverAssignment $driverAssignment,
        RespondToDriverOfferAction $respondToOffer,
    ): JsonResponse {
        return $this->respond($request, $driverAssignment, $respondToOffer, accept: false);
    }

    private function respond(
        Request $request,
        DriverAssignment $driverAssignment,
        RespondToDriverOfferAction $respondToOffer,
        bool $accept,
    ): JsonResponse {
        try {
            ['assignment' => $assignment] = $respondToOffer->execute(
                $request->user(),
                $driverAssignment,
                $accept,
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
