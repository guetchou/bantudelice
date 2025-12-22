# RQC - INVENTAIRE ROUTES BANTUDELICE

**Date**: 2025-12-15  
**Source**: `php artisan route:list` + analyse `routes/web.php` + `routes/api.php`

---

## 1. RÉSUMÉ GLOBAL

- **Total routes**: 333
- **Routes Web**: ~213
- **Routes API**: ~120
- **Routes protégées**: ~129 (39%)
- **Routes publiques**: ~204 (61%)

---

## 2. ROUTES WEB (routes/web.php)

### 2.1 Routes publiques (87 routes)

#### Accueil & Navigation
```
GET  /                              → IndexController@home
GET  /about-us                      → IndexController@about
GET  /contact-us                    → IndexController@contact
POST /contact-us                    → ContactController@ContactUs
GET  /faq                           → IndexController@faq
GET  /help                          → IndexController@help
GET  /offers                         → IndexController@offers
GET  /terms-and-conditions          → IndexController@terms
GET  /return-policy                 → IndexController@refundPolicy
```

#### Authentification
```
GET  /login                         → LoginController@login_view
POST /login                         → LoginController@login
GET  /logout                        → LoginController@logout
GET  /signup                        → IndexController@SignUp
POST /signup                        → IndexController@register
GET  /forgot-password               → IndexController@forgot
GET  /user/login                    → IndexController@Login
GET  /user/forgot                   → IndexController@forgot
POST /user/forgot-password          → IndexController@forgotPassword
GET  /user-logout                   → IndexController@logout
```

#### Restaurants & Produits
```
GET  /resturant/view/{id}           → IndexController@resturantDetail
GET  /product/view/{id}             → IndexController@proDetail
GET  /restaurants                   → IndexController@allRestaurants
GET  /restaurants/cuisine/{id}      → IndexController@restaurantByCuisine
GET  /search/                       → IndexController@searchResult
GET  /search/ajax                   → IndexController@searchAjax
```

#### Panier & Commande
```
GET  /cart                          → IndexController@cartDeatil
POST /cart                          → IndexController@addToCart
GET  /cart/count                    → IndexController@getCartCount
GET  /cart/deleteItem/{id}          → IndexController@deleteItem
PUT  /cart/update/{cart}            → IndexController@updateItem
GET  /checkout                      → IndexController@Checkout
POST /checkout/order                → IndexController@getOrders
GET  /cart/checkout/stripe          → IndexController@stripe
POST /cart/checkout/stripe          → IndexController@stripePost
GET  /cart/checkout/thankyou        → IndexController@thanks
POST /voucher                       → IndexController@checkVoucher
GET  /track-order/{orderNo?}       → IndexController@trackOrder
```

#### Paiement
```
POST /paypal                        → PaypalController@payWithpaypal
GET  /status                        → PaypalController@getPaymentStatus
```

#### Profil utilisateur
```
GET  /profile                       → IndexController@profile
POST /profile/update                → IndexController@updateProfile
POST /profile/password              → IndexController@updatePassword
POST /profile/avatar                → IndexController@updateAvatar
```

#### Inscription partenaires
```
GET  /driver/registration           → DriverController@driver
POST /driver/registration          → DriverController@driverRegistration
GET  /partner/registration         → PartnerController@partner
POST /partner/registration         → PartnerController@partnerRegistration
```

#### Livraisons (protégé par `auth`)
```
GET  /driver/deliveries            → DriverDeliveriesController@index
POST /driver/deliveries/{delivery}/status → DriverDeliveriesController@updateStatus
```

### 2.2 Routes Admin (`middleware: ['auth', 'admin']`)

**Total**: 67 routes

#### Dashboard
```
GET  /admin                         → admin\DashboardController@index
GET  /admin/all-products            → admin\HomeController@totalPro
```

