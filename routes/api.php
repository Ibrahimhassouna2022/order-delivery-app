<?php

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\DeliveryZoneController;
use App\Http\Controllers\Api\V1\LocationConsentController;
use App\Http\Controllers\Api\V1\PaymentSettingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user()->only(['id', 'name', 'email']);
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function (): void {
    require __DIR__.'/api/v1/auth.php';

    Route::post('/location-consents', [LocationConsentController::class, 'store'])
        ->middleware('auth:sanctum')
        ->name('api.v1.location-consents.store');
    Route::delete('/location-consents', [LocationConsentController::class, 'destroy'])
        ->middleware('auth:sanctum')
        ->name('api.v1.location-consents.destroy');

    Route::delete('/account', [AccountController::class, 'destroy'])
        ->middleware('auth:sanctum')
        ->name('api.v1.account.destroy');

    Route::get('/payment-settings', [PaymentSettingController::class, 'show'])
        ->middleware('auth:sanctum')
        ->name('api.v1.payment-settings.show');
    Route::get('/delivery-zones', [DeliveryZoneController::class, 'index'])
        ->name('api.v1.delivery-zones.index');

    require __DIR__.'/api/v1/customer.php';
    require __DIR__.'/api/v1/driver.php';
    require __DIR__.'/api/v1/restaurant.php';
    require __DIR__.'/api/v1/operations.php';
});
