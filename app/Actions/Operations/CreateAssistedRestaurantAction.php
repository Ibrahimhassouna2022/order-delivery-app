<?php

namespace App\Actions\Operations;

use App\Actions\Audit\RecordAuditAction;
use App\Enums\RestaurantOnboardingMethod;
use App\Enums\RestaurantStatus;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateAssistedRestaurantAction
{
    public function __construct(private RecordAuditAction $audit) {}

    /**
     * @param array{
     *   name: string,
     *   address: string,
     *   description?: string|null,
     *   phone?: string|null,
     *   latitude?: float|null,
     *   longitude?: float|null,
     *   owner_user_id?: int|null
     * } $validatedData
     */
    public function execute(array $validatedData, ?User $actor = null): Restaurant
    {
        $ownerUserId = isset($validatedData['owner_user_id'])
            ? (int) $validatedData['owner_user_id']
            : null;
        unset($validatedData['owner_user_id']);

        return DB::transaction(function () use ($validatedData, $ownerUserId, $actor): Restaurant {
            $restaurant = Restaurant::create([
                ...$validatedData,
                'status' => RestaurantStatus::Pending,
                'onboarding_method' => RestaurantOnboardingMethod::Assisted,
                'assisted_onboarding_completed_at' => null,
            ]);

            if ($ownerUserId !== null) {
                $restaurant->users()->attach($ownerUserId, ['membership_role' => 'owner']);
            }

            $this->audit->execute('restaurant.created', $restaurant, $actor, [
                'onboarding_method' => RestaurantOnboardingMethod::Assisted->value,
                'owner_user_id' => $ownerUserId,
            ]);

            return $restaurant;
        });
    }
}