#### Gestion Restaurants
```
GET    /admin/restaurant                    → admin\RestaurantController@index
POST   /admin/restaurant                    → admin\RestaurantController@store
GET    /admin/restaurant/create             → admin\RestaurantController@create
GET    /admin/restaurant/{restaurant}       → admin\RestaurantController@show
PUT    /admin/restaurant/{restaurant}       → admin\RestaurantController@update
DELETE /admin/restaurant/{restaurant}       → admin\RestaurantController@destroy
GET    /admin/restaurant/{restaurant}/edit   → admin\RestaurantController@edit
GET    /admin/pending                        → admin\RestaurantController@pending
GET    /admin/get_service_charges/{restaurant} → admin\RestaurantController@get_service_charges
POST   /admin/set_service_charges/{restaurant} → admin\RestaurantController@set_service_charges
GET    /admin/change_restaurant_active_status/{restaurant} → admin\RestaurantController@change_restaurant_active_status
GET    /admin/change_restaurant_featured_status/{restaurant} → admin\RestaurantController@change_restaurant_featured_status
```

#### Gestion Commandes
```
GET  /admin/all_orders              → admin\OrderController@all_orders
GET  /admin/complete_orders         → admin\OrderController@complete_orders
GET  /admin/prepaire_orders         → admin\OrderController@prepaire_orders
GET  /admin/cancel_orders           → admin\OrderController@cancel_orders
GET  /admin/pending_orders          → admin\OrderController@pending_orders
GET  /admin/schedule_orders         → admin\OrderController@schedule_orders
POST /admin/cancel_order/{order}    → admin\OrderController@cancel_order
GET  /admin/assign_order/{order}    → admin\OrderController@assign_order
GET  /admin/show_order/{order}      → admin\OrderController@show_order
GET  /admin/show_completed_order/{order} → admin\OrderController@show_completed_order
GET  /admin/deliver_order/{order}   → admin\OrderController@deliver_order
POST /admin/assign_driver           → admin\OrderController@assign_driver
POST /admin/prepaire_order          → admin\OrderController@prepaire_order
```

#### Autres ressources (CRUD)
- `/admin/cuisine` (7 routes)
- `/admin/news` (8 routes)
- `/admin/driver` (8 routes)
- `/admin/vehicle` (7 routes)
- `/admin/extras` (7 routes)
- `/admin/user` (7 routes)
- `/admin/charge` (7 routes)

#### Configuration API
```
GET  /admin/api-configuration       → admin\ApiConfigurationController@index
GET  /admin/api/status              → admin\ApiConfigurationController@getStatus
GET  /admin/api/keys                → admin\ApiConfigurationController@getApiKeys
POST /admin/api/keys/save           → admin\ApiConfigurationController@saveApiKeys
POST /admin/api/test/sms            → admin\ApiConfigurationController@testSms
POST /admin/api/test/otp            → admin\ApiConfigurationController@testOtp
POST /admin/api/test/geolocation    → admin\ApiConfigurationController@testGeolocation
POST /admin/api/test/momo           → admin\ApiConfigurationController@testMobileMoney
POST /admin/api/clear-cache         → admin\ApiConfigurationController@clearCache
GET  /admin/api/mail/status         → admin\ApiConfigurationController@getMailStatus
POST /admin/api/mail/save           → admin\ApiConfigurationController@saveMailConfig
POST /admin/api/test/email         → admin\ApiConfigurationController@testEmail
```

#### Payouts
```
GET  /admin/restaurant_payout        → admin\PayoutController@restaurant_payout
GET  /admin/driver_payout            → admin\PayoutController@driver_payout
POST /admin/restaurant_pay          → admin\PayoutController@restaurant_pay
POST /admin/driver_pay               → admin\PayoutController@driver_pay
```

#### Profil Admin
```
GET  /admin/profile                 → admin\UserController@profile
POST /admin/profile/update          → admin\UserController@passwordUpdate
```

### 2.3 Routes Restaurant (`middleware: ['auth', 'restaurant']`)

**Total**: 45 routes

#### Dashboard & Profil
```
GET  /restaurant                    → restaurant\DashboardController@index
GET  /restaurant/profile            → restaurant\UserController@profile
POST /restaurant/profile/profile_update → restaurant\UserController@profile_update
GET  /restaurant/delivery_boundary   → restaurant\HomeController@delivery_boundary
GET  /restaurant/notifications/{id} → restaurant\DashboardController@notifications
```

