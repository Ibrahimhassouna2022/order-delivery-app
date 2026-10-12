<?php

namespace App\Actions\Driver;

use App\Enums\WalletEntryType;
use App\Models\DriverWalletLedgerEntry;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class GetDriverWalletAction
{
    /**
     * @return array{
     *   delivery_credits: Collection<int, array{currency_code: string, amount: string}>,
     *   entries: LengthAwarePaginator
     * }
     */
    public function execute(User $driver): array
    {
        $deliveryCredits = DriverWalletLedgerEntry::query()
            ->whereBelongsTo($driver, 'driver')
            ->where('entry_type', WalletEntryType::DeliveryCredit)
            ->selectRaw('currency_code, SUM(amount) as amount')
            ->groupBy('currency_code')
            ->orderBy('currency_code')
            ->get()
            ->map(fn (object $credit): array => [
                'currency_code' => $credit->currency_code,
                'amount' => number_format((float) $credit->amount, 3, '.', ''),
            ]);

        $entries = DriverWalletLedgerEntry::query()
            ->whereBelongsTo($driver, 'driver')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20);

        return [
            'delivery_credits' => $deliveryCredits,
            'entries' => $entries,
        ];
    }
}
