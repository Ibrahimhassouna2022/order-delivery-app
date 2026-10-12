<?php

namespace App\Http\Requests\Api\V1\Customer;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The workflow itself (active assignment, terminal status) is enforced by
 * CancelOrderAction; this request only asserts owner identity (FR-041).
 */
class CancelOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $order = $this->route('order');

        return $this->user() !== null && $order !== null && $order->customer_id === $this->user()->id;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
