<?php

namespace App\Http\Resources\Api\V1;

use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class MenuItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var MenuItem $menuItem */
        $menuItem = $this->resource;

        return [
            'id' => $menuItem->id,
            'name' => $menuItem->name,
            'description' => $menuItem->description,
            'price' => $menuItem->price,
            'currency_code' => $menuItem->currency_code,
            'photo_url' => $menuItem->photo_path === null
                ? null
                : Storage::disk('public')->url($menuItem->photo_path),
            'is_available' => $menuItem->is_available,
        ];
    }
}
