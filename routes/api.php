<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ModuleHealthController;
use App\Http\Controllers\PushDeviceController;
use App\Http\Controllers\SiteContextController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//Route::middleware('auth:api')->get('/user', function (Request $request) {
//    return $request->user();
//});
Route::get('health/modules', [ModuleHealthController::class, 'index']);
Route::get('health/modules/{module}', [ModuleHealthController::class, 'show']);
Route::get('health/dependencies', [ModuleHealthController::class, 'dependencies']);
Route::get('health/queues', [ModuleHealthController::class, 'queues']);
Route::get('health/workers', [ModuleHealthController::class, 'workers']);
Route::get('cms/contents', [\App\Http\Controllers\api\CmsContentApiController::class, 'index']);
Route::get('cms/contents/{slug}', [\App\Http\Controllers\api\CmsContentApiController::class, 'show']);
Route::get('site/context', [SiteContextController::class, 'show']);

Route::group(['namespace'=>'api'],function() {
    Route::post('register',        'UserController@register')->middleware('throttle:10,1');
    Route::post('login',           'UserController@login')->middleware('throttle:10,1');
    Route::get('user_profile/{user}', 'UserController@profile');
    Route::post('update_profile/', 'UserController@updateProfile');
    Route::post('forgot_password', 'UserController@forgotPassword')->middleware('throttle:5,1');
    Route::post('add_user_address', 'UserController@addUserAddress');
    Route::get('get_user_address/{user}', 'UserController@getUserAddress');
    Route::post('track_pendings_orders', 'UserController@trackOrders');
     Route::post('track_completed_orders', 'UserController@trackCompletedOrders');
     Route::post('reviews_and_ratings', 'UserController@sendReviewsToRestaurant');
     Route::post('user_device_token','UserController@userDeviceToken');

//Dirver's APIs

    Route::post('driver_register',              'DriverAuthController@register')->middleware('throttle:10,1');
    Route::post('driver_login',                 'DriverAuthController@login')->middleware('throttle:10,1');
    Route::post('driver_change_password',       'DriverAuthController@changePassword');
    Route::post('driver_forgot_password',       'DriverAuthController@forgotPassword')->middleware('throttle:5,1');
    Route::get('driver_profile/{driver}',       'DriverProfileController@profile');
    Route::post('driver_update_profile/',       'DriverProfileController@updateProfile');
    Route::post('set_driver_online/{driver}',   'DriverProfileController@setOnline');
    Route::get('set_driver_offline/{driver}',   'DriverProfileController@setOffline');
    Route::post('set_time_for_online',          'DriverProfileController@setOnlineTime');
    Route::get('order_request/{dirver}',        'DriverOrderController@orderRequests');
    Route::post('order_accept_by_driver',       'DriverOrderController@acceptOrderRequests');
    Route::get('ordered_product/{orderno}',     'DriverOrderController@ordersProducts');
    Route::get('driver_reviews/{dirver}',       'ReviewController@driverReviews');
    Route::post('driver_earning_history/{dirver}', 'DriverOrderController@driverEarningHistory');
    
    
    //Home APIs
     Route::post('home_data','IndexController@index');
     Route::get('product_detail/{product}','IndexController@proDetail');
     Route::post('search_filters', 'IndexController@searchFilter');
     Route::post('search_by_keyword','IndexController@searchQurey');
    Route::get('restaurant_detail/{restaurant}','IndexController@restaurantDetail');

//Cart's APIs
    Route::post('add_to_cart','CartController@addToCart');
    Route::get('show_cart_details/{user}','CartController@showCartDetail');
    Route::post('update_cart_details','CartController@UpdateCartDetail');
    Route::delete('delete_cart_product/{cart}','CartController@deleteCartProduct');
    Route::delete('delete_previous_cart/{user}','CartController@deletePreviousCart');
    
    
    //Orders APIs
    Route::post('place_orders/','OrderController@getOrders');
    Route::get('user_pending_orders/{user}', 'OrderController@UserOrderHistory');
    Route::get('user_completed_order_history/{user}', 'OrderController@UserCompletedOrderHistory');
    Route::post('complete_orders', 'OrderController@completeOrders');
    
    
    //Restaurant's APIs
     Route::post('search_restaurant','RestaurantController@search');
      Route::get('restaurants_with_category/{cuisine}', 'RestaurantController@restaurantsByCuisine');
    Route::get('get_filters', 'RestaurantController@sendFilters');
    Route::get('about_restaurant/{restaurant}', 'RestaurantController@restaurantAbout');
     Route::get('restaurants/popular', 'RestaurantController@popular');
     Route::get('restaurants', 'RestaurantController@index');
     Route::get('restaurants/{id}/reviews', 'RestaurantController@getReviews');
     Route::get('restaurants/{id}/promos', 'RestaurantController@getActivePromos');
    
      //Vouchers
    Route::post('get_voucher', 'VoucherController@getVoucher');
    
      
     ///Reasons
     Route::get('get_reason', 'ReasonController@getReason');
     Route::post('reject_order_request', 'ReasonController@rejectOrderRequests');
     
     Route::get('delivery_summary/{driver}', 'DriverOrderController@deliverySummary');
     Route::get('latest_news', 'DriverOrderController@latestNews');
     
     // Real-time tracking APIs
     Route::get('order/{orderNo}/status', [\App\Http\Controllers\IndexController::class, 'getOrderStatus'])->middleware('module:food');
     Route::post('driver/{driverId}/location', 'DriverOrderController@updateLocation')->middleware(['module:food', 'auth:driver_api']);
     
     // Order Rating APIs
     Route::post('orders/{order}/rating', 'OrderRatingController@store')->middleware('module:food');
     Route::get('orders/{order}/rating', 'OrderRatingController@show')->middleware('module:food');
     Route::get('orders/{order}/rating/check', 'OrderRatingController@check')->middleware('module:food');
     
     // Delivery & Driver APIs
     Route::middleware('auth:driver_api')->group(function () {
         Route::get('driver/deliveries', [\App\Http\Controllers\Api\DriverDeliveriesController::class, 'index'])->middleware('module:food');
         Route::patch('driver/deliveries/{delivery}/status', [\App\Http\Controllers\Api\DriverDeliveriesController::class, 'updateStatus'])->middleware('module:food');
         Route::post('driver/deliveries/{delivery}/incident', [\App\Http\Controllers\Api\DriverDeliveriesController::class, 'reportIncident'])->middleware('module:food');
     });

     Route::middleware('auth.web_or_api')->group(function () {
         Route::get('orders/{order}/tracking', 'OrderTrackingController@show')->middleware('module:food');
         Route::post('orders/{order}/confirm-delivery', 'OrderTrackingController@confirmDelivery')->middleware('module:food');
         Route::post('orders/{order}/incident', 'OrderTrackingController@reportIncident')->middleware('module:food');
         Route::post('orders/{order}/redelivery', 'OrderTrackingController@requestRedelivery')->middleware('module:food');
     });

     Route::middleware('auth:api')->group(function () {
         // Checkout & Payment APIs
         Route::post('checkout', [\App\Http\Controllers\Api\CheckoutController::class, '__invoke'])->middleware('module:food');
         Route::get('payments/{payment}', [\App\Http\Controllers\Api\PaymentController::class, 'show'])->middleware('module:food');
         Route::post('payments/{payment}/confirm', [\App\Http\Controllers\Api\PaymentController::class, 'confirm'])->middleware('module:food');

         // Push mobile
         Route::post('push/devices', [PushDeviceController::class, 'store']);
         Route::delete('push/devices', [PushDeviceController::class, 'destroy']);

         // S5.2 — Logout mobile
         Route::delete('user/token', 'UserController@logout')->name('api.user.logout');

         // S5.4 — Profil authentifié (sans {user} en URL)
         Route::get('user/me', 'UserController@me')->name('api.user.me');

         // S5.5 — Mise à jour profil sécurisée
         Route::post('user/me', 'UserController@updateMe')->name('api.user.me.update');

         // S5.3 — Favoris restaurants
         Route::get('user/favorites', 'UserController@favoriteRestaurants')->name('api.user.favorites');
         Route::post('user/favorites/{restaurant}', 'UserController@toggleFavoriteRestaurant')->name('api.user.favorites.toggle');

         // S5.1 — Historique commandes avec auth + ownership
         Route::get('user/orders/active',    'OrderController@UserOrderHistory')->name('api.user.orders.active');
         Route::get('user/orders/completed', 'OrderController@UserCompletedOrderHistory')->name('api.user.orders.completed');
     });
     
    // ... (rest of the file remains same, just moved out of the group)
});

