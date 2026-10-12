<?php

namespace App\Http\Requests\Api\V1\Operations;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexOperationsOrderRequest extends FormRequest
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
            'status' => ['nullable', 'string', Rule::in([
                'placed',
                'restaurant_accepted',
                'preparing',
                'ready_for_pickup',
                'assigned',
                'picked_up',
                'delivered',
                'cancelled',
            ])],
            'restaurant_id' => ['nullable', 'integer', 'exists:restaurants,id'],
            'city' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ];
    }
}
