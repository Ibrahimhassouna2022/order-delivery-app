<?php

namespace App\Actions\Driver;

use App\Actions\Audit\RecordAuditAction;
use App\Actions\Notification\Notifier;
use App\Enums\WalletEntryType;
use App\Models\DriverWalletLedgerEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Completes a driver payout (FR-022): verifies a positive available balance,
 * writes a single `payout_debit` for the full balance, and records the audit
 * trail. Reusing an idempotency key returns the original entry instead of
 * double-debiting.
 */
class CompleteDriverPayoutAction
{
    public function __construct(
        private RecordAuditAction $audit,
        private Notifier $notifier,
    ) {}

    /**
     * @return array{entry: DriverWalletLedgerEntry, created: bool}
     */
    public function execute(User $driver, string $idempotencyKey, string $currencyCode): array
    {
        return DB::transaction(function () use ($driver, $idempotencyKey, $currencyCode): array {
            $existing = DriverWalletLedgerEntry::query()
                ->where('driver_user_id', $driver->id)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing !== null) {
                return [
                    'entry' => $existing,
                    'created' => false,
                ];
            }

            $balance = $this->availableBalance($driver->id, $currencyCode);

            if ($this->toMilliunits($balance) <= 0) {
                throw new DriverWorkflowConflictException('No wallet balance is available to pay out.');
            }

            $entry = DriverWalletLedgerEntry::create([
                'driver_user_id' => $driver->id,
                'entry_type' => WalletEntryType::PayoutDebit,
                'amount' => $balance,
                'currency_code' => $currencyCode,
                'idempotency_key' => $idempotencyKey,
            ]);

            $this->audit->execute('driver.payout_completed', $entry, $driver, [
                'amount' => $balance,
                'currency_code' => $currencyCode,
            ]);

            // Broadcasts after commit so a client never sees a rolled-back payout.
            $this->notifier->driverPayoutCompleted($entry);

            return [
                'entry' => $entry,
                'created' => true,
            ];
        }, attempts: 3);
    }

    /**
     * Credits minus debits (payouts + adjustments) for a currency, in
     * decimal-string form.
     */
    private function availableBalance(int $driverUserId, string $currencyCode): string
    {
        $credits = DriverWalletLedgerEntry::query()
            ->where('driver_user_id', $driverUserId)
            ->where('currency_code', $currencyCode)
            ->where('entry_type', WalletEntryType::DeliveryCredit)
            ->pluck('amount')
            ->reduce(
                fn (int $carry, string $amount): int => $carry + $this->toMilliunits($amount),
                0,
            );

        $debits = DriverWalletLedgerEntry::query()
            ->where('driver_user_id', $driverUserId)
            ->where('currency_code', $currencyCode)
            ->whereIn('entry_type', [WalletEntryType::PayoutDebit, WalletEntryType::Adjustment])
            ->pluck('amount')
            ->reduce(
                fn (int $carry, string $amount): int => $carry + $this->toMilliunits($amount),
                0,
            );

        return $this->fromMilliunits($credits - $debits);
    }

    private function toMilliunits(string $amount): int
    {
        if (! preg_match('/^\d{1,9}(?:\.\d{1,3})?$/D', $amount)) {
            throw new LogicException('Wallet amount must be a decimal with at most three places.');
        }

        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return ((int) $whole * 1000) + (int) str_pad($fraction, 3, '0');
    }

    private function fromMilliunits(int $milliunits): string
    {
        $sign = $milliunits < 0 ? '-' : '';
        $absolute = abs($milliunits);

        return $sign.sprintf('%d.%03d', intdiv($absolute, 1000), $absolute % 1000);
    }
}
