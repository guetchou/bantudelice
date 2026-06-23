<?php

use App\Http\Middleware\ResolveSiteContext;
use Illuminate\Support\Facades\Route;

Route::middleware([ResolveSiteContext::class, 'module:food', 'throttle:20,1'])
    ->get('t/{guestKey}', [\App\Http\Controllers\GuestOrderTrackingController::class, 'show'])
    ->where('guestKey', '[A-Za-z0-9]{48,128}')
    ->name('track.order.guest');
