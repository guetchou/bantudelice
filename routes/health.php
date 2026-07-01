<?php

use App\Http\Controllers\ModuleHealthController;
use Illuminate\Support\Facades\Route;

Route::get('live', [ModuleHealthController::class, 'live'])->name('health.live');
Route::get('ready', [ModuleHealthController::class, 'ready'])->name('health.ready');
