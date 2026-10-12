<?php

namespace App\Http\Requests\Api\V1\Restaurant;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Membership scoping is enforced by RestaurantPolicy in the controller; the
 * role middleware already guarantees a restaurant user here.
 */
class IndexRestaurantOrdersRequest extends FormRequest
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
            'restaurant_id' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'between:1,50'],
        ];
    }
}
