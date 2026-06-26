<?php

use App\Http\Controllers\admin\CashCollectionController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')
    ->middleware(['auth', 'admin', 'admin.2fa', 'admin.audit', 'admin.workspace:bantudelice', 'module:food'])
    ->group(function () {
        Route::get('cash-collections', [CashCollectionController::class, 'index'])
            ->name('admin.cash_collections.index');
        Route::get('cash-collections/export', [CashCollectionController::class, 'export'])
            ->name('admin.cash_collections.export');
    });
