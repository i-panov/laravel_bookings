<?php

use App\Http\Controllers\Api\BookingController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AuthenticateWithApiToken;

Route::middleware(AuthenticateWithApiToken::class)->prefix('/bookings')->group(function() {
    Route::get('/', [BookingController::class, 'index'])->name('api.bookings.index');
    Route::post('/', [BookingController::class, 'create'])->name('api.bookings.create');

    Route::prefix('/{booking}')->group(function() {
        Route::patch('/slots/{slot}', [BookingController::class, 'updateSlot'])->can('update', 'booking')->name('api.bookings.updateSlot');
        Route::post('/slots', [BookingController::class, 'addSlot'])->can('update', 'booking')->name('api.bookings.addSlot');
        Route::delete('/', [BookingController::class, 'destroy'])->can('delete', 'booking')->name('api.bookings.destroy');
    });
});
