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
use App\Http\Controllers\ModuleHealthController;
use App\Http\Controllers\PushDeviceController;
use App\Http\Controllers\SiteContextController;
use App\Http\Controllers\SiteLocaleController;
use App\Http\Middleware\ResolveSiteContext;

// Route admin désactivée - en conflit avec route resource
// Route::get('/', 'HomeController@index')->name('index');
// Le point d'entrée /admin est géré plus bas par le groupe admin protégé.
//Route::get('register', 'RegisterController@register_view')->name('register');
// T1.3 — Page offline PWA (servie par le Service Worker en cache)
Route::get('offline', fn() => view('frontend.offline'))->name('offline');

Route::get('login', 'LoginController@login_view')->name('login');
Route::post('login', 'LoginController@login')->middleware('throttle:10,1');
Route::post('logout', 'LoginController@logout')->middleware('auth')->name('logout');

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

Route::middleware(['auth', 'admin'])->prefix('health')->name('health.')->group(function () {
    Route::get('modules', [ModuleHealthController::class, 'index'])->name('modules.index');
    Route::get('modules/{module}', [ModuleHealthController::class, 'show'])->name('modules.show');
    Route::get('dependencies', [ModuleHealthController::class, 'dependencies'])->name('dependencies.index');
    Route::get('queues', [ModuleHealthController::class, 'queues'])->name('queues.index');
    Route::get('workers', [ModuleHealthController::class, 'workers'])->name('workers.index');
});

