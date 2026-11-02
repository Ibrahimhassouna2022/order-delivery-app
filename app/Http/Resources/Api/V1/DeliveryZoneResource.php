<?php

namespace App\Http\Resources\Api\V1;

use App\Models\DeliveryZone;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryZoneResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var DeliveryZone $zone */
        $zone = $this->resource;

        return [
            'id' => $zone->id,
            'name' => $zone->name,
            'fee' => $zone->fee,
            'estimated_time' => $zone->estimated_time ?? match ($zone->name) {
                'الرمال' => '15 - 20 دقيقة',
                'تل الهوا' => '20 - 25 دقيقة',
                'الشيخ رضوان' => '20 - 25 دقيقة',
                'التفاح' => '20 - 25 دقيقة',
                'الزيتون' => '25 - 30 دقيقة',
                'الشجاعية' => '25 - 30 دقيقة',
                'جباليا' => '30 - 35 دقيقة',
                'خان يونس' => '40 - 50 دقيقة',
                'رفح' => '50 - 60 دقيقة',
                default => '20 - 30 دقيقة',
            },
            'currency_code' => $zone->currency_code,
            'is_active' => $zone->is_active,
            'sort_order' => $zone->sort_order,
        ];
    }
}
