<?php

namespace App\Http\Requests\Api\V1\Customer;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class QuoteFoodOrderRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'restaurant_id' => [
                'required',
                'integer',
            ],
            'fulfillment_type' => [
                'sometimes',
                'string',
                Rule::in(['delivery', 'pickup']),
            ],
            'delivery_location_id' => [
                'nullable',
                'integer',
                Rule::exists('delivery_locations', 'id')->where('user_id', $this->user()->getAuthIdentifier()),
            ],
            'delivery_zone_id' => [
                'nullable',
                'integer',
                Rule::exists('delivery_zones', 'id')->where('is_active', true),
            ],
            'delivery_address' => ['nullable', 'string', 'max:500', 'required_with:delivery_zone_id'],
            'delivery_phone' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['sometimes', 'string', 'max:50'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*' => ['required', 'array:menu_item_id,quantity'],
            'items.*.menu_item_id' => ['required', 'integer', 'distinct'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $fulfillmentType = $this->input('fulfillment_type', 'delivery');

                if ($fulfillmentType !== 'delivery') {
                    return;
                }

                if ($this->input('delivery_location_id') === null && $this->input('delivery_zone_id') === null) {
                    $validator->errors()->add(
                        'delivery_zone_id',
                        'A delivery zone or saved delivery location is required.',
                    );
                }
            },
        ];
    }
}
