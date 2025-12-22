<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

//Route::get('/', function () {
//    return view('welcome');
//});
use Illuminate\Support\Facades\Route;

// Route admin désactivée - en conflit avec route resource
// Route::get('/', 'HomeController@index')->name('index');
Route::get('/admin', 'HomeController@dashboard');
//Route::get('register', 'RegisterController@register_view')->name('register');
Route::get('login', 'LoginController@login_view')->name('login');
Route::post('login', 'LoginController@login');
Route::get('logout', 'LoginController@logout')->name('logout');

// Auth sociale (clients) - Google / Facebook
Route::middleware('guest')->group(function () {
    Route::get('auth/{provider}', 'SocialAuthController@redirect')->name('auth.social.redirect');
    Route::get('auth/{provider}/callback', 'SocialAuthController@callback')->name('auth.social.callback');
});

// Route publique pour la page de connexion des restaurants
Route::get('restaurant', function() {
    if (auth()->check() && auth()->user()->type === 'restaurant') {
        return redirect()->route('restaurant.dashboard');
    }
    return redirect()->route('login');
})->name('restaurant.login.page');

//Fontend routes
Route::get('/', 'IndexController@home')->name('home');
Route::get('resturant/view/{id}', 'IndexController@resturantDetail')->name('resturant.detail');
Route::get('product/view/{id}', 'IndexController@proDetail')->name('pro.detail');
Route::get('cart', 'IndexController@cartDeatil')->name('cart.detail');
Route::get('user/login', 'IndexController@Login')->name('user.login');
Route::get('user/forgot', 'IndexController@forgot')->name('user.forgot');
Route::post('user/forgot-password', 'IndexController@forgotPassword')->name('forgot');
Route::get('signup', 'IndexController@SignUp')->name('user.signup');
Route::post('signup', 'IndexController@register')->name('new.signup');
Route::get('about-us', 'IndexController@about')->name('about.us');
Route::post('contact-us', 'ContactController@ContactUs')->name('contact');
Route::get('contact-us', 'IndexController@contact')->name('contact.us');
Route::get('user-logout', 'IndexController@logout')->name('user.logout');
Route::get('terms-and-conditions', 'IndexController@terms')->name('terms.conditions');
Route::get('return-policy', 'IndexController@refundPolicy')->name('refund.policy');
Route::get('privacy-policy', 'IndexController@privacyPolicy')->name('privacy.policy');
Route::get('data-deletion', 'IndexController@dataDeletion')->name('data.deletion');

// Facebook - User Data Deletion (Meta)
Route::post('facebook/data-deletion', 'FacebookDataDeletionController@handle')->name('facebook.data_deletion');
Route::get('facebook/data-deletion/status/{code}', 'FacebookDataDeletionController@status')->name('facebook.data_deletion.status');
Route::get('profile', 'IndexController@profile')->name('user.profile');
Route::post('profile/update', 'IndexController@updateProfile')->name('profile.update');
Route::post('profile/password', 'IndexController@updatePassword')->name('profile.password');
Route::post('profile/avatar', 'IndexController@updateAvatar')->name('profile.update.avatar');
Route::get('track-order/{orderNo?}', 'IndexController@trackOrder')->name('track.order');
Route::get('faq', 'IndexController@faq')->name('faq');
Route::get('help', 'IndexController@help')->name('help');
Route::get('offers', 'IndexController@offers')->name('offers');
Route::get('forgot-password', 'IndexController@forgot')->name('forgot.password');
Route::post('cart', 'IndexController@addToCart')->name('cart');
Route::post('voucher', 'IndexController@checkVoucher');
Route::get('/cart/deleteItem/{id}','IndexController@deleteItem')->name('cart.item');
Route::put('/cart/update/{cart}','IndexController@updateItem')->name('cart.update');
Route::get('checkout', 'IndexController@Checkout')->name('checkout.detail');
Route::post('/checkout/order', 'IndexController@getOrders')->name('place.order');
Route::get('/cart/count', 'IndexController@getCartCount')->name('cart.count');
Route::get('/cart/checkout/stripe', 'IndexController@stripe')->name('stripe');
Route::post('/cart/checkout/stripe', 'IndexController@stripePost')->name('stripe.post');
Route::get('/cart/checkout/thankyou', 'IndexController@thanks')->name('thanks');
Route::get('/search/', 'IndexController@searchResult')->name('serach');
Route::get('/search/ajax', 'IndexController@searchAjax')->name('search.ajax');
Route::post('paypal', 'PaypalController@payWithpaypal');
// route for check status of the payment
Route::get('status', 'PaypalController@getPaymentStatus');
Route::get('driver/registration', 'DriverController@driver')->name('driver');
Route::post('driver/registration', 'DriverController@driverRegistration')->name('driver.register');

