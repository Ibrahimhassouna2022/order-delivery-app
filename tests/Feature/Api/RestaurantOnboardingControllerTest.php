<?php

namespace Tests\Feature\Api;

use App\Enums\RestaurantStatus;
use App\Enums\UserRole;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RestaurantOnboardingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_an_assisted_restaurant_for_a_restaurant_user(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $owner = User::factory()->create(['role' => UserRole::Restaurant]);
        $token = $admin->createToken('admin')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson('/api/v1/operations/restaurants', [
                'name' => 'Olive Kitchen',
                'address' => 'Ramallah',
                'owner_user_id' => $owner->id,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Olive Kitchen')
            ->assertJsonPath('data.status', 'pending');

        $restaurant = Restaurant::where('name', 'Olive Kitchen')->firstOrFail();

        $this->assertNull($restaurant->assisted_onboarding_completed_at);
        $this->assertDatabaseHas('restaurant_user', [
            'restaurant_id' => $restaurant->id,
            'user_id' => $owner->id,
        ]);
    }

    public function test_self_service_registration_is_unavailable_before_twenty_assisted_completions(): void
    {
        Restaurant::factory()
            ->count(19)
            ->create(['assisted_onboarding_completed_at' => now()]);

        $this->postJson('/api/v1/restaurants', [
            'name' => 'New Restaurant',
            'address' => 'Ramallah',
            'email' => 'owner@example.test',
            'owner_name' => 'Restaurant Owner',
            'password' => 'correct-horse-battery',
            'password_confirmation' => 'correct-horse-battery',
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'owner@example.test']);
        $this->assertDatabaseCount('restaurants', 19);
    }

    public function test_self_service_registration_opens_after_twenty_assisted_completions(): void
    {
        Restaurant::factory()
            ->count(20)
            ->create(['assisted_onboarding_completed_at' => now()]);

        $response = $this->postJson('/api/v1/restaurants', [
            'name' => 'New Restaurant',
            'address' => 'Ramallah',
            'email' => 'owner@example.test',
            'owner_name' => 'Restaurant Owner',
            'password' => 'correct-horse-battery',
            'password_confirmation' => 'correct-horse-battery',
            'status' => 'active',
            'assisted_onboarding_completed_at' => now()->toISOString(),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'New Restaurant')
            ->assertJsonPath('data.status', 'pending');

        $owner = User::where('email', 'owner@example.test')->firstOrFail();
        $restaurant = Restaurant::where('name', 'New Restaurant')->firstOrFail();

        $this->assertSame(UserRole::Restaurant, $owner->role);
        $this->assertTrue(Hash::check('correct-horse-battery', $owner->password));
        $this->assertNull($restaurant->assisted_onboarding_completed_at);
        $this->assertSame('self_service', $restaurant->onboarding_method->value);
        $this->assertDatabaseHas('restaurant_user', [
            'restaurant_id' => $restaurant->id,
            'user_id' => $owner->id,
        ]);
    }

    public function test_admin_completes_assisted_onboarding_and_activates_the_restaurant(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $restaurant = Restaurant::factory()->create(['status' => RestaurantStatus::Pending]);
        $token = $admin->createToken('admin')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson("/api/v1/operations/restaurants/{$restaurant->id}/complete-onboarding");

        $response->assertOk()
            ->assertJsonPath('data.status', 'active');

        $this->assertNotNull($restaurant->fresh()->assisted_onboarding_completed_at);
    }
}
