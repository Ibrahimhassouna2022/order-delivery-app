<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\DriverLocation;
use App\Models\LocationConsent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Account deletion request (FR-033/FR-035): revokes tokens and active consent,
 * deletes live driver location, and schedules the 30-day purge.
 */
class AccountDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_requesting_deletion_revokes_tokens_consent_and_location(): void
    {
        $user = User::factory()->create(['role' => UserRole::Driver]);
        $token = $user->createToken('api')->plainTextToken;
        LocationConsent::factory()->for($user)->create();
        DriverLocation::factory()->for($user, 'driver')->create();

        $this->withToken($token)
            ->deleteJson('/api/v1/account')
            ->assertNoContent();

        $user->refresh();
        $this->assertTrue($user->isDeletionPending());
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertDatabaseHas('location_consents', [
            'user_id' => $user->id,
            'revoked_at' => $user->deletion_requested_at,
        ]);
        $this->assertDatabaseCount('driver_locations', 0);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'account.deletion_requested',
            'actor_user_id' => $user->id,
        ]);
    }
}
