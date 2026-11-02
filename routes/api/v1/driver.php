<?php

use App\Http\Controllers\Api\V1\DriverAssignmentController;
use App\Http\Controllers\Api\V1\DriverAvailabilityController;
use App\Http\Controllers\Api\V1\DriverLocationController;
use App\Http\Controllers\Api\V1\DriverOfferController;
use App\Http\Controllers\Api\V1\DriverWalletController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'role:driver'])
    ->prefix('driver')
    ->name('api.v1.driver.')
    ->group(function (): void {
        Route::get('/availability', [DriverAvailabilityController::class, 'show'])
            ->name('availability.show');
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
        Route::post('/offers/{driverAssignment}/expire', [DriverOfferController::class, 'expire'])
            ->name('offers.expire');
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
