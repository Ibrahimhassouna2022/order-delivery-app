<?php

namespace App\Http\Resources\Api\V1;

use App\Models\LocationConsent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LocationConsentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var LocationConsent $consent */
        $consent = $this->resource;

        return [
            'id' => $consent->id,
            'is_granted' => $consent->revoked_at === null,
            'granted_at' => $consent->granted_at->toISOString(),
            'revoked_at' => $consent->revoked_at?->toISOString(),
            'policy_version' => $consent->policy_version,
        ];
    }
}
