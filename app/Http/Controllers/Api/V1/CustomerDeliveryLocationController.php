<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Customer\CreateDeliveryLocationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Customer\StoreDeliveryLocationRequest;
use App\Http\Resources\Api\V1\DeliveryLocationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class CustomerDeliveryLocationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $locations = $request->user()->deliveryLocations()
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->get();

        return DeliveryLocationResource::collection($locations);
    }

    public function store(
        StoreDeliveryLocationRequest $request,
        CreateDeliveryLocationAction $createLocation,
    ): JsonResponse {
        $location = $createLocation->execute($request->user(), $request->validated());

        return DeliveryLocationResource::make($location)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