// Livraisons livreur (nécessite authentification)
Route::middleware('auth')->group(function () {
    Route::get('driver/deliveries', 'DriverDeliveriesController@index')->name('driver.deliveries');
    Route::post('driver/deliveries/{delivery}/status', 'DriverDeliveriesController@updateStatus')->name('driver.deliveries.update');

    // Suppression de compte (self-service)
    Route::post('profile/delete', 'IndexController@deleteAccount')->name('profile.delete');

    // Module Colis Frontend
    Route::get('mes-colis', 'IndexController@myShipments')->name('colis.mes-envois');
    Route::get('mes-colis/{id}', 'IndexController@showShipment')->name('colis.show');
    Route::get('colis/nouveau', 'IndexController@createShipment')->name('colis.create');
    Route::post('colis/shipments', 'IndexController@storeShipment')->name('colis.shipments.store');

    // Module Transport Frontend
    Route::get('transport', 'TransportController@index')->name('transport.index');
    Route::get('transport/taxi', 'TransportController@taxi')->name('transport.taxi');
    Route::get('transport/carpool', 'TransportController@carpool')->name('transport.carpool');
    Route::get('transport/rental', 'TransportController@rental')->name('transport.rental');
    Route::get('transport/mes-reservations', 'TransportController@myBookings')->name('transport.my_bookings');
    Route::get('transport/booking/{id}', 'TransportController@showBooking')->name('transport.booking.show');

    // Driver Transport
    Route::get('driver/transport', 'TransportController@driverDashboard')->name('driver.transport.dashboard');
});

// Vitrine / Landing Colis
Route::get('livraison-colis', 'IndexController@colisLanding')->name('colis.landing');

// Suivi public de colis (Sans auth)
Route::get('suivi-colis', 'IndexController@trackShipmentPublic')->name('colis.track_public');

Route::get('partner/registration', 'PartnerController@partner')->name('partner');
Route::post('partner/registration', 'PartnerController@partnerRegistration')->name('partner.register');
Route::get('restaurants/cuisine/{id}', 'IndexController@restaurantByCuisine')->name('restaurant.cuisine');
Route::get('restaurants', 'IndexController@allRestaurants')->name('restaurants.all');


