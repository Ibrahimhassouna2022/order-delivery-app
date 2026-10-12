<?php

namespace App\Models;

use App\Enums\WalletEntryType;
use Database\Factories\DriverWalletLedgerEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'driver_user_id',
    'order_id',
    'delivery_credit_order_id',
    'entry_type',
    'amount',
    'currency_code',
    'idempotency_key',
])]
class DriverWalletLedgerEntry extends Model
{
    /** @use HasFactory<DriverWalletLedgerEntryFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'entry_type' => WalletEntryType::class,
            'amount' => 'decimal:3',
        ];
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_user_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
