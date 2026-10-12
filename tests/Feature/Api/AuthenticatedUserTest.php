<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticatedUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_401_when_no_token_is_provided(): void
    {
        $this->getJson('/api/user')
            ->assertUnauthorized();
    }

    public function test_returns_authenticated_user_when_valid_sanctum_token_is_provided(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/user')
            ->assertOk()
            ->assertExactJson([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]);
    }

    public function test_returns_401_when_bearer_token_is_invalid(): void
    {
        $this->withToken('invalid-token')
            ->getJson('/api/user')
            ->assertUnauthorized();
    }
}