#### Gestion Produits
```
GET    /restaurant/product                  → restaurant\ProductController@index
POST   /restaurant/product                  → restaurant\ProductController@store
GET    /restaurant/product/create           → restaurant\ProductController@create
GET    /restaurant/product/{product}        → restaurant\ProductController@show
PUT    /restaurant/product/{product}        → restaurant\ProductController@update
DELETE /restaurant/product/{product}        → restaurant\ProductController@destroy
GET    /restaurant/product/{product}/edit   → restaurant\ProductController@edit
GET    /restaurant/change_product_featured_status/{product} → restaurant\ProductController@change_product_featured_status
```

#### Autres ressources (CRUD)
- `/restaurant/category` (8 routes)
- `/restaurant/add-on` (7 routes)
- `/restaurant/voucher` (7 routes)
- `/restaurant/optional` (7 routes)
- `/restaurant/required` (7 routes)
- `/restaurant/employee` (7 routes)
- `/restaurant/working_hour` (7 routes)
- `/restaurant/r_earnings` (7 routes)

#### Commandes
```
GET  /restaurant/all_orders          → restaurant\OrderController@all_orders
GET  /restaurant/complete_orders      → restaurant\OrderController@complete_orders
GET  /restaurant/cancel_orders        → restaurant\OrderController@cancel_orders
POST /restaurant/prepaire_order      → restaurant\OrderController@prepaire_order
GET  /restaurant/all-preparing-orders → restaurant\OrderController@getPreparingOrders
GET  /restaurant/assigned_orders     → restaurant\OrderController@pending_orders
GET  /restaurant/schedule_orders     → restaurant\OrderController@schedule_orders
GET  /restaurant/cancel_order/{order} → restaurant\OrderController@cancel_order
GET  /restaurant/assign_order/{order} → restaurant\OrderController@assign_order
GET  /restaurant/show_order/{order}   → restaurant\OrderController@show_order
GET  /restaurant/prepaire_order/{order} → restaurant\OrderController@prepaire_order
GET  /restaurant/deliver_order/{order} → restaurant\OrderController@deliver_order
POST /restaurant/assign_driver        → restaurant\OrderController@assign_driver
```

### 2.4 Routes Delivery (`middleware: ['auth', 'delivery']`)

**Total**: 12 routes

```
GET    /restaurant/delivery                    → delivery\DashboardController@index
POST   /restaurant/delivery                    → delivery\DashboardController@store
GET    /restaurant/delivery/create              → delivery\DashboardController@create
GET    /restaurant/delivery/{}                  → delivery\DashboardController@show
PUT    /restaurant/delivery/{}                 → delivery\DashboardController@update
DELETE /restaurant/delivery/{}                 → delivery\DashboardController@destroy
GET    /restaurant/delivery/{}/edit            → delivery\DashboardController@edit
GET    /restaurant/delivery/all_orders          → delivery\OrderController@all_orders
GET    /restaurant/delivery/complete_orders     → delivery\OrderController@complete_orders
GET    /restaurant/delivery/cancel_orders       → delivery\OrderController@cancel_orders
GET    /restaurant/delivery/pending_orders      → delivery\OrderController@pending_orders
GET    /restaurant/delivery/schedule_orders      → delivery\OrderController@schedule_orders
POST   /restaurant/delivery/cancel_order/{order} → delivery\OrderController@cancel_order
```

**⚠️ ANOMALIE**: Routes avec `{}` au lieu de paramètres nommés.

### 2.5 Routes Passport (OAuth)
```
GET    /oauth/authorize                        → Laravel\Passport\AuthorizationController@authorize
POST   /oauth/authorize                        → Laravel\Passport\AuthorizationController@approve
DELETE /oauth/authorize                        → Laravel\Passport\AuthorizationController@deny
GET    /oauth/clients                          → Laravel\Passport\ClientController@index
POST   /oauth/clients                          → Laravel\Passport\ClientController@store
PUT    /oauth/clients/{client_id}              → Laravel\Passport\ClientController@update
DELETE /oauth/clients/{client_id}              → Laravel\Passport\ClientController@destroy
GET    /oauth/personal-access-tokens           → Laravel\Passport\PersonalAccessTokenController@index
POST   /oauth/personal-access-tokens           → Laravel\Passport\PersonalAccessTokenController@store
DELETE /oauth/personal-access-tokens/{token_id} → Laravel\Passport\PersonalAccessTokenController@destroy
GET    /oauth/scopes                           → Laravel\Passport\ScopeController@index
POST   /oauth/token                            → Laravel\Passport\AccessTokenController@issueToken
POST   /oauth/token/refresh                    → Laravel\Passport\TransientTokenController@refresh
GET    /oauth/tokens                           → Laravel\Passport\AuthorizedAccessTokenController@forUser
DELETE /oauth/tokens/{token_id}                → Laravel\Passport\AuthorizedAccessTokenController@destroy
```

