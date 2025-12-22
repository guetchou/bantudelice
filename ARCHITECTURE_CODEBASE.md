# ARCHITECTURE COMPLÈTE DE LA CODEBASE
## Analyse Topographique et Échographique du Projet BantuDelice/TheDrop247

**Date d'analyse :** 2025-01-27  
**Projet :** BantuDelice / TheDrop247  
**Type :** Plateforme de livraison de nourriture (Food Delivery Platform)  
**Framework Backend :** Laravel 10.10  
**PHP :** 8.1+

---

## 📋 TABLE DES MATIÈRES

1. [Vue d'ensemble](#vue-densemble)
2. [Stack Technologique](#stack-technologique)
3. [Architecture Globale](#architecture-globale)
4. [Structure des Répertoires](#structure-des-répertoires)
5. [Modèles de Données](#modèles-de-données)
6. [Couche Contrôleurs](#couche-contrôleurs)
7. [Couche Services](#couche-services)
8. [Routes et API](#routes-et-api)
9. [Frontend](#frontend)
10. [Base de Données](#base-de-données)
11. [Authentification et Sécurité](#authentification-et-sécurité)
12. [Intégrations Externes](#intégrations-externes)
13. [Workflow Utilisateur](#workflow-utilisateur)
14. [Points d'Attention](#points-dattention)

---

## 1. VUE D'ENSEMBLE

### 1.1 Description
BantuDelice/TheDrop247 est une plateforme complète de livraison de nourriture permettant :
- **Clients** : Commander de la nourriture, gérer panier, suivi commandes
- **Restaurants** : Gérer produits, catégories, commandes, employés
- **Livreurs** : Gérer livraisons, statut en ligne/hors ligne, gains
- **Administrateurs** : Gestion globale plateforme, restaurants, livreurs, paiements

### 1.2 Statistiques
- **Modèles Eloquent :** ~30 modèles
- **Contrôleurs :** ~25 contrôleurs
- **Routes Web :** ~90 routes
- **Routes API :** ~35 endpoints
- **Middleware :** 7 middleware personnalisés
- **Vues Blade :** ~50 templates
- **Tables DB :** ~30 tables
- **Migrations :** 30+ migrations

---

## 2. STACK TECHNOLOGIQUE

### 2.1 Backend
```
PHP 8.1+
├── Laravel Framework 10.10
├── Laravel Passport 11.9 (OAuth2 API)
├── Laravel Sanctum 3.2 (SPA/Token Auth)
├── Guzzle HTTP 7.2 (Client HTTP)
└── Composer (Gestionnaire de dépendances)
```

### 2.2 Frontend
```
JavaScript/Assets
├── Laravel Mix 5.0.1 (Build tool)
├── Webpack (Compilation)
├── SASS 1.15.2 (Préprocesseur CSS)
├── Axios 0.19 (Client HTTP)
├── jQuery (via plugins)
├── Bootstrap 5 (UI Framework)
└── Blade Templates (Moteur de template Laravel)
```

### 2.3 Base de Données
```
MySQL (par défaut)
├── Support PostgreSQL
├── Support SQLite
└── Redis (Cache/Sessions)
```

### 2.4 Outils de Développement
```
├── PHPUnit (Tests)
├── Laravel Pint (Code Style)
├── Laravel Sail (Docker)
└── Mockery (Mocking)
```

---

## 3. ARCHITECTURE GLOBALE

### 3.1 Pattern Architectural
**Architecture MVC (Model-View-Controller) avec couche Service**

```
┌─────────────────────────────────────────────────┐
│                  FRONTEND                        │
│  ┌──────────────┐  ┌──────────────┐           │
│  │   Web Views  │  │  API Clients  │           │
│  │   (Blade)    │  │  (Mobile/Web) │           │
│  └──────┬───────┘  └──────┬───────┘           │
└─────────┼──────────────────┼────────────────────┘
          │                  │
          ▼                  ▼
┌─────────────────────────────────────────────────┐
│              ROUTES LAYER                       │
│  ┌──────────────┐  ┌──────────────┐           │
│  │  Web Routes  │  │  API Routes  │           │
│  │  (web.php)   │  │  (api.php)   │           │
│  └──────┬───────┘  └──────┬───────┘           │
└─────────┼──────────────────┼────────────────────┘
          │                  │
          ▼                  ▼
┌─────────────────────────────────────────────────┐
│          MIDDLEWARE LAYER                       │
│  ┌──────────────────────────────────────────┐  │
│  │  Auth, Admin, Restaurant, Delivery, User │  │
│  └──────────────────────────────────────────┘  │
└─────────┼──────────────────────────────────────┘
          │
          ▼
┌─────────────────────────────────────────────────┐
│         CONTROLLERS LAYER                       │
│  ┌──────────────┐  ┌──────────────┐           │
│  │ Web Controllers│ │ API Controllers│         │
│  └──────┬───────┘  └──────┬───────┘           │
└─────────┼──────────────────┼────────────────────┘
          │                  │
          ▼                  ▼
┌─────────────────────────────────────────────────┐
│          SERVICES LAYER                         │
│  ┌──────────────────────────────────────────┐  │
│  │ PaymentService, MobileMoneyService,       │  │
│  │ DeliveryService, NotificationService, etc.│  │
│  └──────────────────────────────────────────┘  │
└─────────┼──────────────────────────────────────┘
          │
          ▼
┌─────────────────────────────────────────────────┐
│           MODELS LAYER                          │
│  ┌──────────────────────────────────────────┐  │
│  │ User, Restaurant, Order, Product, etc.    │  │
│  └──────────────────────────────────────────┘  │
└─────────┼──────────────────────────────────────┘
          │
          ▼
┌─────────────────────────────────────────────────┐
│        DATABASE LAYER                           │
│  ┌──────────────────────────────────────────┐  │
│  │ MySQL / PostgreSQL / SQLite              │  │
│  └──────────────────────────────────────────┘  │
└─────────────────────────────────────────────────┘
```

### 3.2 Types d'Utilisateurs et Rôles

| Type | Description | Namespace | Middleware |
|------|-------------|-----------|------------|
| `admin` | Administrateur global | `admin/` | `admin` |
| `restaurant` | Gestionnaire restaurant | `restaurant/` | `restaurant` |
| `delivery` | Livreur | `delivery/` | `delivery` |
| `user` | Client final | - | `user` |

---

## 4. STRUCTURE DES RÉPERTOIRES

```
/opt/bantudelice242/
│
├── app/                          # Code source principal
│   ├── Http/
│   │   ├── Controllers/          # Contrôleurs MVC
│   │   │   ├── admin/            # Contrôleurs admin (13 fichiers)
│   │   │   ├── api/              # Contrôleurs API (14 fichiers)
│   │   │   ├── restaurant/       # Contrôleurs restaurant
│   │   │   ├── delivery/         # Contrôleurs livreur
│   │   │   └── Auth/             # Contrôleurs authentification
│   │   ├── Middleware/           # Middlewares personnalisés
│   │   │   ├── AdminMiddleware.php
│   │   │   ├── RestaurantMiddleware.php
│   │   │   ├── DeliveryMiddleware.php
│   │   │   └── UserMiddleware.php
│   │   └── Kernel.php            # Configuration middleware
│   │
│   ├── Models/                   # Modèles Eloquent (~30 modèles)
│   │   ├── User.php
│   │   ├── Restaurant.php
│   │   ├── Order.php
│   │   ├── Product.php
│   │   ├── Cart.php
│   │   ├── Driver.php
│   │   ├── Category.php
│   │   ├── Cuisine.php
│   │   ├── Voucher.php
│   │   ├── Rating.php
│   │   ├── Payment.php
│   │   ├── Delivery.php
│   │   └── ... (autres modèles)
│   │
│   ├── Services/                 # Couche service (17 services)
│   │   ├── PaymentService.php
│   │   ├── MobileMoneyService.php
│   │   ├── DeliveryService.php
│   │   ├── NotificationService.php
│   │   ├── SmsService.php
│   │   ├── GeolocationService.php
│   │   ├── RestaurantService.php
│   │   ├── CartService.php
│   │   ├── CheckoutService.php
│   │   ├── RatingService.php
│   │   ├── LoyaltyService.php
│   │   ├── SocialAuthService.php
│   │   ├── ImageUploadService.php
│   │   ├── ConfigService.php
│   │   ├── EnvConfigService.php
│   │   └── DataSyncService.php
│   │
│   ├── Mail/                     # Classes de notification email
│   ├── Providers/               # Service providers
│   ├── Exceptions/               # Exceptions personnalisées
│   ├── Helpers/                  # Helpers personnalisés
│   └── helpers.php               # Fonctions helpers globales
│
├── bootstrap/                    # Fichiers de démarrage Laravel
│   └── app.php
│
├── config/                       # Fichiers de configuration
│   ├── app.php
│   ├── database.php
│   ├── auth.php
│   ├── external-services.php    # Configuration services externes
│   ├── paypal.php
│   └── ...
│
├── database/
│   ├── migrations/               # Migrations DB (30+ fichiers)
│   ├── seeders/                  # Seeders
│   ├── factories/                # Factories pour tests
│   └── seeds/                    # Seeds (ancien format)
│
├── public/                       # Point d'entrée web
│   ├── index.php
│   ├── assets/                   # Assets compilés
│   ├── css/                      # CSS compilés
│   ├── js/                       # JavaScript compilés
│   ├── images/                   # Images
│   ├── frontend/                 # Assets frontend
│   └── plugins/                  # Plugins tiers
│
├── resources/
│   ├── js/                       # JavaScript source
│   │   ├── app.js
│   │   └── bootstrap.js
│   ├── sass/                     # SASS source
│   │   └── app.scss
│   ├── views/                    # Templates Blade
│   │   ├── layouts/              # Layouts
│   │   ├── frontend/             # Vues frontend
│   │   ├── admin/                # Vues admin
│   │   ├── restaurant/           # Vues restaurant
│   │   ├── driver/               # Vues livreur
│   │   ├── auth/                 # Vues authentification
│   │   └── mail/                 # Templates email
│   └── lang/                     # Fichiers de traduction
│
├── routes/                       # Définition des routes
│   ├── web.php                   # Routes web (~90 routes)
│   ├── api.php                   # Routes API (~35 endpoints)
│   ├── channels.php              # Routes broadcasting
│   └── console.php               # Routes console
│
├── storage/                      # Fichiers de stockage
│   ├── app/                      # Fichiers uploadés
│   ├── framework/                # Cache, sessions, views
│   └── logs/                     # Logs application
│
├── tests/                        # Tests PHPUnit
│
├── vendor/                       # Dépendances Composer
│
├── node_modules/                # Dépendances NPM
│
├── composer.json                 # Dépendances PHP
├── composer.lock
├── package.json                  # Dépendances JavaScript
├── pnpm-lock.yaml               # Lock file pnpm
├── webpack.mix.js               # Configuration Laravel Mix
├── phpunit.xml                  # Configuration PHPUnit
└── artisan                      # CLI Laravel
```

---

## 5. MODÈLES DE DONNÉES

### 5.1 Modèles Principaux

#### User (`app/User.php`)
**Rôle :** Authentification multi-rôles  
**Authentification :** Laravel Passport (HasApiTokens)

**Champs fillable :**
- `name`, `email`, `password`, `phone`, `type`, `image`

**Relations :**
- `restaurant()` : hasOne - Restaurant associé (si type='restaurant')
- `order()` : hasMany - Commandes de l'utilisateur
- `cancellation_reasons()` : hasMany - Raisons d'annulation
- `loyaltyPoints()` : hasOne - Points de fidélité
- `loyaltyTransactions()` : hasMany - Transactions fidélité

**Types utilisateur :**
- `admin`, `restaurant`, `delivery`, `user`

---

#### Restaurant (`app/Restaurant.php`)
**Rôle :** Gestion des restaurants

**Champs fillable :**
- `user_id`, `name`, `email`, `password`, `city`, `address`, `phone`
- `description`, `user_name`, `slogan`, `logo`, `cover_image`
- `latitude`, `longitude`, `min_order`, `avg_delivery_time`
- `services`, `account_name`, `account_number`, `bank_name`, `branch_name`

**Relations :**
- `cuisines()` : belongsToMany - Types de cuisines
- `drivers()` : hasMany - Livreurs associés
- `orders()` : hasMany - Commandes
- `payments()` : hasMany - Paiements reçus
- `categories()` : hasMany - Catégories de produits
- `products()` : hasMany - Produits
- `employees()` : hasMany - Employés
- `working_hours()` : hasMany - Horaires d'ouverture
- `ratings()` : hasMany - Avis/notes
- `cart()` : hasMany - Paniers
- `vouchers()` : hasMany - Bons de réduction

---

#### Order (`app/Order.php`)
**Rôle :** Gestion des commandes

**Champs fillable :**
- `restaurant_id`, `user_id`, `product_id`, `qty`, `price`
- `latitude`, `longitude`, `offer_discount`, `tax`
- `delivery_charges`, `sub_total`, `total`
- `admin_commission`, `restaurant_commission`, `driver_tip`
- `delivery_address`, `scheduled_date`, `ordered_time`, `delivered_time`
- `order_no`, `d_lat`, `d_lng`, `payment_method`, `payment_status`, `status`, `driver_id`

**Relations :**
- `restaurant()` : belongsTo - Restaurant
- `product()` : belongsTo - Produit
- `driver()` : belongsTo - Livreur
- `user()` : belongsTo - Client
- `rating()` : hasOne - Note/avis
- `delivery()` : hasOne - Livraison
- `payment()` : hasOne - Paiement

---

#### Product (`app/Product.php`)
**Rôle :** Produits des restaurants

**Relations :**
- `restaurant()` : belongsTo
- `category()` : belongsTo
- `orders()` : hasMany
- `extras()` : belongsToMany

---

#### Cart (`app/Cart.php`)
**Rôle :** Panier d'achat

**Relations :**
- `user()` : belongsTo
- `restaurant()` : belongsTo
- `product()` : belongsTo

---

#### Driver (`app/Driver.php`)
**Rôle :** Livreurs

**Relations :**
- `restaurant()` : belongsTo
- `vehicle()` : belongsTo
- `orders()` : hasMany
- `payments()` : hasMany

---

### 5.2 Modèles Secondaires

- **Category** : Catégories de produits
- **Cuisine** : Types de cuisines
- **Voucher** : Codes promo/bons de réduction
- **Rating** : Avis et notations
- **Review** : Commentaires détaillés
- **Payment** : Paiements
- **Delivery** : Livraisons
- **Employee** : Employés restaurants
- **WorkingHour** : Horaires d'ouverture
- **Vehicle** : Véhicules livreurs
- **Extra** : Extras produits
- **Optional** : Options produits
- **Required** : Requis produits
- **Charge** : Frais système
- **LoyaltyPoint** : Points de fidélité
- **LoyaltyTransaction** : Transactions fidélité
- **Address** : Adresses utilisateurs
- **Contact** : Messages contact
- **News** : Actualités système

---

## 6. COUCHE CONTRÔLEURS

### 6.1 Contrôleurs Web

#### Frontend (`app/Http/Controllers/`)
- **IndexController** : Page d'accueil, détails restaurant, panier, checkout, recherche
- **HomeController** : Tableau de bord
- **LoginController** : Authentification web
- **ContactController** : Formulaire de contact
- **DriverController** : Inscription livreur (frontend)
- **PartnerController** : Inscription restaurant partenaire
- **PaypalController** : Gestion paiements PayPal
- **DriverDeliveriesController** : Gestion livraisons livreur

---

#### Admin (`app/Http/Controllers/admin/`)
- **DashboardController** : Tableau de bord admin
- **RestaurantController** : CRUD restaurants, statuts, commissions
- **DriverController** : CRUD livreurs, statuts, paiements
- **OrderController** : Gestion commandes (tous statuts)
- **CuisineController** : CRUD cuisines
- **NewsController** : Actualités et notifications
- **ChargeController** : Gestion des frais
- **VehicleController** : CRUD véhicules
- **ExtrasController** : CRUD extras
- **UserController** : Gestion utilisateurs, profil admin
- **PayoutController** : Paiements restaurants/livreurs
- **ApiConfigurationController** : Configuration APIs externes

---

#### Restaurant (`app/Http/Controllers/restaurant/`)
- **DashboardController** : Tableau de bord restaurant
- **CategoryController** : CRUD catégories
- **ProductController** : CRUD produits
- **OrderController** : Gestion commandes restaurant
- **AddOnsController** : CRUD add-ons
- **OptionalController** : CRUD options
- **RequiredController** : CRUD requis
- **VoucherController** : CRUD bons de réduction
- **PaymentHistoryController** : Historique paiements
- **EmployeeController** : CRUD employés
- **WorkingHourController** : CRUD horaires
- **UserController** : Profil restaurant

---

#### Delivery (`app/Http/Controllers/delivery/`)
- **DashboardController** : Tableau de bord livreur
- **OrderController** : Gestion livraisons
- **PaymentHistoryController** : Historique gains

---

### 6.2 Contrôleurs API (`app/Http/Controllers/api/`)

- **UserController** : Authentification, profil, adresses, commandes
- **DriverController** : Authentification livreur, profil, commandes, gains
- **IndexController** : Données accueil, détails produits/restaurants, recherche
- **CartController** : Gestion panier (CRUD)
- **OrderController** : Commandes, historique
- **RestaurantController** : Liste restaurants, recherche, filtres, avis
- **VoucherController** : Validation bons de réduction
- **ReasonController** : Raisons d'annulation
- **ReviewController** : Avis livreurs
- **CheckoutController** : Finalisation commande
- **PaymentController** : Gestion paiements
- **PaymentCallbackController** : Callbacks paiements externes
- **OrderRatingController** : Notation commandes
- **OrderTrackingController** : Suivi commandes temps réel
- **DriverDeliveriesController** : Livraisons livreur (API)

---

## 7. COUCHE SERVICES

### 7.1 Services Principaux

#### PaymentService (`app/Services/PaymentService.php`)
**Rôle :** Gestion des paiements multi-providers

**Fonctionnalités :**
- Initiation paiements externes (MoMo, PayPal, Stripe)
- Gestion callbacks
- Confirmation paiements
- Calcul commissions

---

#### MobileMoneyService (`app/Services/MobileMoneyService.php`)
**Rôle :** Intégration Mobile Money (MTN MoMo, Airtel Money)

**Fonctionnalités :**
- Détection opérateur
- Initiation paiements MTN/Airtel
- Gestion tokens d'accès
- Callbacks et confirmations

---

#### DeliveryService (`app/Services/DeliveryService.php`)
**Rôle :** Gestion des livraisons

**Fonctionnalités :**
- Assignation livreurs
- Suivi en temps réel
- Calcul distances/temps
- Gestion statuts livraison

---

#### NotificationService (`app/Services/NotificationService.php`)
**Rôle :** Notifications multi-canaux

**Fonctionnalités :**
- Push notifications (FCM)
- SMS
- Email
- Notifications in-app

---

#### SmsService (`app/Services/SmsService.php`)
**Rôle :** Envoi SMS

**Providers supportés :**
- Twilio
- Africa's Talking
- BulkGate
- SMS Local (Congo)

---

#### GeolocationService (`app/Services/GeolocationService.php`)
**Rôle :** Géolocalisation et calculs géographiques

**Fonctionnalités :**
- Géocodage (adresses → coordonnées)
- Calcul distances
- Calcul temps de trajet
- Zones de livraison

---

#### RestaurantService (`app/Services/RestaurantService.php`)
**Rôle :** Logique métier restaurants

**Fonctionnalités :**
- Recherche et filtrage
- Calcul disponibilité
- Gestion statuts

---

#### CartService (`app/Services/CartService.php`)
**Rôle :** Gestion panier

**Fonctionnalités :**
- Ajout/suppression produits
- Calcul totaux
- Gestion extras/options

---

#### CheckoutService (`app/Services/CheckoutService.php`)
**Rôle :** Finalisation commande

**Fonctionnalités :**
- Validation panier
- Calcul frais
- Création commande
- Application bons de réduction

---

#### RatingService (`app/Services/RatingService.php`)
**Rôle :** Gestion notations

**Fonctionnalités :**
- Création notes
- Calcul moyennes
- Validation notes

---

#### LoyaltyService (`app/Services/LoyaltyService.php`)
**Rôle :** Programme de fidélité

**Fonctionnalités :**
- Attribution points
- Utilisation points
- Historique transactions

---

#### SocialAuthService (`app/Services/SocialAuthService.php`)
**Rôle :** Authentification sociale

**Providers :**
- Google
- Facebook
- Apple

---

#### ImageUploadService (`app/Services/ImageUploadService.php`)
**Rôle :** Upload et gestion images

**Fonctionnalités :**
- Upload local
- Upload Cloudinary
- Upload AWS S3
- Redimensionnement

---

#### ConfigService (`app/Services/ConfigService.php`)
**Rôle :** Configuration système

**Fonctionnalités :**
- Récupération configs
- Cache configs
- Nom entreprise, etc.

---

#### EnvConfigService (`app/Services/EnvConfigService.php`)
**Rôle :** Gestion variables d'environnement

**Fonctionnalités :**
- Lecture/écriture .env
- Validation configs

---

#### DataSyncService (`app/Services/DataSyncService.php`)
**Rôle :** Synchronisation données

**Fonctionnalités :**
- Sync externe
- Import/export

---

## 8. ROUTES ET API

### 8.1 Routes Web (`routes/web.php`)

#### Routes Publiques
```
GET  /                              → IndexController@home
GET  /resturant/view/{id}           → IndexController@resturantDetail
GET  /product/view/{id}             → IndexController@proDetail
GET  /cart                          → IndexController@cartDeatil
GET  /user/login                    → IndexController@Login
GET  /signup                        → IndexController@SignUp
POST /signup                        → IndexController@register
GET  /checkout                      → IndexController@Checkout
POST /checkout/order                → IndexController@getOrders
GET  /search/                       → IndexController@searchResult
GET  /search/ajax                   → IndexController@searchAjax
POST /cart                          → IndexController@addToCart
POST /voucher                       → IndexController@checkVoucher
GET  /cart/deleteItem/{id}          → IndexController@deleteItem
PUT  /cart/update/{cart}            → IndexController@updateItem
GET  /cart/count                    → IndexController@getCartCount
GET  /track-order/{orderNo?}        → IndexController@trackOrder
GET  /restaurants                   → IndexController@allRestaurants
GET  /restaurants/cuisine/{id}      → IndexController@restaurantByCuisine
```

#### Routes Authentification
```
GET  /login                         → LoginController@login_view
POST /login                         → LoginController@login
GET  /logout                        → LoginController@logout
GET  /user/forgot                   → IndexController@forgot
POST /user/forgot-password          → IndexController@forgotPassword
```

#### Routes Admin (`/admin/*`)
```
GET  /admin                         → DashboardController@index
GET  /admin/restaurant              → RestaurantController (resource)
GET  /admin/driver                  → DriverController (resource)
GET  /admin/all_orders              → OrderController@all_orders
GET  /admin/complete_orders         → OrderController@complete_orders
GET  /admin/pending_orders          → OrderController@pending_orders
GET  /admin/api-configuration       → ApiConfigurationController@index
POST /admin/api/keys/save           → ApiConfigurationController@saveApiKeys
```

#### Routes Restaurant (`/restaurant/*`)
```
GET  /restaurant                    → DashboardController@index
GET  /restaurant/category           → CategoryController (resource)
GET  /restaurant/product            → ProductController (resource)
GET  /restaurant/all_orders         → OrderController@all_orders
GET  /restaurant/complete_orders    → OrderController@complete_orders
```

#### Routes Delivery (`/restaurant/delivery/*`)
```
GET  /restaurant/delivery           → DashboardController@index
GET  /restaurant/delivery/all_orders → OrderController@all_orders
```

---

### 8.2 Routes API (`routes/api.php`)

#### Authentification
```
POST /api/register                  → UserController@register
POST /api/login                     → UserController@login
GET  /api/user_profile/{user}       → UserController@profile
POST /api/update_profile/           → UserController@updateProfile
POST /api/forgot_password           → UserController@forgotPassword
```

#### Driver APIs
```
POST /api/driver_register           → DriverController@register
POST /api/driver_login              → DriverController@login
GET  /api/driver_profile/{driver}   → DriverController@profile
POST /api/driver_update_profile/    → DriverController@updateProfile
POST /api/set_driver_online/{driver} → DriverController@SetDriverOnline
GET  /api/order_request/{driver}    → DriverController@orderRequests
POST /api/order_accept_by_driver    → DriverController@acceptOrderRequests
```

#### Home & Search
```
POST /api/home_data                 → IndexController@index
GET  /api/product_detail/{product}  → IndexController@proDetail
POST /api/search_filters            → IndexController@searchFilter
POST /api/search_by_keyword         → IndexController@searchQurey
GET  /api/restaurant_detail/{restaurant} → IndexController@restaurantDetail
```

#### Cart APIs
```
POST /api/add_to_cart               → CartController@addToCart
GET  /api/show_cart_details/{user}  → CartController@showCartDetail
POST /api/update_cart_details       → CartController@UpdateCartDetail
DELETE /api/delete_cart_product/{cart} → CartController@deleteCartProduct
DELETE /api/delete_previous_cart/{user} → CartController@deletePreviousCart
```

#### Order APIs
```
POST /api/place_orders/             → OrderController@getOrders
GET  /api/user_pending_orders/{user} → OrderController@UserOrderHistory
GET  /api/user_completed_order_history/{user} → OrderController@UserCompletedOrderHistory
POST /api/complete_orders            → OrderController@completeOrders
```

#### Restaurant APIs
```
POST /api/search_restaurant         → RestaurantController@search
GET  /api/restaurants_with_category/{cuisine} → RestaurantController@restaurantsByCuisine
GET  /api/get_filters               → RestaurantController@sendFilters
GET  /api/restaurants/popular       → RestaurantController@popular
GET  /api/restaurants               → RestaurantController@index
GET  /api/restaurants/{id}/reviews → RestaurantController@getReviews
```

#### Checkout & Payment APIs (Sanctum)
```
POST /api/checkout                   → CheckoutController@__invoke
GET  /api/payments/{payment}         → PaymentController@show
POST /api/payments/{payment}/confirm → PaymentController@confirm
POST /api/payments/callback/{provider} → PaymentCallbackController@handle
```

#### Tracking & Delivery
```
GET  /api/order/{orderNo}/status     → IndexController@getOrderStatus
POST /api/driver/{driverId}/location → DriverController@updateLocation
GET  /api/orders/{order}/tracking    → OrderTrackingController@show
GET  /api/driver/deliveries          → DriverDeliveriesController@index
PATCH /api/driver/deliveries/{delivery}/status → DriverDeliveriesController@updateStatus
```

#### Rating APIs
```
POST /api/orders/{order}/rating      → OrderRatingController@store
GET  /api/orders/{order}/rating      → OrderRatingController@show
GET  /api/orders/{order}/rating/check → OrderRatingController@check
```

---

## 9. FRONTEND

### 9.1 Structure Frontend

#### Templates Blade (`resources/views/`)

**Layouts :**
- `layouts/app.blade.php` : Layout principal (Bootstrap)
- `layouts/app-modern.blade.php` : Layout moderne (Bootstrap 5)

**Frontend (`frontend/`) :**
- `index.blade.php` : Page d'accueil
- `restaurant.blade.php` : Liste restaurants
- `restaurant_detail.blade.php` : Détail restaurant
- `product_detail.blade.php` : Détail produit
- `cart.blade.php` : Panier
- `checkout.blade.php` : Finalisation commande
- `profile.blade.php` : Profil utilisateur
- `login.blade.php`, `signup.blade.php` : Authentification
- `forgot.blade.php` : Mot de passe oublié
- `search.blade.php` : Résultats recherche
- `track_order.blade.php` : Suivi commande
- `contact.blade.php` : Contact
- `about.blade.php` : À propos
- `terms.blade.php` : Conditions
- `driver.blade.php` : Inscription livreur

**Admin (`admin/`) :**
- Dashboards
- Formulaires CRUD
- Listes et tableaux
- Profil admin

**Restaurant (`restaurant/`) :**
- Dashboard restaurant
- Gestion produits
- Gestion commandes
- Statistiques

**Driver (`driver/`) :**
- Dashboard livreur
- Livraisons
- Gains

---

### 9.2 Assets Frontend

#### JavaScript (`resources/js/`)
- `app.js` : Point d'entrée principal
- `bootstrap.js` : Configuration Bootstrap

#### Styles (`resources/sass/`)
- `app.scss` : Styles principaux (compilé via Laravel Mix)

#### Assets Publics (`public/`)
- `css/` : CSS compilés
- `js/` : JavaScript compilés
- `frontend/` : Assets frontend (CSS, JS, images)
- `plugins/` : Plugins tiers (jQuery, Bootstrap, etc.)
- `images/` : Images statiques

---

### 9.3 Technologies Frontend

- **Blade Templates** : Moteur de template Laravel
- **Bootstrap 5** : Framework CSS
- **jQuery** : Bibliothèque JavaScript (via plugins)
- **Axios** : Client HTTP pour appels API
- **Font Awesome** : Icônes
- **Google Maps API** : Cartes et géolocalisation

---

## 10. BASE DE DONNÉES

### 10.1 Tables Principales

| Table | Description | Relations |
|-------|-------------|-----------|
| `users` | Utilisateurs (tous types) | restaurant, orders, loyalty_points |
| `restaurants` | Restaurants | user, cuisines, products, orders |
| `drivers` | Livreurs | restaurant, vehicle, orders |
| `products` | Produits | restaurant, category, orders |
| `categories` | Catégories produits | restaurant, products |
| `orders` | Commandes | restaurant, user, product, driver, payment, delivery |
| `carts` | Paniers | user, restaurant, product |
| `cuisines` | Types de cuisines | restaurants (many-to-many) |
| `cuisine_restaurant` | Pivot cuisines-restaurants | - |
| `vehicles` | Véhicules | drivers |
| `extras` | Extras produits | products |
| `cart_extras` | Extras dans panier | cart, extra |
| `employees` | Employés restaurants | restaurant |
| `working_hours` | Horaires d'ouverture | restaurant |
| `vouchers` | Bons de réduction | restaurant |
| `driver_payments` | Paiements livreurs | driver |
| `restaurant_payments` | Paiements restaurants | restaurant |
| `cancellation_reasons` | Raisons annulation | user, order |
| `types` | Types divers | - |
| `ratings` | Notes/avis | restaurant, order, user |
| `reviews` | Commentaires | restaurant, user |
| `charges` | Frais système | - |
| `loyalty_points` | Points de fidélité | user |
| `loyalty_transactions` | Transactions fidélité | user |
| `addresses` | Adresses utilisateurs | user |
| `contacts` | Messages contact | - |
| `news` | Actualités système | - |
| `deliveries` | Livraisons | order, driver |
| `payments` | Paiements | order, user |
| `system_config` | Configuration système | - |

---

### 10.2 Migrations

**Migrations principales :**
- `2014_10_12_000000_create_users_table.php`
- `2020_02_21_120236_create_restaurants_table.php`
- `2020_02_24_074903_create_orders_table.php`
- `2020_03_13_044522_create_carts_table.php`
- `2024_12_04_000001_create_ratings_table.php`
- `2024_12_04_000002_create_charges_table.php`
- `2024_12_05_100000_create_loyalty_points_table.php`
- `2025_12_05_100000_create_deliveries_table.php`
- `2025_12_05_120000_create_payments_table.php`
- Et autres...

---

## 11. AUTHENTIFICATION ET SÉCURITÉ

### 11.1 Système d'Authentification

#### Web (Sessions)
- **Laravel Auth** : Authentification par sessions
- **Middleware :** `auth`, `admin`, `restaurant`, `delivery`, `user`

#### API
- **Laravel Passport** : OAuth2 pour API
- **Laravel Sanctum** : Tokens pour SPA/mobile
- **Middleware :** `auth:api`, `auth:sanctum`

---

### 11.2 Middleware Personnalisés

| Middleware | Rôle | Vérifie |
|------------|------|---------|
| `AdminMiddleware` | Admin uniquement | `type === 'admin'` |
| `RestaurantMiddleware` | Restaurant uniquement | `type === 'restaurant'` |
| `DeliveryMiddleware` | Livreur uniquement | `type === 'delivery'` |
| `UserMiddleware` | Client uniquement | `type === 'user'` |

---

### 11.3 Sécurité

- **CSRF Protection** : Activé pour routes web
- **Password Hashing** : Bcrypt
- **API Rate Limiting** : `throttle:60,1` sur routes API
- **Input Validation** : Via Form Requests et validation Laravel
- **SQL Injection Protection** : Eloquent ORM
- **XSS Protection** : Échappement automatique Blade

---

## 12. INTÉGRATIONS EXTERNES

### 12.1 Services de Paiement

#### Mobile Money
- **MTN MoMo** : Intégration via `MobileMoneyService`
- **Airtel Money** : Intégration via `MobileMoneyService`
- Configuration : `config/external-services.php`

#### Paiements Internationaux
- **PayPal** : Intégration via `PaypalController`
- **Stripe** : Mentionné dans routes (à vérifier implémentation)

---

### 12.2 Services de Notification

#### Push Notifications
- **Firebase Cloud Messaging (FCM)** : Via `NotificationService`

#### SMS
- **Twilio** : SMS et OTP
- **Africa's Talking** : SMS
- **BulkGate** : SMS
- **SMS Local (Congo)** : SMS local

#### Email
- **SendGrid** : Transactionnel
- **Mailgun** : Transactionnel
- **SMTP Standard** : Laravel Mail

---

### 12.3 Services de Géolocalisation

- **Google Maps API** : Cartes, géocodage, directions, distance matrix
- **OpenStreetMap/Nominatim** : Alternative gratuite (optionnel)

---

### 12.4 Authentification Sociale

- **Google OAuth** : Via `SocialAuthService`
- **Facebook OAuth** : Via `SocialAuthService`
- **Apple Sign In** : Via `SocialAuthService`

---

### 12.5 Stockage

- **Local Storage** : Par défaut
- **Cloudinary** : Images (optionnel)
- **AWS S3** : Fichiers (optionnel)

---

### 12.6 Analytics

- **Google Analytics** : Tracking (optionnel)
- **Mixpanel** : Analytics (optionnel)

---

## 13. WORKFLOW UTILISATEUR

### 13.1 Workflow Client

```
1. Inscription/Connexion
   ↓
2. Recherche Restaurant
   ↓
3. Consultation Produits
   ↓
4. Ajout au Panier
   ↓
5. Finalisation Commande (Checkout)
   ↓
6. Sélection Mode de Paiement
   ↓
7. Paiement (MoMo, PayPal, Stripe)
   ↓
8. Confirmation Commande
   ↓
9. Suivi Commande (Temps réel)
   ↓
10. Livraison
    ↓
11. Notation/Révision
```

---

### 13.2 Workflow Restaurant

```
1. Inscription/Connexion
   ↓
2. Configuration Restaurant
   ↓
3. Gestion Produits/Catégories
   ↓
4. Réception Commandes
   ↓
5. Préparation Commande
   ↓
6. Assignation Livreur
   ↓
7. Suivi Livraison
   ↓
8. Réception Paiement
```

---

### 13.3 Workflow Livreur

```
1. Inscription/Connexion
   ↓
2. Activation Statut En Ligne
   ↓
3. Réception Demandes Livraison
   ↓
4. Acceptation/Refus Commande
   ↓
5. Récupération Commande
   ↓
6. Mise à Jour Position (Temps réel)
   ↓
7. Livraison Commande
   ↓
8. Confirmation Livraison
   ↓
9. Réception Paiement
```

---

### 13.4 Workflow Admin

```
1. Connexion Admin
   ↓
2. Gestion Restaurants
   ↓
3. Gestion Livreurs
   ↓
4. Gestion Commandes
   ↓
5. Configuration Système
   ↓
6. Gestion Paiements
   ↓
7. Statistiques & Rapports
```

---

## 14. POINTS D'ATTENTION

### 14.1 Architecture

✅ **Points Forts :**
- Architecture MVC claire
- Séparation des responsabilités (Services)
- Middleware bien structurés
- Support multi-rôles

⚠️ **Points d'Amélioration :**
- Certains contrôleurs peuvent être trop volumineux
- Logique métier parfois dans les contrôleurs (à déplacer vers Services)
- Pas de Repository Pattern (optionnel mais recommandé)

---

### 14.2 Sécurité

✅ **Points Forts :**
- CSRF protection
- Password hashing
- Rate limiting API
- Middleware d'autorisation

⚠️ **Points d'Amélioration :**
- Validation des entrées à renforcer (Form Requests)
- Audit des permissions
- Sécurisation des callbacks paiements (IP whitelist)

---

### 14.3 Performance

⚠️ **Points d'Amélioration :**
- Cache des requêtes fréquentes
- Optimisation des requêtes N+1
- Indexation base de données
- Lazy loading des relations

---

### 14.4 Code Quality

⚠️ **Points d'Amélioration :**
- Tests unitaires à compléter
- Documentation des méthodes
- Standardisation des réponses API
- Gestion d'erreurs uniforme

---

### 14.5 Configuration

⚠️ **Points d'Amélioration :**
- Variables d'environnement à documenter
- Configuration centralisée (déjà fait via `external-services.php`)
- Validation des configs au démarrage

---

## 15. COMMANDES UTILES

### 15.1 Développement

```bash
# Installer dépendances PHP
composer install

# Installer dépendances JavaScript
pnpm install

# Compiler assets
pnpm run dev
# ou
pnpm run production

# Lancer serveur de développement
php artisan serve

# Générer clé application
php artisan key:generate

# Installer Passport
php artisan passport:install
```

---

### 15.2 Base de Données

```bash
# Lancer migrations
php artisan migrate

# Lancer migrations avec seeders
php artisan migrate --seed

# Rollback migrations
php artisan migrate:rollback

# Créer nouvelle migration
php artisan make:migration create_example_table

# Créer nouveau modèle avec migration
php artisan make:model Example -m
```

---

### 15.3 Cache

```bash
# Vider cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optimiser pour production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

### 15.4 Tests

```bash
# Lancer tests
php artisan test
# ou
vendor/bin/phpunit
```

---

## 16. CONCLUSION

Cette codebase représente une application complète de livraison de nourriture avec :

✅ **Architecture solide** : MVC avec couche Service  
✅ **Multi-rôles** : Admin, Restaurant, Livreur, Client  
✅ **API complète** : REST API avec Passport/Sanctum  
✅ **Intégrations** : Paiements, SMS, Notifications, Géolocalisation  
✅ **Frontend** : Templates Blade avec Bootstrap  
✅ **Base de données** : Structure complète avec migrations  

**Prochaines étapes recommandées :**
1. Compléter les tests unitaires
2. Optimiser les performances (cache, requêtes)
3. Documenter les APIs (Swagger/OpenAPI)
4. Améliorer la gestion d'erreurs
5. Ajouter des logs structurés

---

**Document généré le :** 2025-01-27  
**Version du document :** 1.0

