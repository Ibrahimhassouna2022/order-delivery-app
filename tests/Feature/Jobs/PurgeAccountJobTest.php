<?php

namespace Tests\Feature\Jobs;

use App\Enums\UserRole;
use App\Enums\WalletEntryType;
use App\Jobs\PurgeAccountJob;
use App\Models\DeliveryLocation;
use App\Models\DriverLocation;
use App\Models\DriverWalletLedgerEntry;
use App\Models\LocationConsent;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Retention purge (FR-035/SC-011): personal data is anonymised after the
 * configured window while order/financial records stay linked but
 * de-identified.
 */
class PurgeAccountJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_within_the_window_personal_data_is_not_purged_yet(): void
    {
        Carbon::setTestNow('2026-10-10 12:00:00');

        $user = User::factory()->create([
            'deletion_requested_at' => now()->subDays(10),
        ]);

        (new PurgeAccountJob($user->id))->handle();

        $this->assertTrue($user->fresh()->isDeletionPending());
        $this->assertNotNull($user->fresh()->email);
    }

    public function test_after_the_window_personal_data_is_purged_but_financial_rows_remain(): void
    {
        Carbon::setTestNow('2026-10-10 12:00:00');

        $user = User::factory()->create(['role' => UserRole::Driver]);
        $user->createToken('api');
        LocationConsent::factory()->for($user)->create();
        DeliveryLocation::factory()->for($user)->create();
        DriverLocation::factory()->create(['driver_user_id' => $user->id]);

        $order = Order::factory()->for($user, 'customer')->create();
        DriverWalletLedgerEntry::factory()->create([
            'driver_user_id' => $user->id,
            'entry_type' => WalletEntryType::DeliveryCredit,
            'amount' => '12.500',
            'idempotency_key' => 'credit-1',
        ]);

        $user->forceFill(['deletion_requested_at' => now()->subDays(31)])->save();

        (new PurgeAccountJob($user->id))->handle();

        $user->refresh();
        $this->assertTrue($user->isAnonymized());
        $this->assertNull($user->email);
        $this->assertSame('Deleted User', $user->name);

        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertDatabaseCount('location_consents', 0);
        $this->assertDatabaseMissing('delivery_locations', ['user_id' => $user->id]);
        $this->assertDatabaseCount('driver_locations', 0);

        // Financial/order records are retained, still de-identified-linked.
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'customer_id' => $user->id]);
        $this->assertDatabaseHas('driver_wallet_ledger_entries', [
            'driver_user_id' => $user->id,
            'amount' => '12.500',
        ]);
    }

    public function test_already_anonymised_or_missing_users_are_skipped(): void
    {
        Carbon::setTestNow('2026-10-10 12:00:00');

        $anonymized = User::factory()->create([
            'deletion_requested_at' => now()->subDays(31),
            'anonymized_at' => now(),
        ]);
        $originalName = $anonymized->name;

        (new PurgeAccountJob($anonymized->id))->handle();
        $this->assertSame($originalName, $anonymized->fresh()->name);

        (new PurgeAccountJob(999_999))->handle();
        $this->addToAssertionCount(1);
    }
}
