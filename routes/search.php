<?php

use App\Http\Controllers\CatalogSearchController;
use App\Http\Controllers\SearchEntryController;
use App\Http\Middleware\ResolveSiteContext;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Catalogue Search Routes
|--------------------------------------------------------------------------
|
| Loaded after routes/web.php so these definitions replace the historical
| search endpoints without removing compatibility for existing links.
|
*/
Route::middleware([ResolveSiteContext::class])->group(function () {
    Route::get('/search', [CatalogSearchController::class, 'index'])
        ->middleware('throttle:60,1')
        ->name('search');

    Route::get('/search/ajax', [CatalogSearchController::class, 'ajax'])
        ->middleware('throttle:60,1')
        ->name('search.ajax');

    Route::get('/search/api', [CatalogSearchController::class, 'api'])
        ->middleware('throttle:60,1')
        ->name('search.api');

    Route::get('/restaurants', [SearchEntryController::class, 'restaurants'])
        ->middleware(['module:food', 'throttle:60,1'])
        ->name('restaurants.all');
});
