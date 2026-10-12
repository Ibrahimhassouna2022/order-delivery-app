<?php

namespace App\Http\Controllers\Api\V1\Operations;

use App\Actions\Operations\CompleteAssistedRestaurantAction;
use App\Actions\Operations\CreateAssistedRestaurantAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Operations\StoreAssistedRestaurantRequest;
use App\Http\Resources\Api\V1\RestaurantResource;
use App\Models\Restaurant;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class RestaurantOnboardingController extends Controller
{
    public function store(
        StoreAssistedRestaurantRequest $request,
        CreateAssistedRestaurantAction $createRestaurant,
    ): JsonResponse {
        $restaurant = $createRestaurant->execute($request->validated(), $request->user());

        return RestaurantResource::make($restaurant)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function complete(
        Restaurant $restaurant,
        CompleteAssistedRestaurantAction $completeOnboarding,
    ): RestaurantResource {
        return RestaurantResource::make($completeOnboarding->execute($restaurant));
    }
}
