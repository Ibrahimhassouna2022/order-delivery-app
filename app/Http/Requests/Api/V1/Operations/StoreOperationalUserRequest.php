<?php

namespace App\Http\Requests\Api\V1\Operations;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreOperationalUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::Admin;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'role' => [
                'required',
                Rule::in([
                    UserRole::Driver->value,
                    UserRole::Restaurant->value,
                    UserRole::Admin->value,
                ]),
            ],
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
    }
}
