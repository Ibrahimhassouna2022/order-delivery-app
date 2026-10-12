<?php

namespace App\Actions\Operations;

use App\Actions\Audit\RecordAuditAction;
use App\Enums\RestaurantOnboardingMethod;
use App\Enums\RestaurantStatus;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CompleteAssistedRestaurantAction
{
    public function __construct(private RecordAuditAction $audit) {}

    public function execute(Restaurant $restaurant, ?User $actor = null): Restaurant
    {
        return DB::transaction(function () use ($restaurant, $actor): Restaurant {
            if ($restaurant->status === RestaurantStatus::Active) {
                return $restaurant;
            }

            $restaurant->status = RestaurantStatus::Active;

            if ($restaurant->onboarding_method === RestaurantOnboardingMethod::Assisted) {
                $restaurant->assisted_onboarding_completed_at = now();
            }

            $restaurant->save();

            $this->audit->execute('restaurant.onboarding_completed', $restaurant, $actor, [
                'onboarding_method' => $restaurant->onboarding_method->value,
            ]);

            return $restaurant;
        });
    }
}
