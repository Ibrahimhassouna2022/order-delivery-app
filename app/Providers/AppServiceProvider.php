<?php

namespace App\Providers;

use App\Actions\Auth\AppleProvider;
use App\Actions\Notification\Notifier;
use App\Actions\Notification\NullNotifier;
use App\Models\DriverAssignment;
use App\Models\DriverRating;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\RestaurantRating;
use App\Policies\DriverAssignmentPolicy;
use App\Policies\DriverRatingPolicy;
use App\Policies\MenuItemPolicy;
use App\Policies\OrderPolicy;
use App\Policies\RestaurantPolicy;
use App\Policies\RestaurantRatingPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(Notifier::class, function (): Notifier {
            $driver = config('delivery.notifications.driver');
            $class = config('delivery.notifications.drivers.'.$driver);

            return is_string($class) && class_exists($class)
                ? $this->app->make($class)
                : new NullNotifier;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(MenuItem::class, MenuItemPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(DriverAssignment::class, DriverAssignmentPolicy::class);
        Gate::policy(Restaurant::class, RestaurantPolicy::class);
        Gate::policy(RestaurantRating::class, RestaurantRatingPolicy::class);
        Gate::policy(DriverRating::class, DriverRatingPolicy::class);

        RateLimiter::for('auth-login', function (Request $request): Limit {
            $email = Str::lower($request->string('email')->trim()->toString());

            return Limit::perMinute(5)->by($email.'|'.$request->ip());
        });

        RateLimiter::for('auth-register', function (Request $request): Limit {
            return Limit::perHour(5)->by($request->ip());
        });

        Socialite::extend('apple', function (): AppleProvider {
            $config = config('services.apple', []);

            return new AppleProvider(
                request(),
                (string) ($config['client_id'] ?? ''),
                (string) ($config['client_secret'] ?? ''),
                (string) ($config['redirect'] ?? ''),
            );
        });
    }
}
