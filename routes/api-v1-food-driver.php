<?php

use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\DriverDeliveriesController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\IndexController;
use App\Http\Controllers\PushDeviceController;
use App\Http\Controllers\api\DriverAuthController;
use App\Http\Controllers\api\DriverOrderController;
use App\Http\Controllers\api\DriverProfileController;
use App\Http\Controllers\api\OrderController;
use App\Http\Controllers\api\OrderRatingController;
use App\Http\Controllers\api\OrderTrackingController;
use App\Http\Controllers\api\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('module:food')->group(function () {
    Route::prefix('driver')->name('api.v1.driver.')->group(function () {
        Route::post('register', [DriverAuthController::class, 'register'])->middleware('throttle:10,1')->name('register');
        Route::post('login', [DriverAuthController::class, 'login'])->middleware('throttle:10,1')->name('login');

        Route::middleware('auth:driver_api')->group(function () {
            Route::get('profile/{driver}', [DriverProfileController::class, 'profile'])->name('profile');
            Route::post('profile', [DriverProfileController::class, 'updateProfile'])->name('profile.update');
            Route::post('{driver}/online', [DriverProfileController::class, 'setOnline'])->name('online');
            Route::post('{driver}/offline', [DriverProfileController::class, 'setOffline'])->name('offline');
            Route::post('availability', [DriverProfileController::class, 'setOnlineTime'])->name('availability');
            Route::get('{driver}/order-requests', [DriverOrderController::class, 'orderRequests'])->name('order-requests');
            Route::post('order-requests/accept', [DriverOrderController::class, 'acceptOrderRequests'])->name('order-requests.accept');
            Route::get('orders/{orderno}/products', [DriverOrderController::class, 'ordersProducts'])->name('orders.products');
            Route::post('{driver}/earnings', [DriverOrderController::class, 'driverEarningHistory'])->name('earnings');
            Route::get('{driver}/delivery-summary', [DriverOrderController::class, 'deliverySummary'])->name('delivery-summary');
            Route::post('{driverId}/location', [DriverOrderController::class, 'updateLocation'])->name('location');
            Route::get('deliveries', [DriverDeliveriesController::class, 'index'])->name('deliveries.index');
            Route::patch('deliveries/{delivery}/status', [DriverDeliveriesController::class, 'updateStatus'])->name('deliveries.status');
            Route::post('deliveries/{delivery}/incident', [DriverDeliveriesController::class, 'reportIncident'])->name('deliveries.incident');
        });
    });

    Route::prefix('food')->name('api.v1.food.')->middleware('auth.web_or_api')->group(function () {
        Route::get('orders/{orderNo}/status', [IndexController::class, 'getOrderStatus'])->name('orders.status');
        Route::get('orders/{order}/tracking', [OrderTrackingController::class, 'show'])->name('orders.tracking');
        Route::post('orders/{order}/confirm-delivery', [OrderTrackingController::class, 'confirmDelivery'])->name('orders.confirm-delivery');
        Route::post('orders/{order}/incident', [OrderTrackingController::class, 'reportIncident'])->name('orders.incident');
        Route::post('orders/{order}/redelivery', [OrderTrackingController::class, 'requestRedelivery'])->name('orders.redelivery');
        Route::post('checkout', CheckoutController::class)->name('checkout');
        Route::get('payments/{payment}', [PaymentController::class, 'show'])->name('payments.show');
        Route::post('payments/{payment}/confirm', [PaymentController::class, 'confirm'])->name('payments.confirm');
        Route::post('orders/{order}/rating', [OrderRatingController::class, 'store'])->name('orders.rating.store');
        Route::get('orders/{order}/rating', [OrderRatingController::class, 'show'])->name('orders.rating.show');
        Route::get('orders/{order}/rating/check', [OrderRatingController::class, 'check'])->name('orders.rating.check');
        Route::post('push/devices', [PushDeviceController::class, 'store'])->name('push.store');
        Route::delete('push/devices', [PushDeviceController::class, 'destroy'])->name('push.destroy');
        Route::delete('user/token', [UserController::class, 'logout'])->name('user.logout');
        Route::get('user/me', [UserController::class, 'me'])->name('user.me');
        Route::post('user/me', [UserController::class, 'updateMe'])->name('user.me.update');
        Route::get('user/favorites', [UserController::class, 'favoriteRestaurants'])->name('user.favorites');
        Route::post('user/favorites/{restaurant}', [UserController::class, 'toggleFavoriteRestaurant'])->name('user.favorites.toggle');
        Route::get('user/orders/active', [OrderController::class, 'UserOrderHistory'])->name('user.orders.active');
        Route::get('user/orders/completed', [OrderController::class, 'UserCompletedOrderHistory'])->name('user.orders.completed');
    });
});
