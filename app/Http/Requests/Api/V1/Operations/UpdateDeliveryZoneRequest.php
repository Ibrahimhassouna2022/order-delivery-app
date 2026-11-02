<?php

namespace App\Http\Requests\Api\V1\Operations;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDeliveryZoneRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $zoneId = $this->route('deliveryZone')?->id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:150', Rule::unique('delivery_zones', 'name')->ignore($zoneId)],
            'fee' => ['sometimes', 'required', 'numeric', 'min:0', 'max:999999999'],
            'currency_code' => ['sometimes', 'string', 'size:3'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:9999'],
        ];
    }
}
