<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Shape for a recorded rating (FR-013). Distinguishes restaurant vs driver
 * activity for the customer's history feed.
 */
class RatingResource extends JsonResource
{
    public function __construct(mixed $resource, public string $activityType)
    {
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'activity_type' => $this->activityType,
            'score' => $this->resource->score,
            'comment' => $this->resource->comment,
            'created_at' => $this->resource->created_at?->toISOString(),
        ];
    }
}
