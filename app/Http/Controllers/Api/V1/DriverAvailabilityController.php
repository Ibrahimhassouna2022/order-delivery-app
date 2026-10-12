<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Driver\DriverWorkflowConflictException;
use App\Actions\Driver\SetDriverAvailabilityAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Driver\SetDriverAvailabilityRequest;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class DriverAvailabilityController extends Controller
{
    public function update(
        SetDriverAvailabilityRequest $request,
        SetDriverAvailabilityAction $setAvailability,
    ): JsonResponse {
        try {
            $profile = $setAvailability->execute(
                $request->user(),
                $request->boolean('is_available'),
            );
        } catch (DriverWorkflowConflictException $exception) {
            return response()->json(
                ['message' => $exception->getMessage()],
                Response::HTTP_CONFLICT,
            );
        }

        return response()->json([
            'data' => [
                'is_available' => $profile->is_available,
                'availability_updated_at' => $profile->availability_updated_at?->toISOString(),
            ],
        ]);
    }
}
