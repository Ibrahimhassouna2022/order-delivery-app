<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\DriverLocation;
use App\Models\LocationConsent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationConsentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_grant_location_consent(): void
    {
        $driver = User::factory()->create(['role' => UserRole::Driver]);
        $token = $driver->createToken('driver')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/location-consents')
            ->assertCreated()
            ->assertJsonPath('data.is_granted', true)
            ->assertJsonPath('data.policy_version', '1');

        $this->assertDatabaseHas('location_consents', [
            'user_id' => $driver->id,
            'policy_version' => '1',
            'revoked_at' => null,
        ]);
        $this->assertSame(1, LocationConsent::query()->where('user_id', $driver->id)->count());
    }

    public function test_repeating_a_grant_returns_the_existing_active_consent(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);
        $token = $customer->createToken('customer')->plainTextToken;

        $firstResponse = $this->withToken($token)->postJson('/api/v1/location-consents');
        $firstResponse->assertCreated();

        $this->withToken($token)
            ->postJson('/api/v1/location-consents')
            ->assertOk()
            ->assertJsonPath('data.id', $firstResponse->json('data.id'));

        $this->assertSame(1, LocationConsent::query()->where('user_id', $customer->id)->count());
    }

    public function test_revoking_driver_consent_stops_tracking_and_removes_the_latest_location(): void
    {
        $driver = User::factory()->create(['role' => UserRole::Driver]);
        $token = $driver->createToken('driver')->plainTextToken;
        $consent = LocationConsent::factory()->for($driver)->create();
        DriverLocation::factory()->create(['driver_user_id' => $driver->id]);

        $this->withToken($token)
            ->deleteJson('/api/v1/location-consents')
            ->assertNoContent();

        $this->assertDatabaseHas('location_consents', [
            'id' => $consent->id,
            'user_id' => $driver->id,
        ]);
        $this->assertNotNull($consent->fresh()->revoked_at);
        $this->assertDatabaseMissing('driver_locations', ['driver_user_id' => $driver->id]);
    }
}
