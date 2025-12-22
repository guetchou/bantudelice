<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
Route::group(['namespace'=>'api'],function() {
    Route::post('register', 'UserController@register');
    Route::post('login', 'UserController@login');
    Route::get('user_profile/{user}', 'UserController@profile');
    Route::post('update_profile/', 'UserController@updateProfile');
    Route::post('forgot_password', 'UserController@forgotPassword');
    Route::post('add_user_address', 'UserController@addUserAddress');
    Route::get('get_user_address/{user}', 'UserController@getUserAddress');
    Route::post('track_pendings_orders', 'UserController@trackOrders');
     Route::post('track_completed_orders', 'UserController@trackCompletedOrders');
     Route::post('reviews_and_ratings', 'UserController@sendReviewsToRestaurant');
     Route::post('user_device_token','UserController@userDeviceToken');

//Dirver's APIs

    Route::post('driver_register', 'DriverController@register');
    Route::post('driver_login', 'DriverController@login');
    Route::get('driver_profile/{driver}', 'DriverController@profile');
    Route::post('driver_update_profile/', 'DriverController@updateProfile');
    Route::post('driver_forgot_password', 'DriverController@forgotPassword');
    Route::post('set_driver_online/{driver}', 'DriverController@SetDriverOnline');
    Route::get('set_driver_offline/{driver}', 'DriverController@SetDriverOffline');
    Route::post('set_time_for_online', 'DriverController@SetDriverOnlineTime');
    Route::get('order_request/{dirver}', 'DriverController@orderRequests');
    Route::post('order_accept_by_driver', 'DriverController@acceptOrderRequests');
    Route::get('ordered_product/{orderno}', 'DriverController@ordersProducts');
    Route::get('driver_reviews/{dirver}', 'ReviewController@driverReviews');
    Route::post('driver_earning_history/{dirver}', 'DriverController@driverEarningHistory');
    
    
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
     
     Route::get('delivery_summary/{driver}', 'DriverController@deliverySummary');
     Route::get('latest_news', 'DriverController@latestNews');
     
     // Real-time tracking APIs
     Route::get('order/{orderNo}/status', 'IndexController@getOrderStatus');
     Route::post('driver/{driverId}/location', 'DriverController@updateLocation');
     
     // Order Rating APIs
     Route::post('orders/{order}/rating', 'OrderRatingController@store');
     Route::get('orders/{order}/rating', 'OrderRatingController@show');
     Route::get('orders/{order}/rating/check', 'OrderRatingController@check');
     
     // Delivery & Driver APIs
     Route::middleware('auth:sanctum')->group(function () {
         // Côté livreur
         Route::get('driver/deliveries', 'DriverDeliveriesController@index');
         Route::patch('driver/deliveries/{delivery}/status', 'DriverDeliveriesController@updateStatus');
         
         // Côté client
         Route::get('orders/{order}/tracking', 'OrderTrackingController@show');
         
         // Checkout & Payment APIs
         Route::post('checkout', 'CheckoutController@__invoke');
         Route::get('payments/{payment}', 'PaymentController@show');
         Route::post('payments/{payment}/confirm', 'PaymentController@confirm');
     });
     
    // ... (rest of the file remains same, just moved out of the group)
});

// Payment Callback (public, mais peut être sécurisé par IP whitelist)
Route::post('payments/callback/{provider}', [\App\Http\Controllers\api\PaymentCallbackController::class, 'handle'])
    ->name('api.payments.callback');

