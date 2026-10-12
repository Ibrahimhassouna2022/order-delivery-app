<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Restaurant\RegisterSelfServiceRestaurantAction;
use App\Enums\RestaurantStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Restaurant\StoreSelfServiceRestaurantRequest;
use App\Http\Resources\Api\V1\RestaurantResource;
use App\Models\Restaurant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RestaurantController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $restaurants = Restaurant::query()
            ->where('status', RestaurantStatus::Active)
            ->with([
                'menuItems' => fn ($query) => $query
                    ->where('is_available', true)
                    ->orderBy('name')
                    ->orderBy('id'),
            ])
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(20);

        return RestaurantResource::collection($restaurants);
    }

    public function store(
        StoreSelfServiceRestaurantRequest $request,
        RegisterSelfServiceRestaurantAction $registerRestaurant,
    ): JsonResponse {
        ['restaurant' => $restaurant, 'token' => $token] = $registerRestaurant->execute($request->validated());

        return RestaurantResource::make($restaurant)
            ->additional(['token' => $token])
            ->response()
            ->setStatusCode(201);
    }
}
