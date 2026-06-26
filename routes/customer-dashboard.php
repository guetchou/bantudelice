<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('dashboard', 'ProfileController@profile')->name('user.dashboard');
    Route::get('dashboard/profile', 'ProfileController@profile')->name('user.dashboard.profile');
});
