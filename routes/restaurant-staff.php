<?php

use App\Http\Controllers\restaurant\RestaurantStaffController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'restaurant'])
    ->prefix('restaurant/staff')
    ->name('restaurant.staff.')
    ->group(function () {
        Route::get('/', [RestaurantStaffController::class, 'index'])->name('index');
        Route::post('/', [RestaurantStaffController::class, 'store'])->name('store');
        Route::put('/{staff}', [RestaurantStaffController::class, 'update'])->name('update');
        Route::delete('/{staff}', [RestaurantStaffController::class, 'deactivate'])->name('deactivate');
    });