---

## 3. ROUTES API (routes/api.php)

### 3.1 Routes publiques (~90 routes)

#### Authentification
```
POST /api/register                 → api\UserController@register
POST /api/login                    → api\UserController@login
POST /api/forgot_password          → api\UserController@forgotPassword
GET  /api/user_profile/{user}      → api\UserController@profile
POST /api/update_profile/          → api\UserController@updateProfile
```

#### Driver APIs
```
POST /api/driver_register          → api\DriverController@register
POST /api/driver_login             → api\DriverController@login
GET  /api/driver_profile/{driver}  → api\DriverController@profile
POST /api/driver_update_profile/   → api\DriverController@updateProfile
POST /api/driver_forgot_password   → api\DriverController@forgotPassword
POST /api/set_driver_online/{driver} → api\DriverController@SetDriverOnline
GET  /api/set_driver_offline/{driver} → api\DriverController@SetDriverOffline
POST /api/set_time_for_online     → api\DriverController@SetDriverOnlineTime
GET  /api/order_request/{dirver}   → api\DriverController@orderRequests
POST /api/order_accept_by_driver   → api\DriverController@acceptOrderRequests
GET  /api/ordered_product/{orderno} → api\DriverController@ordersProducts
GET  /api/driver_reviews/{dirver}  → api\ReviewController@driverReviews
POST /api/driver_earning_history/{dirver} → api\DriverController@driverEarningHistory
GET  /api/delivery_summary/{driver} → api\DriverController@deliverySummary
GET  /api/latest_news              → api\DriverController@latestNews
POST /api/driver/{driverId}/location → api\DriverController@updateLocation
```

#### Home & Recherche
```
POST /api/home_data                → api\IndexController@index
GET  /api/product_detail/{product}  → api\IndexController@proDetail
POST /api/search_filters           → api\IndexController@searchFilter
POST /api/search_by_keyword        → api\IndexController@searchQurey
GET  /api/restaurant_detail/{restaurant} → api\IndexController@restaurantDetail
GET  /api/order/{orderNo}/status   → api\IndexController@getOrderStatus
```

#### Panier
```
POST   /api/add_to_cart            → api\CartController@addToCart
GET    /api/show_cart_details/{user} → api\CartController@showCartDetail
POST   /api/update_cart_details    → api\CartController@UpdateCartDetail
DELETE /api/delete_cart_product/{cart} → api\CartController@deleteCartProduct
DELETE /api/delete_previous_cart/{user} → api\CartController@deletePreviousCart
```

#### Commandes
```
POST /api/place_orders/            → api\OrderController@getOrders
GET  /api/user_pending_orders/{user} → api\OrderController@UserOrderHistory
GET  /api/user_completed_order_history/{user} → api\OrderController@UserCompletedOrderHistory
POST /api/complete_orders          → api\OrderController@completeOrders
```

#### Restaurants
```
POST /api/search_restaurant        → api\RestaurantController@search
GET  /api/restaurants_with_category/{cuisine} → api\RestaurantController@restaurantsByCuisine
GET  /api/get_filters              → api\RestaurantController@sendFilters
GET  /api/about_restaurant/{restaurant} → api\RestaurantController@restaurantAbout
GET  /api/restaurants/popular       → api\RestaurantController@popular
GET  /api/restaurants              → api\RestaurantController@index
GET  /api/restaurants/{id}/reviews → api\RestaurantController@getReviews
GET  /api/restaurants/{id}/promos  → api\RestaurantController@getActivePromos
```

