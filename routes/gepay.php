<?php

use App\Http\Controllers\Api\GePay\V1\HealthController;
use App\Http\Controllers\Api\GePay\V1\TransactionController;
use App\Http\Controllers\Api\GePay\V1\WebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/gepay/v1')->middleware('api')->group(function () {
    Route::get('health', HealthController::class);

    Route::post('webhooks/mtn', [WebhookController::class, 'mtn'])
        ->middleware('throttle:120,1');

    Route::middleware(['gepay.client', 'throttle:120,1'])->group(function () {
        Route::get('client', [TransactionController::class, 'client']);
        Route::post('collections', [TransactionController::class, 'collection']);
        Route::post('disbursements', [TransactionController::class, 'disbursement']);
        Route::get('transactions/{uuid}', [TransactionController::class, 'show']);
        Route::post('transactions/{uuid}/refresh', [TransactionController::class, 'refresh']);
    });
});
