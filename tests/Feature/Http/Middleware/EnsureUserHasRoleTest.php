<?php

namespace Tests\Feature\Http\Middleware;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class EnsureUserHasRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/api/_test/driver', fn () => response()->json(['allowed' => true]))
            ->middleware(['auth:sanctum', 'role:driver']);
        Route::get('/api/_test/invalid-role', fn () => response()->json(['allowed' => true]))
            ->middleware(['auth:sanctum', 'role:not-a-role']);
    }

    public function test_matching_role_can_access_protected_route(): void
    {
        $driver = User::factory()->create(['role' => UserRole::Driver]);
        $token = $driver->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/_test/driver')
            ->assertOk()
            ->assertExactJson(['allowed' => true]);
    }

    public function test_customer_receives_403_from_driver_route(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);
        $token = $customer->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/_test/driver')
            ->assertForbidden();
    }

    public function test_returns_401_when_role_route_has_no_authenticated_user(): void
    {
        $this->getJson('/api/_test/driver')
            ->assertUnauthorized();
    }

    public function test_invalid_role_parameter_fails_closed(): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin]);
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/_test/invalid-role')
            ->assertForbidden();
    }
}