// Payment Callback — throttle 60/min par IP pour éviter le flood
Route::post('payments/callback/{provider}', [\App\Http\Controllers\Api\PaymentCallbackController::class, 'handle'])
    ->middleware('throttle:60,1')
    ->name('api.payments.callback');

Route::prefix('bridge/mobile-money')->middleware('bridge.signature')->group(function () {
    Route::post('payments', [\App\Http\Controllers\Api\MobileMoneyBridgeController::class, 'store']);
    Route::get('payments/{reference}', [\App\Http\Controllers\Api\MobileMoneyBridgeController::class, 'show']);
    Route::post('payments/{reference}/reconcile', [\App\Http\Controllers\Api\MobileMoneyBridgeController::class, 'reconcile']);
});

// Module Colis (V1)
Route::prefix('v1')->group(function () {
    // Public & Client
    Route::post('colis/quotes', [\App\Http\Controllers\Api\V1\Colis\QuoteController::class, '__invoke'])->middleware('module:colis');
    Route::get('colis/track/{tracking_number}', [\App\Http\Controllers\Api\V1\Colis\TrackingController::class, '__invoke'])->middleware('module:colis');
    
    Route::middleware('auth:api')->group(function () {
        Route::get('colis/shipments', [\App\Http\Controllers\Api\V1\Colis\ShipmentController::class, 'index'])->middleware('module:colis');
        Route::post('colis/shipments', [\App\Http\Controllers\Api\V1\Colis\ShipmentController::class, 'store'])->middleware('module:colis')->name('api.colis.shipments.store');
        Route::get('colis/shipments/{shipment}', [\App\Http\Controllers\Api\V1\Colis\ShipmentController::class, 'show'])->middleware('module:colis');
        Route::post('colis/shipments/{shipment}/cancel', [\App\Http\Controllers\Api\V1\Colis\ShipmentController::class, 'cancel'])->middleware('module:colis');
        Route::post('colis/shipments/{shipment}/payment', [\App\Http\Controllers\Api\V1\Colis\ShipmentController::class, 'processPayment'])->middleware('module:colis');
        Route::get('colis/shipments/{shipment}/payment-status', [\App\Http\Controllers\Api\V1\Colis\ShipmentController::class, 'paymentStatus'])->middleware('module:colis');
    });

    Route::middleware('auth:driver_api')->group(function () {
        Route::get('courier/shipments/assigned', [\App\Http\Controllers\Api\V1\Courier\CourierShipmentController::class, 'assigned'])->middleware('module:colis');
        Route::post('courier/shipments/{shipment}/events', [\App\Http\Controllers\Api\V1\Courier\CourierShipmentController::class, 'pushEvent'])->middleware('module:colis');
        Route::post('courier/shipments/{shipment}/proofs', [\App\Http\Controllers\Api\V1\Courier\CourierShipmentController::class, 'uploadProof'])->middleware('module:colis');
        Route::post('courier/shipments/{shipment}/deliver', [\App\Http\Controllers\Api\V1\Courier\CourierShipmentController::class, 'deliver'])->middleware('module:colis');
    });

    Route::prefix('admin')->middleware(['auth:api', 'user.role:admin,api'])->group(function () {
        Route::get('colis/shipments', [\App\Http\Controllers\Api\V1\Admin\AdminShipmentController::class, 'index'])->middleware('module:colis');
        Route::post('colis/shipments/{shipment}/assign', [\App\Http\Controllers\Api\V1\Admin\AdminShipmentController::class, 'assign'])->middleware('module:colis');
        Route::post('colis/shipments/{shipment}/auto-assign', [\App\Http\Controllers\Api\V1\Admin\AdminShipmentController::class, 'autoAssign'])->middleware('module:colis');
        Route::post('colis/shipments/{shipment}/status', [\App\Http\Controllers\Api\V1\Admin\AdminShipmentController::class, 'overrideStatus'])->middleware('module:colis');
    });

    // Module Transport
    Route::prefix('transport')->middleware('module:transport')->group(function () {
        Route::post('estimate', [\App\Http\Controllers\api\Transport\TransportBookingController::class, 'estimate']);
        
        Route::middleware('auth:api')->group(function () {
            Route::get('bookings', [\App\Http\Controllers\api\Transport\TransportBookingController::class, 'index']);
            Route::post('bookings', [\App\Http\Controllers\api\Transport\TransportBookingController::class, 'store']);
            Route::get('bookings/{id}', [\App\Http\Controllers\api\Transport\TransportBookingController::class, 'show']);
            Route::post('bookings/{id}/cancel', [\App\Http\Controllers\api\Transport\TransportBookingController::class, 'cancel']);
            Route::post('bookings/{id}/pay', [\App\Http\Controllers\api\Transport\TransportBookingController::class, 'pay']);
        });

        Route::prefix('driver')->middleware('auth:driver_api')->group(function () {
            Route::get('nearby', [\App\Http\Controllers\api\Transport\DriverTransportController::class, 'nearby']);
            Route::post('bookings/{id}/accept', [\App\Http\Controllers\api\Transport\DriverTransportController::class, 'accept']);
            Route::post('bookings/{id}/status', [\App\Http\Controllers\api\Transport\DriverTransportController::class, 'updateStatus']);
            Route::post('bookings/{id}/location', [\App\Http\Controllers\api\Transport\DriverTransportController::class, 'updateLocation']);
        });
    });
});

// Métriques admin (nécessite auth)
Route::middleware(['auth:api', 'user.role:admin,api'])->prefix('admin')->group(function () {
    Route::get('metrics/realtime', [\App\Http\Controllers\admin\MetricsController::class, 'realtime']);
    Route::get('metrics/historical', [\App\Http\Controllers\admin\MetricsController::class, 'historical']);
});
