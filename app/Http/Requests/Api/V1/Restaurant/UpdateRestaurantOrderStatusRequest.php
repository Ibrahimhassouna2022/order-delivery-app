<?php

namespace App\Http\Requests\Api\V1\Restaurant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRestaurantOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in([
                'restaurant_accepted',
                'preparing',
                'ready_for_pickup',
            ])],
        ];
    }
}