Route::group(['prefix' => 'admin', 'namespace' => 'admin', 'middleware' => ['auth', 'admin']], function () {

//    Route::get('/', 'HomeController@home')->name('admin.home');
    Route::get('/', 'DashboardController@index')->name('admin.dashboard');
    Route::get('all-products', 'HomeController@totalPro')->name('total.pro');
    Route::resource('restaurant', 'RestaurantController');
    Route::get('pending', 'RestaurantController@pending')->name('admin.pending');
    Route::get('get_service_charges/{restaurant}', 'RestaurantController@get_service_charges')->name('admin.get_service_charges');
    Route::post('set_service_charges/{restaurant}', 'RestaurantController@set_service_charges')->name('admin.set_service_charges');
    Route::get('change_restaurant_active_status/{restaurant}', 'RestaurantController@change_restaurant_active_status')->name('admin.change_restaurant_active_status');
    Route::get('change_restaurant_featured_status/{restaurant}', 'RestaurantController@change_restaurant_featured_status')->name('admin.change_restaurant_featured_status');
    Route::resource('cuisine', 'CuisineController');
    Route::resource('news', 'NewsController');
    Route::get('news-notification/{news}', 'NewsController@sentNotification')->name('send.notification');
    Route::resource('driver', 'DriverController');
    Route::get('change_driver_active_status/{driver}', 'DriverController@change_driver_active_status')->name('admin.change_driver_active_status');
    Route::get('get_hourly_pay/{driver}', 'DriverController@get_hourly_pay')->name('admin.get_hourly_pay');
    Route::post('set_hourly_pay/{driver}', 'DriverController@set_hourly_pay')->name('admin.set_hourly_pay');
    // charges
    Route::resource('charge', 'ChargeController');

    // Module Colis (Livraison)
    Route::get('colis', 'ColisController@index')->name('admin.colis.index');
    Route::get('colis/export-csv', 'ColisController@exportCsv')->name('admin.colis.export-csv');
    Route::get('colis/finance', 'ColisController@financialIndex')->name('admin.colis.finance');
    Route::post('colis/reconcile/{courierId}', 'ColisController@reconcile')->name('admin.colis.reconcile');
    Route::get('colis/{shipment}', 'ColisController@show')->name('admin.colis.show');
    Route::get('colis/{shipment}/print', 'ColisController@print')->name('admin.colis.print');
    Route::post('colis/{shipment}/incident', 'ColisController@reportIncident')->name('admin.colis.report-incident');
    
    // Points Relais
    Route::get('relay-points', 'RelayPointController@index')->name('admin.relay-points.index');
    Route::get('relay-points/create', 'RelayPointController@create')->name('admin.relay-points.create');
    Route::post('relay-points', 'RelayPointController@store')->name('admin.relay-points.store');
    Route::post('relay-points/{relayPoint}/toggle', 'RelayPointController@toggle')->name('admin.relay-points.toggle');

    Route::resource('vehicle', 'VehicleController');

    // Module Transport (Admin)
    Route::prefix('transport')->group(function () {
        Route::get('/', 'Transport\AdminTransportController@dashboard')->name('admin.transport.dashboard');
        Route::get('bookings', 'Transport\AdminTransportController@bookings')->name('admin.transport.bookings.index');
        Route::get('vehicles', 'Transport\AdminTransportController@vehicles')->name('admin.transport.vehicles.index');
        Route::post('vehicles/{id}/approve', 'Transport\AdminTransportController@approveVehicle')->name('admin.transport.vehicles.approve');
        Route::post('vehicles/{id}/reject', 'Transport\AdminTransportController@rejectVehicle')->name('admin.transport.vehicles.reject');
        Route::get('pricing-rules', 'Transport\AdminTransportController@pricingRules')->name('admin.transport.pricing.index');
        Route::post('pricing-rules', 'Transport\AdminTransportController@storePricingRule')->name('admin.transport.pricing.store');
        Route::post('pricing-rules/{id}', 'Transport\AdminTransportController@updatePricingRule')->name('admin.transport.pricing.update');
    });

    Route::resource('extras', 'ExtrasController');
    Route::resource('user', 'UserController');
    Route::get('change_block_status/{user}', 'UserController@change_block_status')->name('admin.change_block_status');
    //orders
    Route::get('all_orders', 'OrderController@all_orders')->name('admin.all_orders');
    Route::get('complete_orders', 'OrderController@complete_orders')->name('admin.complete_orders');
    Route::get('prepaire_orders', 'OrderController@prepaire_orders')->name('admin.prepaire_orders');
    Route::get('cancel_orders', 'OrderController@cancel_orders')->name('admin.cancel_orders');
    Route::get('pending_orders', 'OrderController@pending_orders')->name('admin.pending_orders');
    Route::get('schedule_orders', 'OrderController@schedule_orders')->name('admin.schedule_orders');
    Route::post('cancel_order/{order}', 'OrderController@cancel_order')->name('admin.cancel_order');
    Route::get('assign_order/{order}', 'OrderController@assign_order')->name('admin.assign_order');
    Route::get('show_order/{order}', 'OrderController@show_order')->name('admin.show_order');
    Route::get('show_completed_order/{order}', 'OrderController@show_completed_order')->name('admin.show_completed_order');
    Route::get('deliver_order/{order}', 'OrderController@deliver_order')->name('admin.deliver_order');
    Route::post('assign_driver', 'OrderController@assign_driver')->name('admin.assign_driver');
    Route::post('prepaire_order', 'OrderController@prepaire_order')->name('admin.prepaire_order');
    //orders
    //payouts
     Route::get('restaurant_payout', 'PayoutController@restaurant_payout')->name('restaurant_payout');
    Route::get('driver_payout', 'PayoutController@driver_payout')->name('driver_payout');
    Route::post('restaurant_pay', 'PayoutController@restaurant_pay')->name('restaurant_pay');
    Route::post('driver_pay', 'PayoutController@driver_pay')->name('driver_pay');
    //profile
    Route::get('profile', 'UserController@profile')->name('admin.profile');
    Route::post('profile/update', 'UserController@passwordUpdate')->name('admin.profile_update');
    
    // API Configuration
    Route::get('api-configuration', 'ApiConfigurationController@index')->name('admin.api.configuration');
    
    // Métriques et observabilité
    Route::get('metrics', 'MetricsController@index')->name('admin.metrics');
    Route::get('api/status', 'ApiConfigurationController@getStatus')->name('admin.api.status');
    Route::get('api/keys', 'ApiConfigurationController@getApiKeys')->name('admin.api.keys');
    Route::post('api/keys/save', 'ApiConfigurationController@saveApiKeys')->name('admin.api.keys.save');
    Route::post('api/test/sms', 'ApiConfigurationController@testSms')->name('admin.api.test.sms');
    Route::post('api/test/otp', 'ApiConfigurationController@testOtp')->name('admin.api.test.otp');
    Route::post('api/test/geolocation', 'ApiConfigurationController@testGeolocation')->name('admin.api.test.geolocation');
    Route::post('api/test/momo', 'ApiConfigurationController@testMobileMoney')->name('admin.api.test.momo');
    Route::post('api/clear-cache', 'ApiConfigurationController@clearCache')->name('admin.api.clear-cache');
    // Email/SMTP Configuration
    Route::get('api/mail/status', 'ApiConfigurationController@getMailStatus')->name('admin.api.mail.status');
    Route::post('api/mail/save', 'ApiConfigurationController@saveMailConfig')->name('admin.api.mail.save');
    Route::post('api/test/email', 'ApiConfigurationController@testEmail')->name('admin.api.test.email');
});

