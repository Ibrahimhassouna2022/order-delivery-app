<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

/**
 * Social (Google/Apple) and guest authentication (FR-001).
 *
 * Google/Apple endpoints are covered with a faked Socialite driver
 * (T040/T045/T047); the real Apple identity-token verification is exercised
 * in Tests\Unit\AppleProviderTest.
 */
class AuthSocialTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_session_issues_a_customer_identity(): void
    {
        $response = $this->postJson('/api/v1/auth/guest');

        $response->assertCreated()
            ->assertJsonPath('data.role', UserRole::Customer->value)
            ->assertJsonStructure([
                'data' => ['id', 'name', 'email', 'role'],
                'token',
            ]);

        $userId = $response->json('data.id');
        $this->assertDatabaseHas('users', [
            'id' => $userId,
            'role' => UserRole::Customer->value,
            'is_guest' => true,
            'email' => null,
            'password' => null,
        ]);

        $this->withToken($response->json('token'))
            ->getJson('/api/v1/auth/user')
            ->assertOk()
            ->assertJsonPath('data.id', $userId);
    }

    public function test_google_sign_in_creates_a_customer_and_issues_a_token(): void
    {
        $this->fakeSocialProvider('google', $this->socialUser([
            'id' => 'google-uid-1',
            'email' => 'google.customer@example.com',
        ]));

        $response = $this->postJson('/api/v1/auth/google', [
            'access_token' => 'provider-identity-token',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Social Customer')
            ->assertJsonPath('data.email', 'google.customer@example.com')
            ->assertJsonPath('data.role', UserRole::Customer->value)
            ->assertJsonStructure([
                'data' => ['id', 'name', 'email', 'role'],
                'token',
            ]);

        $userId = $response->json('data.id');
        $this->assertDatabaseHas('users', [
            'id' => $userId,
            'name' => 'Social Customer',
            'email' => 'google.customer@example.com',
            'is_guest' => false,
            'password' => null,
        ]);

        $this->assertNotNull(User::find($userId)?->email_verified_at);

        $this->withToken($response->json('token'))
            ->getJson('/api/v1/auth/user')
            ->assertOk()
            ->assertJsonPath('data.id', $userId);
    }

    public function test_google_sign_in_claims_an_existing_user_with_matching_email(): void
    {
        $existing = User::factory()->create([
            'name' => 'Registered Customer',
            'email' => 'same.person@example.com',
            'email_verified_at' => null,
            'is_guest' => false,
        ]);

        $this->fakeSocialProvider('google', $this->socialUser([
            'id' => 'google-uid-2',
            'email' => 'same.person@example.com',
        ]));

        $response = $this->postJson('/api/v1/auth/google', [
            'access_token' => 'provider-identity-token',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.id', $existing->id)
            ->assertJsonPath('data.name', 'Registered Customer');

        $this->assertDatabaseCount('users', 1);
    }

    public function test_apple_sign_in_creates_a_customer_and_issues_a_token(): void
    {
        $this->fakeSocialProvider('apple', $this->socialUser([
            'id' => 'apple-sub-1',
            'email' => 'apple.customer@example.com',
        ]));

        $response = $this->postJson('/api/v1/auth/apple', [
            'access_token' => 'provider-identity-token',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Social Customer')
            ->assertJsonPath('data.email', 'apple.customer@example.com')
            ->assertJsonPath('data.role', UserRole::Customer->value)
            ->assertJsonStructure([
                'data' => ['id', 'name', 'email', 'role'],
                'token',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $response->json('data.id'),
            'email' => 'apple.customer@example.com',
            'is_guest' => false,
        ]);
    }

    public function test_social_sign_in_rejects_a_provider_without_a_verified_email(): void
    {
        $this->fakeSocialProvider('google', $this->socialUser(['email' => null]));

        $response = $this->postJson('/api/v1/auth/google', [
            'access_token' => 'provider-identity-token',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath(
                'message',
                'The provider did not return a verified email address to match the account.'
            );

        $this->assertDatabaseCount('users', 0);
    }

    public function test_social_sign_in_rejects_an_invalid_provider_token(): void
    {
        $provider = Mockery::mock(AbstractProvider::class);
        $provider->shouldReceive('userFromToken')
            ->once()
            ->with('provider-identity-token')
            ->andThrow(new \Exception('Invalid provider ID token.'));

        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $response = $this->postJson('/api/v1/auth/google', [
            'access_token' => 'provider-identity-token',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'The identity token could not be verified.');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_social_sign_in_requires_an_access_token(): void
    {
        $response = $this->postJson('/api/v1/auth/google');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('access_token');
    }

    /**
     * Stub the Socialite driver so `userFromToken` returns the given user.
     */
    private function fakeSocialProvider(string $driver, SocialiteUser $user): void
    {
        $provider = Mockery::mock(AbstractProvider::class);
        $provider->shouldReceive('userFromToken')
            ->once()
            ->with('provider-identity-token')
            ->andReturn($user);

        Socialite::shouldReceive('driver')->once()->with($driver)->andReturn($provider);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function socialUser(array $attributes = []): SocialiteUser
    {
        return (new SocialiteUser)->map(array_merge([
            'id' => 'social-id-1',
            'name' => 'Social Customer',
            'email' => 'social@example.com',
            'avatar' => null,
        ], $attributes));
    }
}
