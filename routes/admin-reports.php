<?php

use App\Http\Controllers\admin\OrderReportController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/reports/orders')
    ->middleware(['auth', 'admin', 'admin.2fa', 'admin.audit', 'admin.workspace:bantudelice', 'module:food'])
    ->group(function () {
        Route::get('/', [OrderReportController::class, 'index'])->name('admin.reports.orders.index');
        Route::get('accounting.csv', [OrderReportController::class, 'accounting'])->name('admin.reports.orders.accounting');
        Route::get('commercial.csv', [OrderReportController::class, 'commercial'])->name('admin.reports.orders.commercial');
    });
