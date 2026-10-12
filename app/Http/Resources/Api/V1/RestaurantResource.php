<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RestaurantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Restaurant $restaurant */
        $restaurant = $this->resource;

        return [
            'id' => $restaurant->id,
            'name' => $restaurant->name,
            'description' => $restaurant->description,
            'address' => $restaurant->address,
            'phone' => $restaurant->phone,
            'latitude' => $restaurant->latitude,
            'longitude' => $restaurant->longitude,
            'status' => $restaurant->status->value,
            'menu_items' => MenuItemResource::collection($this->whenLoaded('menuItems')),
        ];
    }
}
