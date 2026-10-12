<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Restaurant\CreateMenuItemAction;
use App\Enums\RestaurantStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Restaurant\StoreMenuItemRequest;
use App\Http\Requests\Api\V1\Restaurant\UpdateMenuItemRequest;
use App\Http\Resources\Api\V1\MenuItemResource;
use App\Models\MenuItem;
use App\Models\Restaurant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class RestaurantMenuItemController extends Controller
{
    public function index(Request $request, Restaurant $restaurant): AnonymousResourceCollection
    {
        $isMember = $request->user()?->restaurants()
            ->whereKey($restaurant->getKey())
            ->exists() ?? false;

        abort_if($restaurant->status !== RestaurantStatus::Active && ! $isMember, Response::HTTP_NOT_FOUND);

        $menuItems = $restaurant->menuItems()
            ->when(! $isMember, fn ($query) => $query->where('is_available', true))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(20);

        return MenuItemResource::collection($menuItems);
    }

    public function store(
        StoreMenuItemRequest $request,
        Restaurant $restaurant,
        CreateMenuItemAction $createMenuItem,
    ): JsonResponse {
        $menuItem = $createMenuItem->execute(
            $restaurant,
            $request->safe()->except('photo'),
            $request->file('photo'),
        );

        return MenuItemResource::make($menuItem)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(
        UpdateMenuItemRequest $request,
        Restaurant $restaurant,
        MenuItem $menuItem,
    ): MenuItemResource {
        Gate::authorize('update', $menuItem);
        $menuItem->update($request->validated());

        return MenuItemResource::make($menuItem->refresh());
    }
}