#### Utilisateur
```
POST /api/add_user_address         → api\UserController@addUserAddress
GET  /api/get_user_address/{user}  → api\UserController@getUserAddress
POST /api/track_pendings_orders    → api\UserController@trackOrders
POST /api/track_completed_orders   → api\UserController@trackCompletedOrders
POST /api/reviews_and_ratings      → api\UserController@sendReviewsToRestaurant
POST /api/user_device_token        → api\UserController@userDeviceToken
```

#### Vouchers & Raisons
```
POST /api/get_voucher              → api\VoucherController@getVoucher
GET  /api/get_reason               → api\ReasonController@getReason
POST /api/reject_order_request     → api\ReasonController@rejectOrderRequests
```

#### Ratings
```
POST /api/orders/{order}/rating    → api\OrderRatingController@store
GET  /api/orders/{order}/rating    → api\OrderRatingController@show
GET  /api/orders/{order}/rating/check → api\OrderRatingController@check
```

### 3.2 Routes protégées (`middleware: 'auth:sanctum'`)

**Total**: 5 routes

```
GET  /api/driver/deliveries        → api\DriverDeliveriesController@index
PATCH /api/driver/deliveries/{delivery}/status → api\DriverDeliveriesController@updateStatus
GET  /api/orders/{order}/tracking  → api\OrderTrackingController@show
POST /api/checkout                 → api\CheckoutController@__invoke
GET  /api/payments/{payment}       → api\PaymentController@show
POST /api/payments/{payment}/confirm → api\PaymentController@confirm
```

### 3.3 Callback Payment (public)
```
POST /api/payments/callback/{provider} → api\PaymentCallbackController@handle
```

**⚠️ RISQUE**: Endpoint sensible sans authentification. Devrait être protégé par IP whitelist ou signature.

---

## 4. ANALYSE SÉCURITÉ

### 4.1 Routes critiques non protégées

#### ❌ **CRITIQUE 1**: Endpoints de commande
- `POST /api/place_orders/` - Publique
- `POST /api/complete_orders` - Publique
- `GET /api/user_pending_orders/{user}` - Publique (ID utilisateur exposé)

#### ❌ **CRITIQUE 2**: Endpoints de panier
- `POST /api/add_to_cart` - Publique
- `POST /api/update_cart_details` - Publique
- `DELETE /api/delete_cart_product/{cart}` - Publique

#### ❌ **CRITIQUE 3**: Endpoints de profil
- `GET /api/user_profile/{user}` - Publique (ID utilisateur exposé)
- `POST /api/update_profile/` - Publique
- `GET /api/driver_profile/{driver}` - Publique

#### ⚠️ **CRITIQUE 4**: Endpoints de paiement
- `POST /api/payments/callback/{provider}` - Publique (devrait être IP whitelist)

### 4.2 Routes avec ID utilisateur exposé

**Problème**: Routes utilisant `{user}` ou `{driver}` dans l'URL sans vérification d'autorisation.

**Exemples**:
- `GET /api/user_profile/{user}`
- `GET /api/show_cart_details/{user}`
- `GET /api/driver_profile/{driver}`

**Risque**: Accès aux données d'autres utilisateurs si ID deviné.

---

## 5. RECOMMANDATIONS

### 5.1 Actions immédiates (P0)
1. ✅ Protéger toutes les routes API sensibles avec `auth:sanctum`
2. ✅ Ajouter vérification d'autorisation sur routes avec `{user}` ou `{driver}`
3. ✅ Protéger callback payment par IP whitelist ou signature

### 5.2 Actions importantes (P1)
1. Implémenter rate limiting sur routes publiques
2. Ajouter validation stricte sur tous les endpoints
3. Documenter les routes dans OpenAPI/Swagger

### 5.3 Actions recommandées (P2)
1. Regrouper routes API par domaine fonctionnel
2. Ajouter versioning API (`/api/v1/`)
3. Implémenter logging des accès aux routes sensibles

---

## 6. STATUT

**STATUT**: ⚠️ **NON CONFORME**

**Raisons**:
- 61% des routes sont publiques (risque élevé)
- Routes critiques non protégées
- IDs utilisateurs exposés dans URLs

