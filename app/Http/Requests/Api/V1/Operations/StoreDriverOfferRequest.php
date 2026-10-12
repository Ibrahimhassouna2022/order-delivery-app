<?php

namespace App\Http\Requests\Api\V1\Operations;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDriverOfferRequest extends FormRequest
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
            'driver_user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('role', 'driver'),
            ],
            'distance_meters' => ['required', 'integer', 'between:0,4294967295'],
            'fare' => [
                'required',
                'numeric',
                'gt:0',
                'max:999999999.999',
                'regex:/^\d{1,9}(?:\.\d{1,3})?$/D',
            ],
        ];
    }
}