Route::group(['prefix' => 'restaurant', 'namespace' => 'restaurant', 'middleware' => ['auth', 'restaurant']], function () {
    //profile
    Route::get('profile', 'UserController@profile')->name('restaurant.profile');
    Route::get('delivery_boundary', 'HomeController@delivery_boundary')->name('delivery_boundary');

    Route::post('profile/profile_update', 'UserController@profile_update')->name('restaurant.profile.profile_update');
    Route::get('/', 'DashboardController@index')->name('restaurant.dashboard');
    //category
    Route::resource('category', 'CategoryController');
    Route::post('category/search/{category}', 'CategoryController@search')->name('category.search');
    Route::get('notifications/{id}','DashboardController@notifications');
    //AddOns
    Route::resource('add-on', 'AddOnsController');
    // vouchers
    Route::resource('voucher','VoucherController');
    //products
    Route::resource('product', 'ProductController');
    Route::resource('optional', 'OptionalController');
    Route::resource('required', 'RequiredController');
    Route::get('change_product_featured_status/{product}', 'ProductController@change_product_featured_status')->name('restaurant.change_product_featured_status');
    //orders
    Route::get('all_orders', 'OrderController@all_orders')->name('restaurant.all_orders');
    Route::get('complete_orders', 'OrderController@complete_orders')->name('restaurant.complete_orders');
    Route::get('cancel_orders', 'OrderController@cancel_orders')->name('restaurant.cancel_orders');

    Route::post('prepaire_order', 'OrderController@prepaire_order')->name('restaurant.prepaire_orders');
    Route::get('all-preparing-orders', 'OrderController@getPreparingOrders')->name('restaurant.getpreparing');

    Route::get('assigned_orders', 'OrderController@pending_orders')->name('restaurant.pending_orders');
    Route::get('schedule_orders', 'OrderController@schedule_orders')->name('restaurant.schedule_orders');
    Route::get('cancel_order/{order}', 'OrderController@cancel_order')->name('restaurant.cancel_order');
    Route::get('assign_order/{order}', 'OrderController@assign_order')->name('restaurant.assign_order');
    Route::get('show_order/{order}', 'OrderController@show_order')->name('restaurant.show_order');
    
    Route::get('prepaire_order/{order}', 'OrderController@prepaire_order')->name('restaurant.prepaire_order');
    Route::get('deliver_order/{order}', 'OrderController@deliver_order')->name('restaurant.deliver_order');
    Route::post('assign_driver', 'OrderController@assign_driver')->name('restaurant.assign_driver');
    //payment
    Route::resource('r_earnings', 'PaymentHistoryController');
    //employee
    Route::resource('employee', 'EmployeeController');
    //working hours
    Route::resource('working_hour', 'WorkingHourController');

    // Kitchen display (polling JSON)
    Route::get('kitchen', 'KitchenController@index')->name('restaurant.kitchen');
    Route::get('kitchen/orders', 'KitchenController@orders')->name('restaurant.kitchen.orders');
    Route::patch('kitchen/orders/{orderNo}/status', 'KitchenController@updateStatus')->name('restaurant.kitchen.orders.status');

    // Menu moderne (sections + disponibilité + tri)
    Route::get('menu', 'MenuController@index')->name('restaurant.menu.index');
    Route::patch('menu/categories/reorder', 'MenuController@reorderCategories')->name('restaurant.menu.categories.reorder');
    Route::patch('menu/categories/{category}/products/reorder', 'MenuController@reorderProducts')->name('restaurant.menu.products.reorder');
    Route::patch('menu/categories/{category}/availability', 'MenuController@toggleCategoryAvailability')->name('restaurant.menu.categories.availability');
    Route::patch('menu/products/{product}/availability', 'MenuController@toggleProductAvailability')->name('restaurant.menu.products.availability');

    // Médias (galerie)
    Route::get('media', 'RestaurantMediaController@index')->name('restaurant.media.index');
    Route::post('media', 'RestaurantMediaController@store')->name('restaurant.media.store');
    Route::delete('media/{media}', 'RestaurantMediaController@destroy')->name('restaurant.media.destroy');
    Route::patch('media/reorder', 'RestaurantMediaController@reorder')->name('restaurant.media.reorder');


});
Route::group(['prefix' => 'restaurant/delivery', 'namespace' => 'delivery', 'middleware' => ['auth', 'delivery']], function () {
    Route::resource('/', 'DashboardController', ['names' => ['index' => 'delivery.dashboard']]);
    //orders
    Route::get('all_orders', 'OrderController@all_orders')->name('delivery.all_orders');
    Route::get('complete_orders', 'OrderController@complete_orders')->name('delivery.complete_orders');
    Route::get('cancel_orders', 'OrderController@cancel_orders')->name('delivery.cancel_orders');
    Route::get('pending_orders', 'OrderController@pending_orders')->name('delivery.pending_orders');
    Route::get('schedule_orders', 'OrderController@schedule_orders')->name('delivery.schedule_orders');
    Route::post('cancel_order/{order}', 'OrderController@cancel_order');
    //payment
    Route::resource('d_earnings', 'PaymentHistoryController');

});
