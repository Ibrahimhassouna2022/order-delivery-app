<?php

namespace Database\Factories;

use App\Models\PaymentSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentSetting>
 */
class PaymentSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'phone' => '+970 59 000 0000',
            'iban' => 'PS92 PALS 0000 0000 0000 0000 0000 0',
            'account_name' => 'Wasalli Delivery',
            'support_phone' => '+970 59 111 1111',
            'instructions' => 'حوّل قيمة الطلب ثم ارفع صورة إشعار التحويل.',
        ];
    }
}