Route::middleware([ResolveSiteContext::class])->group(function () {
    //Fontend routes
    Route::get('/', 'IndexController@home')->name('home');
    Route::get('site/context', [SiteContextController::class, 'show'])->name('site.context');
    Route::get('lang/{locale}', [SiteLocaleController::class, 'switchLocale'])->name('site.locale.switch');
    Route::get('site/{siteKey}', [SiteLocaleController::class, 'switchSite'])
        ->where('siteKey', '^(?!context$)[A-Za-z0-9_-]+$')
        ->name('site.switch');
    Route::get('restaurant/view/{id}', 'PublicCatalogController@resturantDetail')->middleware('module:food')->name('restaurant.detail');
    Route::get('resturant/view/{id}', fn($id) => redirect()->route('restaurant.detail', $id, 301))->middleware('module:food')->name('resturant.detail');
    Route::get('plat/{id}/{slug?}', 'PublicCatalogController@proDetail')->name('frontend.product.show');
    Route::get('product/view/{id}', 'PublicCatalogController@proDetail')->name('pro.detail');
    Route::get('cart', 'CartCheckoutController@cartDetail')->name('cart.detail');
    Route::get('user/login', 'AuthViewController@Login')->name('user.login');
    Route::get('user/forgot', 'AuthViewController@forgot')->name('user.forgot');
    Route::post('user/forgot-password', 'CartCheckoutController@forgotPassword')->middleware('throttle:5,1')->name('forgot');
    Route::get('signup', 'AuthViewController@SignUp')->name('user.signup');
    Route::post('signup', 'CartCheckoutController@register')->middleware('throttle:10,1')->name('new.signup');
    Route::get('about-us', 'LegalPageController@about')->name('about.us');
    Route::post('contact-us', 'ContactController@ContactUs')->name('contact');
    Route::get('contact-us', 'StaticPageController@contact')->name('contact.us');
    Route::post('user-logout', 'AuthViewController@logout')->middleware('auth')->name('user.logout');
    Route::get('terms-and-conditions', 'LegalPageController@terms')->name('terms.conditions');
    Route::get('return-policy', 'LegalPageController@refundPolicy')->name('refund.policy');
    Route::get('privacy-policy', 'LegalPageController@privacyPolicy')->name('privacy.policy');
    Route::get('data-deletion', 'StaticPageController@dataDeletion')->name('data.deletion');
    Route::get('mentions-legales', 'LegalPageController@legalNotices')->name('legal.notices');
    Route::get('politique-cookies', 'LegalPageController@cookies')->name('cookies.policy');
    Route::get('plan-du-site', 'StaticPageController@siteMap')->name('site.map');
    Route::get('session/keepalive', fn() => response()->json(['ok' => true, 'csrf' => csrf_token()]))->middleware('auth')->name('session.keepalive');
    Route::get('guidance/execution', 'GuidanceController@executionGuide')->name('guidance.execution');
    Route::post('guidance/execution/tasks', 'GuidanceController@updateExecutionGuideTask')->name('guidance.execution.tasks.update');
    Route::post('guidance/execution/reset', 'GuidanceController@resetExecutionGuideState')->name('guidance.execution.reset');

    // Facebook - User Data Deletion (Meta)
    Route::post('facebook/data-deletion', 'FacebookDataDeletionController@handle')->name('facebook.data_deletion');
    Route::get('facebook/data-deletion/status/{code}', 'FacebookDataDeletionController@status')->name('facebook.data_deletion.status');
    Route::get('profile', 'ProfileController@profile')->name('user.profile');
    Route::get('profile/orders', 'ProfileController@orders')->name('user.orders');
    Route::get('profile/loyalty', 'ProfileController@loyalty')->name('profile.loyalty');
    Route::get('notifications', 'ProfileController@notifications')->name('user.notifications');
    Route::get('notifications/unread-count', 'ProfileController@unreadNotificationsCount')->name('user.notifications.count');
    Route::post('notifications/read-all', 'ProfileController@markAllNotificationsRead')->name('user.notifications.read_all');
    Route::post('notifications/{id}/read', 'ProfileController@markNotificationRead')->name('user.notifications.read');
    Route::post('profile/update', 'ProfileController@updateProfile')->middleware('throttle:20,1')->name('profile.update');
    Route::post('profile/password', 'ProfileController@updatePassword')->middleware('throttle:5,1')->name('profile.password');
    Route::post('profile/avatar', 'ProfileController@updateAvatar')->middleware('throttle:10,1')->name('profile.update.avatar');
    Route::get('track-order/{orderNo?}', 'CustomerOrderController@trackOrder')->middleware(['auth', 'module:food'])->name('track.order');
    // Polling statut commande — utilisé par la page thanks (session web requise pour contrôle invité)
    Route::get('order/{orderNo}/status', [\App\Http\Controllers\IndexController::class, 'getOrderStatus'])->middleware('module:food')->name('order.status');
    Route::post('track-order/{orderNo}/confirm', 'CustomerOrderController@confirmOrderReceipt')->middleware('module:food')->name('track.order.confirm');
    Route::post('track-order/{orderNo}/reopen-pickup', 'CustomerOrderController@reopenPickupOrder')->middleware('module:food')->name('track.order.reopen_pickup');
    Route::post('track-order/{orderNo}/incident', 'CustomerOrderController@reportOrderIncident')->middleware('module:food')->name('track.order.incident');
    Route::post('track-order/{orderNo}/redelivery', 'CustomerOrderController@requestOrderRedelivery')->middleware('module:food')->name('track.order.redelivery');
    Route::get('order-receipt/{orderNo}', 'CartCheckoutController@orderReceipt')->middleware('module:food')->name('order.receipt');
    Route::get('faq', 'LegalPageController@faq')->name('faq');
    Route::get('help', 'LegalPageController@help')->name('help');
    Route::get('offers', 'LegalPageController@offers')->name('offers');
    Route::get('forgot-password', 'AuthViewController@forgot')->name('forgot.password');
    Route::post('cart', 'CartCheckoutController@addToCart')->name('cart');
    Route::post('voucher', 'CartCheckoutController@checkVoucher');
    Route::post('/cart/deleteItem/{id}','CartCheckoutController@deleteItem')->name('cart.item');
    Route::put('/cart/update/{cart}','CartCheckoutController@updateItem')->name('cart.update');
    Route::get('checkout', 'CartCheckoutController@Checkout')->middleware('module:food')->name('checkout.detail');
    Route::post('/checkout/order', 'CustomerOrderController@getOrders')->middleware(['module:food', 'throttle:30,1'])->name('place.order');
    Route::middleware(['module:food', 'throttle:30,1'])->prefix('checkout')->group(function () {
        Route::post('api', [\App\Http\Controllers\Api\CheckoutController::class, '__invoke'])->name('checkout.api');
        Route::get('payments/{payment}', [\App\Http\Controllers\Api\PaymentController::class, 'show'])->name('checkout.payments.show');
        Route::post('payments/{payment}/confirm', [\App\Http\Controllers\Api\PaymentController::class, 'confirm'])->name('checkout.payments.confirm');
        // Relance paiement pour commandes en accepted_awaiting_payment (paiement différé à l'acceptation restaurant)
        Route::get('retry-payment/{orderNo}', [\App\Http\Controllers\Api\CheckoutController::class, 'retryPayment'])->name('checkout.retry-payment');
    });
    Route::get('/cart/count', 'PublicCatalogController@getCartCount')->name('cart.count');
    // Stripe non implémenté — routes conservées pour ne pas casser les liens existants.
    // Retourne 503 explicite au lieu d'une 500 silencieuse (méthodes inexistantes dans IndexController).
    Route::match(['get', 'post'], '/cart/checkout/stripe', function () {
        abort(503, 'Paiement Stripe temporairement indisponible.');
    })->middleware('module:food')->name('stripe');
    Route::post('/cart/checkout/stripe', function () {
        abort(503, 'Paiement Stripe temporairement indisponible.');
    })->middleware('module:food')->name('stripe.post');
    Route::get('/cart/checkout/thankyou', 'CartCheckoutController@thanks')->name('thanks');
    Route::get('/search/', 'PublicCatalogController@searchResult')->middleware('throttle:60,1')->name('search');
    Route::get('/search/ajax', 'PublicCatalogController@searchAjax')->middleware('throttle:60,1')->name('search.ajax');
    Route::get('/search/api', 'PublicCatalogController@searchApi')->middleware('throttle:60,1')->name('search.api');
    Route::post('paypal', 'PaypalController@payWithpaypal')->middleware('module:food')->name('checkout.paypal.start');
    Route::get('checkout/paypal/return', 'PaypalController@handleReturn')->middleware('module:food')->name('checkout.paypal.return');
    Route::get('checkout/paypal/cancel', 'PaypalController@handleCancel')->middleware('module:food')->name('checkout.paypal.cancel');
    // route for check status of the payment
    Route::get('status', 'PaypalController@getPaymentStatus');
    Route::get('driver', function () {
        if (Auth::check()) {
            $userType = auth()->user()->type ?? null;
            if (in_array($userType, ['driver', 'delivery'], true)) {
                return redirect()->route('driver.deliveries');
            }
            // Connecté mais pas livreur → accueil
            return redirect()->route('home');
        }
        // Non connecté → page de login (pas d'inscription auto)
        return redirect()->route('login');
    })->name('driver.portal');

    Route::get('driver/registration', 'DriverController@driver')->name('driver');
    Route::post('driver/registration', 'DriverController@driverRegistration')->name('driver.register');

    // Livraisons livreur (nécessite authentification)
    Route::middleware('auth')->group(function () {
    Route::get('driver/deliveries', 'DriverDeliveriesController@index')->name('driver.deliveries');
    Route::get('driver/deliveries/poll', 'DriverDeliveriesController@pollAssignments')->name('driver.deliveries.poll');
    Route::post('driver/deliveries/{delivery}/status', 'DriverDeliveriesController@updateStatus')->middleware('module:food')->name('driver.deliveries.update');
    Route::post('driver/deliveries/{delivery}/incident', 'DriverDeliveriesController@reportIncident')->middleware('module:food')->name('driver.deliveries.incident');
    Route::post('driver/deliveries/{delivery}/offer/accept', 'DriverOfferController@accept')->middleware('module:food')->name('driver.offer.accept');
    Route::post('driver/deliveries/{delivery}/offer/decline', 'DriverOfferController@decline')->middleware('module:food')->name('driver.offer.decline');
    Route::post('driver/location', 'DriverLocationWebController@update')->middleware('module:food')->name('driver.location.update');

    // Suppression de compte (self-service)
    Route::post('profile/delete', 'ProfileController@deleteAccount')->name('profile.delete');
    Route::post('push/devices', [PushDeviceController::class, 'store'])->name('push.devices.store.web');
    Route::delete('push/devices', [PushDeviceController::class, 'destroy'])->name('push.devices.destroy.web');
    Route::get('orders/{orderNo}/chat/messages', 'OrderChatController@messages')->middleware('module:food')->name('orders.chat.messages');
    Route::post('orders/{orderNo}/chat', 'OrderChatController@store')->middleware('module:food')->name('orders.chat.store');

    // Module Colis Frontend (auth required)
    Route::get('mes-colis', 'ColisCustomerController@myShipments')->middleware('module:colis')->name('colis.mes-envois');
    Route::get('mes-colis/{id}', 'ColisCustomerController@showShipment')->middleware('module:colis')->name('colis.show');
    Route::post('mes-colis/{id}/cancel', 'ColisCustomerController@cancelShipment')->middleware('module:colis')->name('colis.cancel');
    Route::get('colis/create', 'PublicCatalogController@createShipment')->middleware('module:colis')->name('colis.create.alias');
    Route::get('colis/nouveau', 'PublicCatalogController@createShipment')->middleware('module:colis')->name('colis.create');
    Route::post('colis/shipments', 'ColisCustomerController@storeShipment')->middleware('module:colis')->name('colis.shipments.store');

    // Module Transport Frontend (auth required)
    Route::get('transport/mes-reservations', 'TransportController@myBookings')->middleware('module:transport')->name('transport.my_bookings');
    Route::get('transport/booking/{id}', 'TransportController@showBooking')->middleware('module:transport')->name('transport.booking.show');
    Route::get('transport/xhr/bookings', [\App\Http\Controllers\api\Transport\TransportBookingController::class, 'index'])->middleware('module:transport')->name('transport.xhr.bookings.index');
    Route::post('transport/xhr/bookings', [\App\Http\Controllers\api\Transport\TransportBookingController::class, 'store'])->middleware('module:transport')->name('transport.xhr.bookings.store');
    Route::get('transport/xhr/bookings/{id}', [\App\Http\Controllers\api\Transport\TransportBookingController::class, 'show'])->middleware('module:transport')->name('transport.xhr.bookings.show');
    Route::post('transport/xhr/bookings/{id}/pay', [\App\Http\Controllers\api\Transport\TransportBookingController::class, 'pay'])->middleware('module:transport')->name('transport.xhr.bookings.pay');
    Route::post('transport/xhr/driver/bookings/{id}/accept', [\App\Http\Controllers\api\Transport\DriverTransportController::class, 'accept'])->middleware('module:transport')->name('transport.xhr.driver.accept');
    Route::post('transport/xhr/driver/bookings/{id}/status', [\App\Http\Controllers\api\Transport\DriverTransportController::class, 'updateStatus'])->middleware('module:transport')->name('transport.xhr.driver.status');
    Route::post('transport/xhr/driver/bookings/{id}/location', [\App\Http\Controllers\api\Transport\DriverTransportController::class, 'updateLocation'])->middleware('module:transport')->name('transport.xhr.driver.location');

    // Driver Transport
    Route::get('driver/transport', 'TransportController@driverDashboard')->middleware('module:transport')->name('driver.transport.dashboard');

    // ── Nouvelles pages espace livreur ──────────────────────
    Route::get('driver/documents',           'DriverDocumentController@index')  ->name('driver.documents');
    Route::post('driver/documents',          'DriverDocumentController@store')  ->name('driver.documents.store')->middleware('throttle:10,1');
    Route::delete('driver/documents/{id}',   'DriverDocumentController@destroy')->name('driver.documents.destroy');
    Route::get('driver/documents/{id}/view', 'DriverDocumentController@show')   ->name('driver.documents.view');
    Route::get('driver/gains',      'DriverPageController@gains')     ->name('driver.gains');
    Route::post('driver/payout/request', 'DriverPageController@requestPayout')->name('driver.payout.request')->middleware('throttle:3,60');
    Route::post('driver/withdrawals', 'DriverWithdrawController@store')->name('driver.withdrawals.store')->middleware('throttle:3,10');
    Route::get('withdrawals/{withdrawal}/status', 'WithdrawalStatusController@show')->name('withdrawals.status')->middleware('throttle:30,1');
    Route::get('driver/historique', 'DriverPageController@historique')->name('driver.historique');
    Route::get('driver/note',       'DriverPageController@note')      ->name('driver.note');
    Route::get('driver/support',    'DriverPageController@support')   ->name('driver.support');
    });

    // Vitrine / Landing Colis
    Route::get('colis', 'PublicCatalogController@colisLanding')->middleware('module:colis')->name('colis.index');
    Route::get('livraison-colis', 'PublicCatalogController@colisLanding')->middleware('module:colis')->name('colis.landing');

    // Suivi public de colis (Sans auth)
    Route::get('colis/track', 'ColisCustomerController@trackShipmentPublic')->middleware('module:colis')->name('colis.track.alias');
    Route::get('suivi-colis', 'ColisCustomerController@trackShipmentPublic')->middleware('module:colis')->name('colis.track_public');

    // Module Transport Frontend (public catalogue + estimation)
    Route::post('transport/xhr/estimate', [\App\Http\Controllers\api\Transport\TransportBookingController::class, 'estimate'])->middleware('module:transport')->name('transport.xhr.estimate');
    Route::get('transport/xhr/geocode', [\App\Http\Controllers\api\Transport\TransportBookingController::class, 'geocode'])->middleware('module:transport')->name('transport.xhr.geocode');
    Route::get('transport/xhr/reverse', [\App\Http\Controllers\api\Transport\TransportBookingController::class, 'reverseGeocode'])->middleware('module:transport')->name('transport.xhr.reverse');
    Route::post('transport/xhr/route', [\App\Http\Controllers\api\Transport\TransportBookingController::class, 'routeSummary'])->middleware('module:transport')->name('transport.xhr.route');
    Route::get('transport', 'TransportController@index')->middleware('module:transport')->name('transport.index');
    Route::get('transport/taxi', 'TransportController@taxi')->middleware('module:transport')->name('transport.taxi');
    Route::get('transport/carpool', 'TransportController@carpool')->middleware('module:transport')->name('transport.carpool');
    Route::get('transport/rental', 'TransportController@rental')->middleware('module:transport')->name('transport.rental');
    Route::get('transport/bus', 'TransportController@bus')->middleware('module:transport')->name('transport.bus');

    Route::get('partenaires', 'PartnerController@partenaires')->name('partenaires');
    Route::get('partner/registration', 'PartnerController@partner')->name('partner');
    Route::post('partner/registration', 'PartnerController@partnerRegistration')->name('partner.register');
    Route::get('restaurants/cuisine/{id}', 'PublicCatalogController@restaurantByCuisine')->middleware(['module:food', 'throttle:60,1'])->name('restaurant.cuisine');
    Route::get('restaurants', 'PublicCatalogController@allRestaurants')->middleware(['module:food', 'throttle:60,1'])->name('restaurants.all');
    Route::middleware(['auth', 'module:food'])->group(function () {
        Route::get('favorite-restaurants', 'RestaurantFavoriteController@index')->name('restaurants.favorites');
        Route::post('restaurants/{restaurant}/favorite', 'RestaurantFavoriteController@toggle')->name('restaurants.favorite.toggle');
    });

    Route::middleware('auth')->group(function () {
        Route::post('profile/addresses', 'UserAddressController@store')->name('profile.addresses.store');
        Route::put('profile/addresses/{address}', 'UserAddressController@update')->name('profile.addresses.update');
        Route::post('profile/addresses/{address}/default', 'UserAddressController@makeDefault')->name('profile.addresses.default');
        Route::delete('profile/addresses/{address}', 'UserAddressController@destroy')->name('profile.addresses.destroy');
        Route::get('orders/{orderNo}/edit', 'OrderModificationController@edit')->middleware('module:food')->name('orders.edit');
        Route::patch('orders/{orderNo}', 'OrderModificationController@update')->middleware('module:food')->name('orders.update');
    });
});


