<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationalUserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_driver_account_without_returning_its_password(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $token = $admin->createToken('admin')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson('/api/v1/operations/users', [
                'name' => 'Delivery Driver',
                'email' => 'driver@example.test',
                'role' => 'driver',
                'password' => 'correct-horse-battery',
                'password_confirmation' => 'correct-horse-battery',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Delivery Driver')
            ->assertJsonPath('data.email', 'driver@example.test')
            ->assertJsonPath('data.role', 'driver')
            ->assertJsonMissingPath('data.password');

        $driver = User::where('email', 'driver@example.test')->firstOrFail();

        $this->assertSame(UserRole::Driver, $driver->role);
        $this->assertDatabaseHas('driver_profiles', [
            'user_id' => $driver->id,
            'is_available' => false,
        ]);
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_non_admin_cannot_create_an_operational_account(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);
        $token = $customer->createToken('customer')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/operations/users', [
                'name' => 'Privileged Account',
                'email' => 'privileged@example.test',
                'role' => 'admin',
                'password' => 'correct-horse-battery',
                'password_confirmation' => 'correct-horse-battery',
            ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'privileged@example.test']);
    }

    public function test_admin_cannot_provision_a_customer_through_the_operational_endpoint(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $token = $admin->createToken('admin')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/operations/users', [
                'name' => 'Customer Account',
                'email' => 'customer@example.test',
                'role' => 'customer',
                'password' => 'correct-horse-battery',
                'password_confirmation' => 'correct-horse-battery',
            ])->assertUnprocessable()
            ->assertJsonValidationErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'customer@example.test']);
    }
}
