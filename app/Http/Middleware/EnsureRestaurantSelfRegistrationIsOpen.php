<?php

namespace App\Http\Middleware;

use App\Enums\RestaurantOnboardingMethod;
use App\Models\Restaurant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRestaurantSelfRegistrationIsOpen
{
    private const int ASSISTED_ONBOARDING_THRESHOLD = 20;

    public function handle(Request $request, Closure $next): Response
    {
        $assistedRestaurantsCompleted = Restaurant::query()
            ->where('onboarding_method', RestaurantOnboardingMethod::Assisted)
            ->whereNotNull('assisted_onboarding_completed_at')
            ->count();

        abort_if(
            $assistedRestaurantsCompleted < self::ASSISTED_ONBOARDING_THRESHOLD,
            Response::HTTP_FORBIDDEN,
        );

        return $next($request);
    }
}