// ================================================================
// ADMIN — routes communes (auth + admin uniquement)
// ================================================================
// 2FA challenge — accessible avant que l'auth soit complète (login 2 étapes)
Route::get('admin/2fa/challenge',  'admin\TwoFactorController@showChallenge')->name('admin.2fa.challenge');
Route::post('admin/2fa/challenge', 'admin\TwoFactorController@verifyChallenge')->name('admin.2fa.verify')->middleware('throttle:10,1');

Route::group(['prefix' => 'admin', 'namespace' => 'admin', 'middleware' => ['auth', 'admin', 'admin.2fa', 'admin.audit']], function () {
    // Audit trail
    Route::get('audit-trail', 'AuditTrailController@index')->name('admin.audit_trail');

    // KYC livreurs
    Route::get('drivers/{driver}/kyc',                             'DriverKycController@show')   ->name('admin.driver.kyc');
    Route::post('drivers/{driver}/kyc/{document}/approve',         'DriverKycController@approve') ->name('admin.driver.kyc.approve');
    Route::post('drivers/{driver}/kyc/{document}/reject',          'DriverKycController@reject')  ->name('admin.driver.kyc.reject');

    // 2FA setup (protégé par admin.2fa middleware du groupe)
    Route::get('2fa/setup',    'TwoFactorController@showSetup')->name('admin.2fa.setup');
    Route::post('2fa/enable',  'TwoFactorController@enable')->name('admin.2fa.enable')->middleware('throttle:5,1');
    Route::post('2fa/disable', 'TwoFactorController@disable')->name('admin.2fa.disable')->middleware('throttle:5,1');

    Route::post('impersonate/stop', 'ImpersonationController@stop')->name('admin.impersonate.stop');
    Route::get('portal', 'AdminPortalController@index')->name('admin.portal');
    Route::get('profile', 'UserController@profile')->name('admin.profile');
    Route::post('profile/update', 'UserController@passwordUpdate')->name('admin.profile_update');

    // Routes systeme — accessibles a tout administrateur authentifie
    Route::get('api-configuration', 'ApiConfigurationController@index')->name('admin.api.configuration');
    Route::get('modules', 'ModuleOperationsController@index')->name('admin.modules.index');
    Route::post('modules', 'ModuleOperationsController@update')->name('admin.modules.update');
    Route::post('modules/failed-jobs/{jobId}/retry', 'ModuleOperationsController@retryFailedJob')->name('admin.modules.failed_jobs.retry');
    Route::get('metrics', 'MetricsController@index')->name('admin.metrics');
    Route::get('metrics/realtime', 'MetricsController@realtime')->name('admin.metrics.realtime');
    Route::get('metrics/historical', 'MetricsController@historical')->name('admin.metrics.historical');
    Route::get('api/status', 'ApiConfigurationController@getStatus')->name('admin.api.status');
    Route::get('api/keys', 'ApiConfigurationController@getApiKeys')->name('admin.api.keys');
    Route::post('api/keys/save', 'ApiConfigurationController@saveApiKeys')->name('admin.api.keys.save');
    Route::post('api/test/sms', 'ApiConfigurationController@testSms')->name('admin.api.test.sms');
    Route::post('api/test/otp', 'ApiConfigurationController@testOtp')->name('admin.api.test.otp');
    Route::post('api/test/geolocation', 'ApiConfigurationController@testGeolocation')->name('admin.api.test.geolocation');
    Route::post('api/test/momo', 'ApiConfigurationController@testMobileMoney')->name('admin.api.test.momo');
    Route::post('api/clear-cache', 'ApiConfigurationController@clearCache')->name('admin.api.clear-cache');
    Route::get('api/mail/status', 'ApiConfigurationController@getMailStatus')->name('admin.api.mail.status');
    Route::post('api/mail/save', 'ApiConfigurationController@saveMailConfig')->name('admin.api.mail.save');
    Route::post('api/test/email', 'ApiConfigurationController@testEmail')->name('admin.api.test.email');

    // ================================================================
    // BANTUDELICE — livraison repas
    // ================================================================
    Route::middleware('admin.workspace:bantudelice')->group(function () {
        Route::get('/', 'DashboardController@index')->name('admin.dashboard');
        Route::get('architecture/admin-shell', 'AdminArchitectureController@show')->name('admin.architecture.show');
        Route::get('architecture/admin-shell/preview', 'AdminArchitectureController@preview')->name('admin.architecture.preview');
        Route::get('all-products', 'ProductController@index')->name('total.pro');
        Route::resource('restaurant', 'RestaurantController');
        Route::get('restaurant/{restaurant}/dashboard-preview', 'RestaurantDashboardPreviewController@show')->name('admin.restaurant.dashboard-preview');
        Route::post('impersonate/restaurant/{restaurant}', 'ImpersonationController@restaurant')->name('admin.impersonate.restaurant');
        Route::post('impersonate/driver/{driver}', 'ImpersonationController@driver')->name('admin.impersonate.driver');
        Route::get('pending', 'RestaurantController@pending')->name('admin.pending');
        Route::get('get_service_charges/{restaurant}', 'RestaurantController@get_service_charges')->name('admin.get_service_charges');
        Route::post('set_service_charges/{restaurant}', 'RestaurantController@set_service_charges')->name('admin.set_service_charges');
        Route::post('change_restaurant_active_status/{restaurant}', 'RestaurantController@change_restaurant_active_status')->name('admin.change_restaurant_active_status');
        Route::post('change_restaurant_featured_status/{restaurant}', 'RestaurantController@change_restaurant_featured_status')->name('admin.change_restaurant_featured_status');
        Route::resource('cuisine', 'CuisineController');
        Route::resource('news', 'NewsController');
        Route::get('promotions', 'VoucherController@index')->name('admin.promotions.index');
        Route::get('promotions/create', 'VoucherController@create')->name('admin.promotions.create');
        Route::post('promotions', 'VoucherController@store')->name('admin.promotions.store');
        Route::get('promotions/{voucher}/edit', 'VoucherController@edit')->name('admin.promotions.edit');
        Route::put('promotions/{voucher}', 'VoucherController@update')->name('admin.promotions.update');
        Route::delete('promotions/{voucher}', 'VoucherController@destroy')->name('admin.promotions.destroy');
        Route::get('home-content', 'HomeContentController@edit')->name('admin.home-content.edit');
        Route::put('home-content', 'HomeContentController@update')->name('admin.home-content.update');
        Route::redirect('cms/admin/cms/contents', '/admin/cms/contents', 301);
        Route::get('cms', 'CmsDashboardController@index')->name('admin.cms.dashboard');
        Route::get('cms/content-types', 'CmsContentTypeController@index')->name('admin.cms.content-types.index');
        Route::get('cms/content-types/create', 'CmsContentTypeController@create')->name('admin.cms.content-types.create');
        Route::post('cms/content-types', 'CmsContentTypeController@store')->name('admin.cms.content-types.store');
        Route::get('cms/content-types/{contentType}/edit', 'CmsContentTypeController@edit')->name('admin.cms.content-types.edit');
        Route::put('cms/content-types/{contentType}', 'CmsContentTypeController@update')->name('admin.cms.content-types.update');
        Route::get('cms/content-types/{contentType}/fields/create', 'CmsContentFieldController@create')->name('admin.cms.content-types.fields.create');
        Route::post('cms/content-types/{contentType}/fields', 'CmsContentFieldController@store')->name('admin.cms.content-types.fields.store');
        Route::get('cms/content-types/{contentType}/fields/{field}/edit', 'CmsContentFieldController@edit')->name('admin.cms.content-types.fields.edit');
        Route::put('cms/content-types/{contentType}/fields/{field}', 'CmsContentFieldController@update')->name('admin.cms.content-types.fields.update');
        Route::delete('cms/content-types/{contentType}/fields/{field}', 'CmsContentFieldController@destroy')->name('admin.cms.content-types.fields.destroy');
        Route::get('cms/contents', 'CmsContentController@index')->name('admin.cms.contents.index');
        Route::get('cms/contents/create', 'CmsContentController@create')->name('admin.cms.contents.create');
        Route::post('cms/contents', 'CmsContentController@store')->name('admin.cms.contents.store');
        Route::get('cms/contents/{content}/edit', 'CmsContentController@edit')->name('admin.cms.contents.edit');
        Route::put('cms/contents/{content}', 'CmsContentController@update')->name('admin.cms.contents.update');
        Route::delete('cms/contents/{content}', 'CmsContentController@destroy')->name('admin.cms.contents.destroy');
        Route::post('cms/contents/{content}/transition/{toStatus}', 'CmsContentController@transition')->name('admin.cms.contents.transition');
        Route::get('cms/media', 'CmsMediaController@index')->name('admin.cms.media.index');
        Route::post('cms/media', 'CmsMediaController@store')->name('admin.cms.media.store');
        Route::get('support-tickets', 'SupportTicketController@index')->name('admin.support-tickets.index');
        Route::post('support-tickets/{ticket}/resolve', 'SupportTicketController@resolve')->name('admin.support-tickets.resolve');
        Route::get('commerce-analytics', 'CommerceAnalyticsController@index')->name('admin.commerce-analytics.index');
        Route::get('payments/dashboard', 'PaymentDashboardController@index')->name('admin.payments.dashboard');
        Route::get('payments/dashboard/data', 'PaymentDashboardController@data')->name('admin.payments.dashboard.data');
        Route::post('payments/{payment}/reconcile', 'PaymentDashboardController@reconcile')->name('admin.payments.reconcile');
        Route::get('payments/export-csv', 'PaymentDashboardController@exportCsv')->name('admin.payments.export-csv');
        Route::get('gepay', [\App\Http\Controllers\admin\GePayDisbursementController::class, 'index'])->name('admin.gepay.index');
        Route::post('gepay/disburse', [\App\Http\Controllers\admin\GePayDisbursementController::class, 'disburse'])->name('admin.gepay.disburse');
        Route::post('gepay/collect', [\App\Http\Controllers\admin\GePayDisbursementController::class, 'collect'])->name('admin.gepay.collect');
        Route::get('gepay/transactions', [\App\Http\Controllers\admin\GePayDisbursementController::class, 'transactions'])->name('admin.gepay.transactions');
        Route::post('news-notification/{news}', 'NewsController@sentNotification')->name('send.notification');
        Route::resource('driver', 'DriverController');
        Route::post('change_driver_active_status/{driver}', 'DriverController@change_driver_active_status')->name('admin.change_driver_active_status');
        Route::get('get_hourly_pay/{driver}', 'DriverController@get_hourly_pay')->name('admin.get_hourly_pay');
        Route::post('set_hourly_pay/{driver}', 'DriverController@set_hourly_pay')->name('admin.set_hourly_pay');
        Route::resource('charge', 'ChargeController');
        Route::get('weather-surcharge', 'WeatherSurchargeController@index')->name('admin.weather-surcharge.index');
        Route::post('weather-surcharge', 'WeatherSurchargeController@update')->name('admin.weather-surcharge.update');
        Route::post('weather-surcharge/toggle', 'WeatherSurchargeController@toggle')->name('admin.weather-surcharge.toggle');
        Route::resource('extras', 'ExtrasController');
        Route::get('product/create', 'ProductController@create')->name('admin.product.create');
        Route::post('product', 'ProductController@store')->name('admin.product.store');
        Route::get('product/{product}', 'ProductController@show')->name('admin.product.show');
        Route::get('product/{product}/edit', 'ProductController@edit')->name('admin.product.edit');
        Route::put('product/{product}', 'ProductController@update')->name('admin.product.update');
        Route::delete('product/{product}', 'ProductController@destroy')->name('admin.product.destroy');
        Route::resource('user', 'UserController');
        Route::post('change_block_status/{user}', 'UserController@change_block_status')->name('admin.change_block_status');
        Route::get('all_orders', 'OrderController@all_orders')->middleware('module:food')->name('admin.all_orders');
        Route::get('notifications', 'DashboardController@notifications')->middleware('module:food')->name('admin.notifications');
        Route::get('complete_orders', 'OrderController@complete_orders')->name('admin.complete_orders');
        Route::get('prepaire_orders', 'OrderController@prepaire_orders')->name('admin.prepaire_orders');
        Route::get('cancel_orders', 'OrderController@cancel_orders')->name('admin.cancel_orders');
        Route::get('pending_orders', 'OrderController@pending_orders')->name('admin.pending_orders');
        Route::get('schedule_orders', 'OrderController@schedule_orders')->name('admin.schedule_orders');
        Route::post('cancel_order/{order}', 'OrderController@cancel_order')->name('admin.cancel_order');
        Route::post('assign_order/{order}', 'OrderController@assign_order')->name('admin.assign_order');
        Route::get('show_order/{order}', 'OrderController@show_order')->name('admin.show_order');
        Route::get('show_completed_order/{order}', 'OrderController@show_completed_order')->name('admin.show_completed_order');
        Route::post('deliver_order/{order}', 'OrderController@deliver_order')->name('admin.deliver_order');
        Route::post('resolve_incident/{order}', 'OrderController@resolveIncident')->name('admin.resolve_incident');
        Route::get('cash-disputes', 'OrderController@cashDisputes')->name('admin.cash_disputes');
        Route::post('cash-disputes/{orderNo}/resolve', 'OrderController@resolveCashDispute')->name('admin.cash_disputes.resolve');
        Route::post('assign_driver', 'OrderController@assign_driver')->name('admin.assign_driver');
        Route::post('prepaire_order', 'OrderController@prepaire_order')->name('admin.prepaire_order');
        Route::get('restaurant_payout', 'PayoutController@restaurant_payout')->name('restaurant_payout');
        Route::get('driver_payout', 'PayoutController@driver_payout')->name('driver_payout');
        Route::get('restaurant_payout/export-bulk-csv', 'PayoutController@exportRestaurantBulkCsv')->name('restaurant_payout.export_csv');
        Route::get('driver_payout/export-bulk-csv', 'PayoutController@exportDriverBulkCsv')->name('driver_payout.export_csv');
        Route::post('restaurant_pay', 'PayoutController@restaurant_pay')->name('restaurant_pay');
        Route::post('driver_pay', 'PayoutController@driver_pay')->name('driver_pay');
        // T1.1 — Admin : force pause/resume restaurant
        Route::get('restaurants/paused', 'RestaurantPauseController@index')->name('admin.restaurants.paused');
        Route::post('restaurants/{restaurant}/force-pause', 'RestaurantPauseController@forcePause')->name('admin.restaurants.force_pause');
        Route::post('restaurants/{restaurant}/force-resume', 'RestaurantPauseController@forceResume')->name('admin.restaurants.force_resume');
    }); // bantudelice

    // ================================================================
    // KENDE — transport & taxi
    // ================================================================
    Route::middleware('admin.workspace:kende')->group(function () {
        Route::resource('vehicle', 'VehicleController');
        Route::prefix('transport')->middleware('module:transport')->group(function () {
            Route::get('/', 'Transport\AdminTransportController@dashboard')->name('admin.transport.dashboard');
            Route::get('bookings', 'Transport\AdminTransportController@bookings')->name('admin.transport.bookings.index');
            Route::get('bookings/{id}', 'Transport\AdminTransportController@showBooking')->name('admin.transport.bookings.show');
            Route::get('vehicles', 'Transport\AdminTransportController@vehicles')->name('admin.transport.vehicles.index');
            Route::post('vehicles/{id}/approve', 'Transport\AdminTransportController@approveVehicle')->name('admin.transport.vehicles.approve');
            Route::post('vehicles/{id}/reject', 'Transport\AdminTransportController@rejectVehicle')->name('admin.transport.vehicles.reject');
            Route::get('pricing-rules', 'Transport\AdminTransportController@pricingRules')->name('admin.transport.pricing.index');
            Route::post('pricing-rules', 'Transport\AdminTransportController@storePricingRule')->name('admin.transport.pricing.store');
            Route::post('pricing-rules/{id}', 'Transport\AdminTransportController@updatePricingRule')->name('admin.transport.pricing.update');
        });
    }); // kende

    // ================================================================
    // MEMA — colis & logistique
    // ================================================================
    Route::middleware('admin.workspace:mema')->group(function () {
        Route::get('colis', 'ColisController@index')->middleware('module:colis')->name('admin.colis.index');
        Route::get('colis/export-csv', 'ColisController@exportCsv')->middleware('module:colis')->name('admin.colis.export-csv');
        Route::get('colis/finance', 'ColisController@financialIndex')->middleware('module:colis')->name('admin.colis.finance');
        Route::post('colis/reconcile/{courierId}', 'ColisController@reconcile')->middleware('module:colis')->name('admin.colis.reconcile');
        Route::get('colis/{shipment}', 'ColisController@show')->middleware('module:colis')->name('admin.colis.show');
        Route::get('colis/{shipment}/print', 'ColisController@print')->middleware('module:colis')->name('admin.colis.print');
        Route::post('colis/{shipment}/incident', 'ColisController@reportIncident')->middleware('module:colis')->name('admin.colis.report-incident');
        Route::get('relay-points', 'RelayPointController@index')->name('admin.relay-points.index');
        Route::get('relay-points/create', 'RelayPointController@create')->name('admin.relay-points.create');
        Route::post('relay-points', 'RelayPointController@store')->name('admin.relay-points.store');
        Route::post('relay-points/{relayPoint}/toggle', 'RelayPointController@toggle')->name('admin.relay-points.toggle');
    }); // mema

}); // admin

