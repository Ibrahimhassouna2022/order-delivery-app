<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_registers_customer_and_returns_sanctum_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Example Customer',
            'email' => 'customer@example.test',
            'password' => 'correct-horse-battery',
            'password_confirmation' => 'correct-horse-battery',
            'role' => 'admin',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Example Customer')
            ->assertJsonPath('data.email', 'customer@example.test')
            ->assertJsonPath('data.role', 'customer')
            ->assertJsonStructure(['data' => ['id', 'name', 'email', 'role'], 'token']);

        $user = User::where('email', 'customer@example.test')->firstOrFail();

        $this->assertSame(UserRole::Customer, $user->role);
        $this->assertTrue(Hash::check('correct-horse-battery', $user->password));
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_rejects_duplicate_or_malformed_registration_data(): void
    {
        User::factory()->create(['email' => 'customer@example.test']);

        $this->postJson('/api/v1/auth/register', [
            'name' => '',
            'email' => 'customer@example.test',
            'password' => 'short',
            'password_confirmation' => 'different',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_rejects_invalid_login_credentials(): void
    {
        User::factory()->create(['email' => 'customer@example.test']);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'customer@example.test',
            'password' => 'wrong-password',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_limits_repeated_login_failures_for_an_email_and_ip_pair(): void
    {
        User::factory()->create(['email' => 'limited@example.test']);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => 'limited@example.test',
                'password' => 'wrong-password',
            ])->assertUnprocessable();
        }

        $this->postJson('/api/v1/auth/login', [
            'email' => 'limited@example.test',
            'password' => 'wrong-password',
        ])->assertTooManyRequests();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_authenticates_with_valid_credentials(): void
    {
        User::factory()->create([
            'email' => 'customer@example.test',
            'password' => 'correct-horse-battery',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'customer@example.test',
            'password' => 'correct-horse-battery',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.email', 'customer@example.test')
            ->assertJsonPath('data.role', 'customer')
            ->assertJsonStructure(['data' => ['id', 'name', 'email', 'role'], 'token']);

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_logout_revokes_only_the_current_token(): void
    {
        $user = User::factory()->create();
        $currentToken = $user->createToken('current');
        $otherToken = $user->createToken('other');

        $this->withToken($currentToken->plainTextToken)
            ->postJson('/api/v1/auth/logout')
            ->assertNoContent();

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $currentToken->accessToken->id,
        ]);
        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $otherToken->accessToken->id,
        ]);
    }

    public function test_authenticated_user_endpoint_requires_a_valid_token(): void
    {
        $this->getJson('/api/v1/auth/user')
            ->assertUnauthorized();
    }
}
