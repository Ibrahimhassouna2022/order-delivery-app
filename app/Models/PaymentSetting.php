<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'phone',
    'iban',
    'account_name',
    'support_phone',
    'instructions',
])]
class PaymentSetting extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentSettingFactory> */
    use HasFactory;

    /**
     * The platform keeps a single payment account configuration row.
     */
    public static function current(): self
    {
        return self::query()->first() ?? new self([
            'phone' => null,
            'iban' => null,
            'account_name' => null,
            'support_phone' => null,
            'instructions' => null,
        ]);
    }
}
