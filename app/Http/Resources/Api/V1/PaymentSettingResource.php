<?php

namespace App\Http\Resources\Api\V1;

use App\Models\PaymentSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentSettingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var PaymentSetting $setting */
        $setting = $this->resource;

        return [
            'phone' => $setting->phone,
            'iban' => $setting->iban,
            'account_name' => $setting->account_name,
            'support_phone' => $setting->support_phone,
            'instructions' => $setting->instructions,
        ];
    }
}
