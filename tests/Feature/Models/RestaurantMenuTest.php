<?php

namespace Tests\Feature\Models;

use App\Enums\RestaurantStatus;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RestaurantMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_restaurant_owns_its_menu_items(): void
    {
        $restaurant = Restaurant::create([
            'name' => 'Olive Kitchen',
            'address' => 'Ramallah',
            'latitude' => 31.9,
            'longitude' => 35.2,
        ]);

        $menuItem = $restaurant->menuItems()->create([
            'name' => 'Falafel Plate',
            'price' => '18.500',
            'currency_code' => 'ILS',
        ]);

        $this->assertSame(RestaurantStatus::Pending, $restaurant->fresh()->status);
        $this->assertTrue($restaurant->menuItems->contains($menuItem));
        $this->assertSame('18.500', $menuItem->fresh()->price);
        $this->assertDatabaseHas('menu_items', [
            'id' => $menuItem->id,
            'restaurant_id' => $restaurant->id,
            'currency_code' => 'ILS',
        ]);
    }
}
