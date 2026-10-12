<?php

namespace App\Http\Resources\Api\V1;

use App\Models\DriverLocation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverLocationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var DriverLocation $location */
        $location = $this->resource;

        return [
            'latitude' => $location->latitude,
            'longitude' => $location->longitude,
            'recorded_at' => $location->recorded_at->toISOString(),
        ];
    }
}
