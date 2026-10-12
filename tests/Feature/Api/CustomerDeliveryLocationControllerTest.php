<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\DeliveryLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerDeliveryLocationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_save_a_delivery_location_owned_by_the_authenticated_user(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);
        $otherCustomer = User::factory()->create(['role' => UserRole::Customer]);
        $token = $customer->createToken('customer')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson('/api/v1/delivery-locations', [
                'label' => 'Home',
                'address' => '12 Main Street, Gaza',
                'latitude' => '31.5017000',
                'longitude' => '34.4668000',
                'delivery_instructions' => 'Call on arrival',
                'user_id' => $otherCustomer->id,
                'is_default' => false,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.label', 'Home')
            ->assertJsonPath('data.address', '12 Main Street, Gaza')
            ->assertJsonPath('data.is_default', true);

        $location = DeliveryLocation::findOrFail($response->json('data.id'));
        $this->assertModelExists($location);
        $this->assertSame($customer->id, $location->user_id);
        $this->assertSame('31.5017000', $location->latitude);
        $this->assertSame('34.4668000', $location->longitude);
        $this->assertDatabaseCount('delivery_locations', 1);
    }

    public function test_customer_only_lists_their_own_delivery_locations(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);
        $ownLocation = DeliveryLocation::factory()->for($customer)->create();
        DeliveryLocation::factory()->create();
        $token = $customer->createToken('customer')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/delivery-locations')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ownLocation->id);
    }

    public function test_only_customers_can_manage_delivery_locations(): void
    {
        $restaurantUser = User::factory()->create(['role' => UserRole::Restaurant]);
        $restaurantToken = $restaurantUser->createToken('restaurant')->plainTextToken;

        $this->withToken($restaurantToken)
            ->postJson('/api/v1/delivery-locations', [
                'label' => 'Home',
                'address' => '12 Main Street, Gaza',
            ])->assertForbidden();

        $this->assertDatabaseCount('delivery_locations', 0);
    }

    public function test_customer_must_submit_valid_coordinates_as_a_pair(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);
        $customerToken = $customer->createToken('customer')->plainTextToken;

        $this->withToken($customerToken)
            ->postJson('/api/v1/delivery-locations', [
                'label' => 'Home',
                'address' => '12 Main Street, Gaza',
                'latitude' => '91',
            ])->assertUnprocessable()
            ->assertJsonValidationErrors(['latitude', 'longitude']);

        $this->assertDatabaseCount('delivery_locations', 0);
    }
}
