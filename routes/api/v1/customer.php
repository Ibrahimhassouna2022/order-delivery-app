<?php

use App\Http\Controllers\Api\V1\CustomerDeliveryLocationController;
use App\Http\Controllers\Api\V1\CustomerOrderController;
use App\Http\Controllers\Api\V1\PaymentProofController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'role:customer'])->group(function (): void {
    Route::get('/delivery-locations', [CustomerDeliveryLocationController::class, 'index'])
        ->name('api.v1.delivery-locations.index');
    Route::post('/delivery-locations', [CustomerDeliveryLocationController::class, 'store'])
        ->name('api.v1.delivery-locations.store');
    Route::get('/orders', [CustomerOrderController::class, 'index'])
        ->name('api.v1.orders.index');
    Route::post('/orders/quote', [CustomerOrderController::class, 'quote'])
        ->name('api.v1.orders.quote');
    Route::post('/orders', [CustomerOrderController::class, 'store'])
        ->name('api.v1.orders.store');
    Route::get('/orders/{order}', [CustomerOrderController::class, 'show'])
        ->name('api.v1.orders.show');
    Route::post('/orders/{order}/payment-proof', [PaymentProofController::class, 'store'])
        ->name('api.v1.orders.payment-proof.store');
    Route::post('/orders/{order}/cancel', [CustomerOrderController::class, 'cancel'])
        ->name('api.v1.orders.cancel');
    Route::post('/orders/{order}/restaurant-rating', [CustomerOrderController::class, 'rateRestaurant'])
        ->name('api.v1.orders.restaurant-rating');
    Route::post('/orders/{order}/driver-rating', [CustomerOrderController::class, 'rateDriver'])
        ->name('api.v1.orders.driver-rating');
});