// Module Colis (V1)
Route::prefix('v1')->group(function () {
    // Public & Client
    Route::post('colis/quotes', [\App\Http\Controllers\Api\V1\Colis\QuoteController::class, '__invoke']);
    Route::get('colis/track/{tracking_number}', [\App\Http\Controllers\Api\V1\Colis\TrackingController::class, '__invoke']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('colis/shipments', [\App\Http\Controllers\Api\V1\Colis\ShipmentController::class, 'index']);
        Route::post('colis/shipments', [\App\Http\Controllers\Api\V1\Colis\ShipmentController::class, 'store'])->name('colis.shipments.store');
        Route::get('colis/shipments/{shipment}', [\App\Http\Controllers\Api\V1\Colis\ShipmentController::class, 'show']);
        Route::post('colis/shipments/{shipment}/cancel', [\App\Http\Controllers\Api\V1\Colis\ShipmentController::class, 'cancel']);
        Route::post('colis/shipments/{shipment}/payment', [\App\Http\Controllers\Api\V1\Colis\ShipmentController::class, 'processPayment']);
        Route::get('colis/shipments/{shipment}/payment-status', [\App\Http\Controllers\Api\V1\Colis\ShipmentController::class, 'paymentStatus']);
        
        // Courier
        Route::get('courier/shipments/assigned', [\App\Http\Controllers\Api\V1\Courier\CourierShipmentController::class, 'assigned']);
        Route::post('courier/shipments/{shipment}/events', [\App\Http\Controllers\Api\V1\Courier\CourierShipmentController::class, 'pushEvent']);
        Route::post('courier/shipments/{shipment}/proofs', [\App\Http\Controllers\Api\V1\Courier\CourierShipmentController::class, 'uploadProof']);
        Route::post('courier/shipments/{shipment}/deliver', [\App\Http\Controllers\Api\V1\Courier\CourierShipmentController::class, 'deliver']);
        
        // Admin
        Route::prefix('admin')->group(function () {
            Route::get('colis/shipments', [\App\Http\Controllers\Api\V1\Admin\AdminShipmentController::class, 'index']);
            Route::post('colis/shipments/{shipment}/assign', [\App\Http\Controllers\Api\V1\Admin\AdminShipmentController::class, 'assign']);
            Route::post('colis/shipments/{shipment}/auto-assign', [\App\Http\Controllers\Api\V1\Admin\AdminShipmentController::class, 'autoAssign']);
            Route::post('colis/shipments/{shipment}/status', [\App\Http\Controllers\Api\V1\Admin\AdminShipmentController::class, 'overrideStatus']);
        });
    });

    // Module Transport
    Route::prefix('transport')->group(function () {
        Route::post('estimate', [\App\Http\Controllers\api\Transport\TransportBookingController::class, 'estimate']);
        
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('bookings', [\App\Http\Controllers\api\Transport\TransportBookingController::class, 'index']);
            Route::post('bookings', [\App\Http\Controllers\api\Transport\TransportBookingController::class, 'store']);
            Route::get('bookings/{id}', [\App\Http\Controllers\api\Transport\TransportBookingController::class, 'show']);
            Route::post('bookings/{id}/cancel', [\App\Http\Controllers\api\Transport\TransportBookingController::class, 'cancel']);
            Route::post('bookings/{id}/pay', [\App\Http\Controllers\api\Transport\TransportBookingController::class, 'pay']);
            
            // Driver routes for transport
            Route::prefix('driver')->group(function () {
                Route::get('nearby', [\App\Http\Controllers\api\Transport\DriverTransportController::class, 'nearby']);
                Route::post('bookings/{id}/accept', [\App\Http\Controllers\api\Transport\DriverTransportController::class, 'accept']);
                Route::post('bookings/{id}/status', [\App\Http\Controllers\api\Transport\DriverTransportController::class, 'updateStatus']);
                Route::post('bookings/{id}/location', [\App\Http\Controllers\api\Transport\DriverTransportController::class, 'updateLocation']);
            });
        });
    });
});

// Métriques admin (nécessite auth)
Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    Route::get('metrics/realtime', [\App\Http\Controllers\admin\MetricsController::class, 'realtime']);
    Route::get('metrics/historical', [\App\Http\Controllers\admin\MetricsController::class, 'historical']);
});
