<?php

namespace App\Http\Requests\Api\V1\Restaurant;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::Restaurant;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'price' => ['sometimes', 'required', 'numeric', 'gt:0', 'max:999999999.999'],
            'currency_code' => ['sometimes', 'required', 'regex:/^[A-Z]{3}$/'],
            'is_available' => ['sometimes', 'boolean'],
        ];
    }
}
