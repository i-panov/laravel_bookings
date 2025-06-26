<?php

use App\Http\Controllers\Api\BookingController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AuthenticateWithApiToken;

Route::middleware(AuthenticateWithApiToken::class)->prefix('/bookings')->group(function() {
    Route::get('/', [BookingController::class, 'index']);
    Route::post('/', [BookingController::class, 'create']);

    Route::prefix('/{booking}')->group(function() {
        Route::patch('/slots/{slot}', [BookingController::class, 'updateSlot'])->can('update', 'booking');
        Route::post('/slots', [BookingController::class, 'addSlot'])->can('update', 'booking');
        Route::delete('/', [BookingController::class, 'destroy'])->can('delete', 'booking');
    });
});
