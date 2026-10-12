<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Location\GrantLocationConsentAction;
use App\Actions\Location\RevokeLocationConsentAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\LocationConsentResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LocationConsentController extends Controller
{
    public function store(Request $request, GrantLocationConsentAction $grantConsent): JsonResponse
    {
        ['consent' => $consent, 'created' => $created] = $grantConsent->execute($request->user());

        return LocationConsentResource::make($consent)
            ->response()
            ->setStatusCode($created ? Response::HTTP_CREATED : Response::HTTP_OK);
    }

    public function destroy(Request $request, RevokeLocationConsentAction $revokeConsent): JsonResponse
    {
        $revokeConsent->execute($request->user());

        return response()->json(status: Response::HTTP_NO_CONTENT);
    }
}
