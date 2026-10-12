<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Location\LocationConsentRequiredException;
use App\Actions\Location\UpdateDriverLocationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Driver\UpdateDriverLocationRequest;
use App\Http\Resources\Api\V1\DriverLocationResource;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class DriverLocationController extends Controller
{
    public function update(
        UpdateDriverLocationRequest $request,
        UpdateDriverLocationAction $updateLocation,
    ): JsonResponse {
        try {
            $location = $updateLocation->execute($request->user(), $request->validated());
        } catch (LocationConsentRequiredException $exception) {
            return response()->json(
                ['message' => $exception->getMessage()],
                Response::HTTP_FORBIDDEN,
            );
        }

        return DriverLocationResource::make($location)->response();
    }
}
