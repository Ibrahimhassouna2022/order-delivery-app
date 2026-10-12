<?php

namespace Tests\Feature\Api;

use App\Enums\RestaurantStatus;
use App\Enums\UserRole;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RestaurantMenuControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_restaurant_user_can_add_a_menu_item_to_their_restaurant(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create(['role' => UserRole::Restaurant]);
        $restaurant = Restaurant::factory()->create(['status' => RestaurantStatus::Active]);
        $restaurant->users()->attach($owner, ['membership_role' => 'owner']);
        $token = $owner->createToken('restaurant')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson("/api/v1/restaurants/{$restaurant->id}/menu-items", [
                'name' => 'Falafel Plate',
                'description' => 'Fresh falafel with salad',
                'price' => '18.500',
                'currency_code' => 'ILS',
                'is_available' => true,
                'photo' => UploadedFile::fake()->image('falafel.jpg'),
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Falafel Plate')
            ->assertJsonPath('data.price', '18.500');

        $menuItem = MenuItem::where('name', 'Falafel Plate')->firstOrFail();

        $this->assertNotNull($response->json('data.photo_url'));
        $this->assertDatabaseHas('menu_items', [
            'restaurant_id' => $restaurant->id,
            'name' => 'Falafel Plate',
            'currency_code' => 'ILS',
        ]);
        Storage::disk('public')->assertExists($menuItem->photo_path);
    }

    public function test_restaurant_user_cannot_edit_another_restaurants_menu_item(): void
    {
        $owner = User::factory()->create(['role' => UserRole::Restaurant]);
        $ownRestaurant = Restaurant::factory()->create();
        $ownRestaurant->users()->attach($owner);
        $ownItem = MenuItem::factory()->for($ownRestaurant)->create();
        $otherRestaurant = Restaurant::factory()->create();
        $item = MenuItem::factory()->for($otherRestaurant)->create();
        $token = $owner->createToken('restaurant')->plainTextToken;

        $this->withToken($token)
            ->patchJson("/api/v1/restaurants/{$ownRestaurant->id}/menu-items/{$ownItem->id}", [
                'name' => 'Updated Item',
            ])->assertOk();

        $this->withToken($token)
            ->patchJson("/api/v1/restaurants/{$otherRestaurant->id}/menu-items/{$item->id}", [
                'name' => 'Tampered Item',
            ])->assertNotFound();

        $this->assertDatabaseHas('menu_items', [
            'id' => $item->id,
            'name' => $item->name,
        ]);
    }

    public function test_rejects_unsupported_menu_photo_formats(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create(['role' => UserRole::Restaurant]);
        $restaurant = Restaurant::factory()->create();
        $restaurant->users()->attach($owner);
        $token = $owner->createToken('restaurant')->plainTextToken;

        $this->withToken($token)
            ->postJson("/api/v1/restaurants/{$restaurant->id}/menu-items", [
                'name' => 'Unsafe Upload',
                'price' => '18.500',
                'currency_code' => 'ILS',
                'photo' => UploadedFile::fake()->create('dish.svg', 10, 'image/svg+xml'),
            ])->assertUnprocessable()
            ->assertJsonValidationErrors('photo');

        $this->assertDatabaseMissing('menu_items', [
            'restaurant_id' => $restaurant->id,
            'name' => 'Unsafe Upload',
        ]);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_customers_only_browse_active_restaurants_and_available_menu_items(): void
    {
        $activeRestaurant = Restaurant::factory()->create(['status' => RestaurantStatus::Active]);
        $availableItem = MenuItem::factory()->for($activeRestaurant)->create(['is_available' => true]);
        MenuItem::factory()->for($activeRestaurant)->create(['is_available' => false]);
        $pendingRestaurant = Restaurant::factory()->create(['status' => RestaurantStatus::Pending]);
        MenuItem::factory()->for($pendingRestaurant)->create();

        $this->getJson('/api/v1/restaurants')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $activeRestaurant->id)
            ->assertJsonPath('data.0.menu_items.0.id', $availableItem->id);
    }
}