Route::group(['prefix' => 'restaurant', 'namespace' => 'restaurant', 'middleware' => ['auth', 'restaurant']], function () {
    //profile
    Route::get('profile', 'UserController@profile')->name('restaurant.profile');
    Route::get('delivery_boundary', 'HomeController@delivery_boundary')->name('delivery_boundary');

    Route::post('profile/profile_update', 'UserController@profile_update')->name('restaurant.profile.profile_update');
    Route::get('/', 'DashboardController@index')->name('restaurant.dashboard');
    // T1.1 — Disponibilité restaurant (pause E2C / météo)
    Route::get('availability/status',   'RestaurantAvailabilityController@status')->name('restaurant.availability.status');
    Route::post('availability/pause',   'RestaurantAvailabilityController@pause')->name('restaurant.availability.pause');
    Route::post('availability/resume',  'RestaurantAvailabilityController@resume')->name('restaurant.availability.resume');
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
    Route::post('change_product_featured_status/{product}', 'ProductController@change_product_featured_status')->name('restaurant.change_product_featured_status');
    //orders
    Route::get('all_orders', 'OrderController@all_orders')->name('restaurant.all_orders');
    Route::get('complete_orders', 'OrderController@complete_orders')->name('restaurant.complete_orders');
    Route::get('cancel_orders', 'OrderController@cancel_orders')->name('restaurant.cancel_orders');

    Route::post('prepaire_order', 'OrderController@prepaire_order')->name('restaurant.prepaire_orders');
    Route::get('all-preparing-orders', 'OrderController@getPreparingOrders')->name('restaurant.getpreparing');

    Route::get('assigned_orders', 'OrderController@pending_orders')->name('restaurant.pending_orders');
    Route::get('schedule_orders', 'OrderController@schedule_orders')->name('restaurant.schedule_orders');
    Route::post('cancel_order/{order}', 'OrderController@cancel_order')->name('restaurant.cancel_order');
    Route::post('assign_order/{order}', 'OrderController@assign_order')->name('restaurant.assign_order');
    Route::get('show_order/{order}', 'OrderController@show_order')->name('restaurant.show_order');
    Route::get('notifications', 'OrderController@notificationsForCurrentRestaurant')->name('restaurant.notifications.poll');

    Route::get('prepaire_order/{order}', 'OrderController@prepaire_order')->name('restaurant.prepaire_order');
    Route::post('deliver_order/{order}', 'OrderController@deliver_order')->name('restaurant.deliver_order');
    Route::post('assign_driver', 'OrderController@assign_driver')->name('restaurant.assign_driver');
    Route::post('orders/{orderNo}/cash-dispute', 'OrderController@disputeCashCollection')->name('restaurant.orders.cash_dispute');
    //payment
    Route::resource('r_earnings', 'PaymentHistoryController');
    Route::post('withdrawals', 'WithdrawController@store')->name('restaurant.withdrawals.store')->middleware('throttle:3,10');
    //employee
    Route::resource('employee', 'EmployeeController');
    //working hours
    Route::resource('working_hour', 'WorkingHourController');
    Route::get('special-closures/create', 'RestaurantSpecialClosureController@create')->name('restaurant.special_closures.create');
    Route::post('special-closures', 'RestaurantSpecialClosureController@store')->name('restaurant.special_closures.store');
    Route::get('special-closures/{specialClosure}/edit', 'RestaurantSpecialClosureController@edit')->name('restaurant.special_closures.edit');
    Route::put('special-closures/{specialClosure}', 'RestaurantSpecialClosureController@update')->name('restaurant.special_closures.update');
    Route::delete('special-closures/{specialClosure}', 'RestaurantSpecialClosureController@destroy')->name('restaurant.special_closures.destroy');

    // Avis clients
    Route::get('ratings', 'RatingsController@index')->name('restaurant.ratings');

    // Analytics
    Route::get('analytics', 'AnalyticsController@index')->name('restaurant.analytics');

    // Kitchen display (polling JSON)
    Route::get('kitchen', 'KitchenController@index')->middleware('module:food')->name('restaurant.kitchen');
    Route::get('kitchen/orders', 'KitchenController@orders')->middleware('module:food')->name('restaurant.kitchen.orders');
    Route::patch('kitchen/orders/{orderNo}/status', 'KitchenController@updateStatus')->middleware('module:food')->name('restaurant.kitchen.orders.status');

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

    // Sidebar stats (polling)
    Route::get('sidebar-stats', 'DashboardController@sidebarStats')->name('restaurant.sidebar.stats');

});
Route::group(['prefix' => 'restaurant/delivery', 'namespace' => 'delivery', 'middleware' => ['auth', 'delivery']], function () {
    Route::get('/', 'DashboardController@index')->name('delivery.dashboard');
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
