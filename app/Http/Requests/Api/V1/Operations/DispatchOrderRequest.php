<?php

namespace App\Http\Requests\Api\V1\Operations;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DispatchOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'exclude_driver_user_ids' => ['sometimes', 'array'],
            'exclude_driver_user_ids.*' => [
                'integer',
                Rule::exists('users', 'id')->where('role', 'driver'),
            ],
        ];
    }
}
