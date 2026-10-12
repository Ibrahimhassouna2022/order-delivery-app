<?php

namespace App\Http\Requests\Api\V1\Restaurant;

use App\Models\MenuItem;
use Illuminate\Foundation\Http\FormRequest;

class StoreMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', [
            MenuItem::class,
            $this->route('restaurant'),
        ]) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'numeric', 'gt:0', 'max:999999999.999'],
            'currency_code' => ['required', 'regex:/^[A-Z]{3}$/'],
            'is_available' => ['sometimes', 'boolean'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}
