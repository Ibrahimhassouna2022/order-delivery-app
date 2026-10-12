<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\PaymentAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentAttempt>
 */
class PaymentAttemptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'method' => PaymentMethod::CashOnDelivery,
            'provider' => null,
            'status' => PaymentStatus::Pending,
            'amount' => '10.000',
            'currency_code' => 'ILS',
            'provider_reference' => null,
            'idempotency_key' => fake()->uuid(),
            'metadata' => null,
        ];
    }
}
