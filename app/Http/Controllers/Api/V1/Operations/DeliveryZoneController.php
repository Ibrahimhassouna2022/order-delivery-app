<?php

namespace App\Http\Controllers\Api\V1\Operations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Operations\StoreDeliveryZoneRequest;
use App\Http\Requests\Api\V1\Operations\UpdateDeliveryZoneRequest;
use App\Http\Resources\Api\V1\DeliveryZoneResource;
use App\Models\DeliveryZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class DeliveryZoneController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return DeliveryZoneResource::collection(
            DeliveryZone::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        );
    }

    public function store(StoreDeliveryZoneRequest $request): JsonResponse
    {
        $zone = DeliveryZone::query()->create($request->validated());

        return DeliveryZoneResource::make($zone)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(UpdateDeliveryZoneRequest $request, DeliveryZone $deliveryZone): DeliveryZoneResource
    {
        $deliveryZone->update($request->validated());

        return DeliveryZoneResource::make($deliveryZone->refresh());
    }

    public function destroy(DeliveryZone $deliveryZone): JsonResponse
    {
        $deliveryZone->delete();

        return response()->json(status: Response::HTTP_NO_CONTENT);
    }
}
