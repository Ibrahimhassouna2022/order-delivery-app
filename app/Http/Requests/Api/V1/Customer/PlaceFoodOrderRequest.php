<?php

namespace App\Http\Requests\Api\V1\Customer;

use Illuminate\Contracts\Validation\ValidationRule;

class PlaceFoodOrderRequest extends QuoteFoodOrderRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'idempotency_key' => $this->header('Idempotency-Key'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'idempotency_key' => ['required', 'string', 'max:255'],
        ];
    }
}
