<?php

namespace App\Http\Resources\Api\V1;

use App\Models\DeliveryLocation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryLocationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var DeliveryLocation $location */
        $location = $this->resource;

        return [
            'id' => $location->id,
            'label' => $location->label,
            'address' => $location->address,
            'latitude' => $location->latitude,
            'longitude' => $location->longitude,
            'delivery_instructions' => $location->delivery_instructions,
            'is_default' => $location->is_default,
        ];
    }
}
