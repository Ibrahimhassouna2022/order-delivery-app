<?php

namespace App\Actions\Restaurant;

use App\Actions\Audit\RecordAuditAction;
use App\Enums\RestaurantOnboardingMethod;
use App\Enums\RestaurantStatus;
use App\Enums\UserRole;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class RegisterSelfServiceRestaurantAction
{
    public function __construct(private RecordAuditAction $audit) {}

    /**
     * @param array{
     *   owner_name: string,
     *   email: string,
     *   password: string,
     *   name: string,
     *   address: string,
     *   description?: string|null,
     *   phone?: string|null,
     *   latitude?: float|null,
     *   longitude?: float|null
     * } $validatedData
     * @return array{restaurant: Restaurant, user: User, token: string}
     */
    public function execute(array $validatedData): array
    {
        return DB::transaction(function () use ($validatedData): array {
            $user = new User([
                'name' => $validatedData['owner_name'],
                'email' => $validatedData['email'],
                'password' => $validatedData['password'],
            ]);
            $user->role = UserRole::Restaurant;
            $user->save();

            $restaurant = Restaurant::create([
                ...Arr::only($validatedData, [
                    'name',
                    'description',
                    'address',
                    'phone',
                    'latitude',
                    'longitude',
                ]),
                'status' => RestaurantStatus::Pending,
                'onboarding_method' => RestaurantOnboardingMethod::SelfService,
                'assisted_onboarding_completed_at' => null,
            ]);

            $restaurant->users()->attach($user, ['membership_role' => 'owner']);

            $this->audit->execute('restaurant.created', $restaurant, $user, [
                'onboarding_method' => RestaurantOnboardingMethod::SelfService->value,
            ]);

            return [
                'restaurant' => $restaurant,
                'user' => $user,
                'token' => $user->createToken('api')->plainTextToken,
            ];
        });
    }
}
