<?php

use App\Http\Controllers\api\Transport\TransportBookingController;
use App\Http\Middleware\ResolveSiteContext;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', ResolveSiteContext::class, 'module:transport'])->group(function () {
    Route::post('transport/xhr/bookings/{id}/cancel', [TransportBookingController::class, 'cancel'])
        ->name('transport.xhr.bookings.cancel');
});
