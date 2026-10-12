<?php

namespace App\Http\Requests\Api\V1\Operations;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssistedRestaurantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::Admin;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'address' => ['required', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:32'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'owner_user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('role', UserRole::Restaurant->value),
            ],
        ];
    }
}
