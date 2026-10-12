<?php

namespace App\Http\Requests\Api\V1\Customer;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'delivery_location_id' => [
                'required',
                'integer',
                Rule::exists('delivery_locations', 'id')->where('user_id', $this->user()->getAuthIdentifier()),
            ],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*' => ['required', 'array:menu_item_id,quantity'],
            'items.*.menu_item_id' => ['required', 'integer', 'distinct'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000'],
        ];
    }
}
