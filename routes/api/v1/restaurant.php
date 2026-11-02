<?php

use App\Http\Controllers\Api\V1\PaymentReviewController;
use App\Http\Controllers\Api\V1\RestaurantController;
use App\Http\Controllers\Api\V1\RestaurantMenuItemController;
use App\Http\Controllers\Api\V1\RestaurantOrderController;
use Illuminate\Support\Facades\Route;

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
        Route::post('/orders/{order}/handover', [RestaurantOrderController::class, 'handover'])
            ->name('orders.handover');
        Route::post('/orders/{order}/dispatch', [RestaurantOrderController::class, 'dispatch'])
            ->name('orders.dispatch');
        Route::get('/payment-reviews', [PaymentReviewController::class, 'index'])
            ->name('payment-reviews.index');
        Route::post('/orders/{order}/payment-review', [PaymentReviewController::class, 'store'])
            ->name('orders.payment-review.store');
    });
