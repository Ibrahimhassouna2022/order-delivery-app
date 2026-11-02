<?php

use App\Http\Controllers\Api\V1\Operations\DeliveryZoneController;
use App\Http\Controllers\Api\V1\Operations\DriverDispatchController;
use App\Http\Controllers\Api\V1\Operations\OperationalUserController;
use App\Http\Controllers\Api\V1\Operations\OrderMonitorController;
use App\Http\Controllers\Api\V1\Operations\PaymentSettingController;
use App\Http\Controllers\Api\V1\Operations\RestaurantOnboardingController;
use App\Http\Controllers\Api\V1\PaymentReviewController;
use Illuminate\Support\Facades\Route;

Route::post('/operations/users', [OperationalUserController::class, 'store'])
    ->middleware(['auth:sanctum', 'role:admin'])
    ->name('api.v1.operations.users.store');

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
        Route::get('/payment-reviews', [PaymentReviewController::class, 'index'])
            ->name('payment-reviews.index');
        Route::post('/orders/{order}/payment-review', [PaymentReviewController::class, 'store'])
            ->name('orders.payment-review.store');
        Route::get('/payment-settings', [PaymentSettingController::class, 'show'])
            ->name('payment-settings.show');
        Route::put('/payment-settings', [PaymentSettingController::class, 'update'])
            ->name('payment-settings.update');
        Route::get('/delivery-zones', [DeliveryZoneController::class, 'index'])
            ->name('delivery-zones.index');
        Route::post('/delivery-zones', [DeliveryZoneController::class, 'store'])
            ->name('delivery-zones.store');
        Route::patch('/delivery-zones/{deliveryZone}', [DeliveryZoneController::class, 'update'])
            ->name('delivery-zones.update');
        Route::delete('/delivery-zones/{deliveryZone}', [DeliveryZoneController::class, 'destroy'])
            ->name('delivery-zones.destroy');
    });
