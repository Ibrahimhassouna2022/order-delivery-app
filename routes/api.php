<?php

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CustomerDeliveryLocationController;
use App\Http\Controllers\Api\V1\CustomerOrderController;
use App\Http\Controllers\Api\V1\DriverAssignmentController;
use App\Http\Controllers\Api\V1\DriverAvailabilityController;
use App\Http\Controllers\Api\V1\DriverLocationController;
use App\Http\Controllers\Api\V1\DriverOfferController;
use App\Http\Controllers\Api\V1\DriverWalletController;
use App\Http\Controllers\Api\V1\LocationConsentController;
use App\Http\Controllers\Api\V1\Operations\DriverDispatchController;
use App\Http\Controllers\Api\V1\Operations\OperationalUserController;
use App\Http\Controllers\Api\V1\Operations\OrderMonitorController;
use App\Http\Controllers\Api\V1\Operations\RestaurantOnboardingController;
use App\Http\Controllers\Api\V1\RestaurantController;
use App\Http\Controllers\Api\V1\RestaurantMenuItemController;
use App\Http\Controllers\Api\V1\RestaurantOrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user()->only(['id', 'name', 'email']);
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function (): void {
    Route::prefix('auth')->name('api.v1.auth.')->group(function (): void {
        Route::post('/register', [AuthController::class, 'register'])
            ->middleware('throttle:auth-register')
            ->name('register');
        Route::post('/login', [AuthController::class, 'login'])
            ->middleware('throttle:auth-login')
            ->name('login');
        Route::post('/guest', [AuthController::class, 'guest'])
            ->middleware('throttle:auth-register')
            ->name('guest');
        Route::post('/google', [AuthController::class, 'google'])
            ->middleware('throttle:auth-register')
            ->name('google');
        Route::post('/apple', [AuthController::class, 'apple'])
            ->middleware('throttle:auth-register')
            ->name('apple');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
            Route::get('/user', [AuthController::class, 'user'])->name('user');
        });
    });

    Route::post('/location-consents', [LocationConsentController::class, 'store'])
        ->middleware('auth:sanctum')
        ->name('api.v1.location-consents.store');
    Route::delete('/location-consents', [LocationConsentController::class, 'destroy'])
        ->middleware('auth:sanctum')
        ->name('api.v1.location-consents.destroy');

    Route::delete('/account', [AccountController::class, 'destroy'])
        ->middleware('auth:sanctum')
        ->name('api.v1.account.destroy');

    Route::middleware(['auth:sanctum', 'role:customer'])->group(function (): void {
        Route::get('/delivery-locations', [CustomerDeliveryLocationController::class, 'index'])
            ->name('api.v1.delivery-locations.index');
        Route::post('/delivery-locations', [CustomerDeliveryLocationController::class, 'store'])
            ->name('api.v1.delivery-locations.store');
        Route::post('/orders/quote', [CustomerOrderController::class, 'quote'])
            ->name('api.v1.orders.quote');
        Route::post('/orders', [CustomerOrderController::class, 'store'])
            ->name('api.v1.orders.store');
        Route::get('/orders/{order}', [CustomerOrderController::class, 'show'])
            ->name('api.v1.orders.show');
        Route::post('/orders/{order}/cancel', [CustomerOrderController::class, 'cancel'])
            ->name('api.v1.orders.cancel');
        Route::post('/orders/{order}/restaurant-rating', [CustomerOrderController::class, 'rateRestaurant'])
            ->name('api.v1.orders.restaurant-rating');
        Route::post('/orders/{order}/driver-rating', [CustomerOrderController::class, 'rateDriver'])
            ->name('api.v1.orders.driver-rating');
    });

    Route::middleware(['auth:sanctum', 'role:driver'])
        ->prefix('driver')
        ->name('api.v1.driver.')
        ->group(function (): void {
            Route::put('/availability', [DriverAvailabilityController::class, 'update'])
                ->name('availability.update');
            Route::put('/location', [DriverLocationController::class, 'update'])
                ->name('location.update');
            Route::get('/offers', [DriverOfferController::class, 'index'])
                ->name('offers.index');
            Route::post('/offers/{driverAssignment}/accept', [DriverOfferController::class, 'accept'])
                ->name('offers.accept');
            Route::post('/offers/{driverAssignment}/reject', [DriverOfferController::class, 'reject'])
                ->name('offers.reject');
            Route::get('/assignments', [DriverAssignmentController::class, 'index'])
                ->name('assignments.index');
            Route::post('/assignments/{driverAssignment}/pickup', [DriverAssignmentController::class, 'pickup'])
                ->name('assignments.pickup');
            Route::post('/assignments/{driverAssignment}/deliver', [DriverAssignmentController::class, 'deliver'])
                ->name('assignments.deliver');
            Route::get('/wallet', [DriverWalletController::class, 'show'])
                ->name('wallet.show');
            Route::post('/payouts', [DriverWalletController::class, 'payout'])
                ->name('payouts.store');
        });

    Route::post('/operations/users', [OperationalUserController::class, 'store'])
        ->middleware(['auth:sanctum', 'role:admin'])
        ->name('api.v1.operations.users.store');

    Route::get('/restaurants', [RestaurantController::class, 'index'])
        ->name('api.v1.restaurants.index');
    Route::post('/restaurants', [RestaurantController::class, 'store'])
        ->middleware(['restaurant.self-registration', 'throttle:auth-register'])
        ->name('api.v1.restaurants.store');

    Route::get('/restaurants/{restaurant}/menu-items', [RestaurantMenuItemController::class, 'index'])
        ->name('api.v1.restaurants.menu-items.index');
    Route::post('/restaurants/{restaurant}/menu-items', [RestaurantMenuItemController::class, 'store'])
        ->middleware(['auth:sanctum', 'role:restaurant'])
        ->name('api.v1.restaurants.menu-items.store');
    Route::patch('/restaurants/{restaurant}/menu-items/{menuItem}', [RestaurantMenuItemController::class, 'update'])
        ->scopeBindings()
        ->middleware(['auth:sanctum', 'role:restaurant'])
        ->name('api.v1.restaurants.menu-items.update');

    Route::middleware(['auth:sanctum', 'role:restaurant'])
        ->prefix('restaurant')
        ->name('api.v1.restaurant.')
        ->group(function (): void {
            Route::get('/orders', [RestaurantOrderController::class, 'index'])
                ->name('orders.index');
            Route::patch('/orders/{order}/status', [RestaurantOrderController::class, 'update'])
                ->name('orders.status.update');
        });

    Route::prefix('operations')
        ->name('api.v1.operations.')
        ->middleware(['auth:sanctum', 'role:admin'])
        ->group(function (): void {
            Route::post('/orders/{order}/offers', [DriverDispatchController::class, 'store'])
                ->name('orders.offers.store');
            Route::post('/orders/{order}/dispatch', [DriverDispatchController::class, 'dispatch'])
                ->name('orders.dispatch');
            Route::post('/driver-assignments/{driverAssignment}/expire', [DriverDispatchController::class, 'expire'])
                ->name('driver-assignments.expire');
            Route::post('/restaurants', [RestaurantOnboardingController::class, 'store'])
                ->name('restaurants.store');
            Route::post('/restaurants/{restaurant}/complete-onboarding', [
                RestaurantOnboardingController::class,
                'complete',
            ])->name('restaurants.complete');
            Route::get('/orders', [OrderMonitorController::class, 'index'])
                ->name('orders.index');
            Route::post('/orders/{order}/cancel', [OrderMonitorController::class, 'cancel'])
                ->name('orders.cancel');
        });
});
