<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\LocationConsent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverLocationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_consented_driver_can_publish_their_latest_location(): void
    {
        $driver = User::factory()->create(['role' => UserRole::Driver]);
        $token = $driver->createToken('driver')->plainTextToken;
        LocationConsent::factory()->for($driver)->create();
        $payload = [
            'latitude' => '31.9000000',
            'longitude' => '35.2000000',
        ];

        $this->withToken($token)
            ->putJson('/api/v1/driver/location', $payload)
            ->assertCreated()
            ->assertJsonPath('data.latitude', '31.9000000')
            ->assertJsonPath('data.longitude', '35.2000000');

        $this->assertDatabaseHas('driver_locations', [
            'driver_user_id' => $driver->id,
            ...$payload,
        ]);
    }

    public function test_driver_location_sharing_returns_403_without_active_consent(): void
    {
        $driver = User::factory()->create(['role' => UserRole::Driver]);
        $token = $driver->createToken('driver')->plainTextToken;

        $this->withToken($token)
            ->putJson('/api/v1/driver/location', [
                'latitude' => '31.9000000',
                'longitude' => '35.2000000',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('driver_locations', ['driver_user_id' => $driver->id]);
    }
}
