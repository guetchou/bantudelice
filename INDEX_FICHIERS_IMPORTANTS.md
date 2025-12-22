# INDEX DES FICHIERS IMPORTANTS
## Navigation Rapide dans la Codebase

**Date :** 2025-01-27

---

## 📋 DOCUMENTS D'ANALYSE

### Architecture
- `ARCHITECTURE_CODEBASE.md` - Analyse complète de l'architecture
- `DIAGRAMME_ARCHITECTURE.md` - Diagrammes visuels
- `RESUME_ARCHITECTURE.md` - Résumé exécutif
- `INDEX_FICHIERS_IMPORTANTS.md` - Ce document

### Audits Existants
- `AUDIT_ETAT_DES_LIEUX.md` - Audit initial complet
- `ANALYSE_COMPOSANTS.md` - Analyse détaillée des composants
- `RESUME_AUDIT.md` - Résumé audit
- `VARIABLES_ENVIRONNEMENT.md` - Référence variables .env

---

## 🔧 CONFIGURATION

### Fichiers de Configuration Principaux
```
config/
├── app.php                    # Configuration application
├── database.php               # Configuration base de données
├── auth.php                   # Configuration authentification
├── external-services.php     # Configuration services externes ⭐
├── paypal.php                 # Configuration PayPal
├── services.php               # Configuration services Laravel
├── mail.php                   # Configuration email
├── queue.php                  # Configuration queues
└── cache.php                  # Configuration cache
```

### Fichiers de Build
```
├── composer.json              # Dépendances PHP
├── package.json                # Dépendances JavaScript
├── webpack.mix.js             # Configuration Laravel Mix
├── phpunit.xml                # Configuration tests
└── .env                       # Variables d'environnement (à créer)
```

---

## 🎯 ROUTES

### Routes Web
```
routes/
├── web.php                    # Routes web (~90 routes) ⭐
├── api.php                    # Routes API (~35 endpoints) ⭐
├── channels.php               # Routes broadcasting
└── console.php                # Routes console
```

**Points d'entrée principaux :**
- `routes/web.php` - Ligne 36 : Route home
- `routes/web.php` - Ligne 89 : Routes admin
- `routes/web.php` - Ligne 155 : Routes restaurant
- `routes/api.php` - Ligne 20 : Routes API

---

## 🎮 CONTRÔLEURS

### Contrôleurs Web Frontend
```
app/Http/Controllers/
├── IndexController.php        # Page d'accueil, recherche, panier ⭐
├── HomeController.php         # Tableau de bord
├── LoginController.php        # Authentification web
├── ContactController.php      # Formulaire contact
├── DriverController.php       # Inscription livreur (frontend)
├── PartnerController.php      # Inscription restaurant
├── PaypalController.php       # Paiements PayPal
└── DriverDeliveriesController.php # Livraisons livreur
```

### Contrôleurs Admin
```
app/Http/Controllers/admin/
├── DashboardController.php    # Dashboard admin ⭐
├── RestaurantController.php   # CRUD restaurants ⭐
├── DriverController.php       # CRUD livreurs
├── OrderController.php        # Gestion commandes ⭐
├── CuisineController.php      # CRUD cuisines
├── NewsController.php         # Actualités
├── ChargeController.php       # Frais système
├── VehicleController.php      # Véhicules
├── ExtrasController.php       # Extras produits
├── UserController.php         # Utilisateurs
├── PayoutController.php       # Paiements restaurants/livreurs
└── ApiConfigurationController.php # Configuration APIs ⭐
```

### Contrôleurs Restaurant
```
app/Http/Controllers/restaurant/
├── DashboardController.php    # Dashboard restaurant ⭐
├── CategoryController.php     # CRUD catégories
├── ProductController.php      # CRUD produits ⭐
├── OrderController.php        # Gestion commandes ⭐
├── AddOnsController.php       # Add-ons
├── OptionalController.php     # Options
├── RequiredController.php     # Requis
├── VoucherController.php      # Bons de réduction
├── PaymentHistoryController.php # Historique paiements
├── EmployeeController.php     # Employés
├── WorkingHourController.php  # Horaires
└── UserController.php         # Profil restaurant
```

### Contrôleurs API
```
app/Http/Controllers/api/
├── UserController.php         # Authentification, profil ⭐
├── DriverController.php       # APIs livreur ⭐
├── IndexController.php        # Données accueil, recherche ⭐
├── CartController.php         # Gestion panier ⭐
├── OrderController.php        # Commandes ⭐
├── RestaurantController.php   # Liste restaurants, recherche ⭐
├── VoucherController.php      # Validation bons
├── ReasonController.php        # Raisons annulation
├── ReviewController.php       # Avis
├── CheckoutController.php     # Finalisation commande ⭐
├── PaymentController.php      # Gestion paiements ⭐
├── PaymentCallbackController.php # Callbacks paiements ⭐
├── OrderRatingController.php  # Notation commandes
├── OrderTrackingController.php # Suivi commandes ⭐
└── DriverDeliveriesController.php # Livraisons livreur (API)
```

---

## 🗄️ MODÈLES

### Modèles Principaux
```
app/
├── User.php                   # Utilisateurs (tous types) ⭐
├── Restaurant.php             # Restaurants ⭐
├── Order.php                  # Commandes ⭐
├── Product.php                # Produits ⭐
├── Cart.php                   # Paniers ⭐
├── Driver.php                 # Livreurs ⭐
├── Category.php               # Catégories
├── Cuisine.php                # Types de cuisines
├── Voucher.php                # Bons de réduction
├── Rating.php                 # Notes/avis
├── Review.php                 # Commentaires
├── Payment.php                # Paiements ⭐
├── Delivery.php               # Livraisons ⭐
├── Employee.php               # Employés
├── WorkingHour.php            # Horaires
├── Vehicle.php                # Véhicules
├── Extra.php                  # Extras produits
├── Optional.php               # Options
├── Required.php               # Requis
├── Charge.php                 # Frais système
├── LoyaltyPoint.php          # Points fidélité
├── LoyaltyTransaction.php    # Transactions fidélité
├── Address.php                # Adresses
├── Contact.php                # Messages contact
├── News.php                   # Actualités
└── ... (autres modèles)
```

---

## 🔧 SERVICES

### Services Métier
```
app/Services/
├── PaymentService.php         # Gestion paiements multi-providers ⭐
├── MobileMoneyService.php     # Mobile Money (MTN/Airtel) ⭐
├── DeliveryService.php        # Gestion livraisons ⭐
├── NotificationService.php    # Notifications multi-canaux ⭐
├── SmsService.php             # Envoi SMS ⭐
├── GeolocationService.php     # Géolocalisation ⭐
├── RestaurantService.php      # Logique métier restaurants
├── CartService.php            # Gestion panier
├── CheckoutService.php        # Finalisation commande ⭐
├── RatingService.php          # Gestion notations
├── LoyaltyService.php         # Programme fidélité
├── SocialAuthService.php      # Authentification sociale
├── ImageUploadService.php     # Upload images
├── ConfigService.php          # Configuration système
├── EnvConfigService.php       # Variables d'environnement
└── DataSyncService.php        # Synchronisation données
```

---

## 🛡️ MIDDLEWARE

### Middleware Personnalisés
```
app/Http/Middleware/
├── AdminMiddleware.php        # Vérification admin ⭐
├── RestaurantMiddleware.php   # Vérification restaurant ⭐
├── DeliveryMiddleware.php    # Vérification livreur ⭐
├── UserMiddleware.php         # Vérification client
└── ... (autres middleware Laravel)
```

### Configuration Middleware
```
app/Http/Kernel.php           # Configuration middleware ⭐
```

---

## 🎨 FRONTEND

### Templates Blade Principaux
```
resources/views/
├── layouts/
│   ├── app.blade.php          # Layout principal (Bootstrap)
│   └── app-modern.blade.php   # Layout moderne (Bootstrap 5) ⭐
│
├── frontend/
│   ├── index.blade.php        # Page d'accueil ⭐
│   ├── restaurant_detail.blade.php # Détail restaurant ⭐
│   ├── product_detail.blade.php # Détail produit ⭐
│   ├── cart.blade.php         # Panier ⭐
│   ├── checkout.blade.php     # Finalisation commande ⭐
│   ├── profile.blade.php      # Profil utilisateur
│   ├── login.blade.php        # Connexion
│   ├── signup.blade.php       # Inscription
│   ├── search.blade.php       # Recherche
│   └── track_order.blade.php  # Suivi commande ⭐
│
├── admin/                     # Vues admin
├── restaurant/                # Vues restaurant
├── driver/                    # Vues livreur
└── auth/                      # Vues authentification
```

### Assets JavaScript
```
resources/js/
├── app.js                     # Point d'entrée principal ⭐
└── bootstrap.js              # Configuration Bootstrap
```

### Assets Styles
```
resources/sass/
└── app.scss                  # Styles principaux
```

---

## 🗃️ BASE DE DONNÉES

### Migrations Principales
```
database/migrations/
├── 2014_10_12_000000_create_users_table.php ⭐
├── 2020_02_21_120236_create_restaurants_table.php ⭐
├── 2020_02_24_074903_create_orders_table.php ⭐
├── 2020_03_13_044522_create_carts_table.php ⭐
├── 2024_12_04_000001_create_ratings_table.php
├── 2024_12_04_000002_create_charges_table.php
├── 2024_12_05_100000_create_loyalty_points_table.php
├── 2025_12_05_100000_create_deliveries_table.php ⭐
├── 2025_12_05_120000_create_payments_table.php ⭐
└── ... (autres migrations)
```

---

## 🔐 AUTHENTIFICATION

### Configuration Auth
```
config/auth.php                # Configuration authentification ⭐
```

### Passport (API OAuth2)
```
# Installation : php artisan passport:install
# Routes : routes/api.php
```

### Sanctum (SPA/Token)
```
# Configuration : config/sanctum.php
# Utilisation : middleware auth:sanctum
```

---

## 📦 INTÉGRATIONS EXTERNES

### Configuration Services Externes
```
config/external-services.php   # Configuration complète ⭐
```

**Services configurés :**
- Paiements : MTN MoMo, Airtel Money, PayPal, Stripe
- Notifications : FCM, Twilio, BulkGate, SendGrid, Mailgun
- Géolocalisation : Google Maps, OpenStreetMap
- Authentification sociale : Google, Facebook, Apple
- Stockage : Cloudinary, AWS S3

---

## 🧪 TESTS

### Configuration Tests
```
phpunit.xml                    # Configuration PHPUnit ⭐
```

### Tests
```
tests/
├── Feature/                   # Tests d'intégration
└── Unit/                      # Tests unitaires
```

---

## 🚀 COMMANDES ARTISAN

### Commandes Utiles
```bash
# Générer clé application
php artisan key:generate

# Installer Passport
php artisan passport:install

# Lancer migrations
php artisan migrate
php artisan migrate --seed

# Vider cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optimiser production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Lancer tests
php artisan test
```

---

## 📝 HELPERS

### Helpers Globaux
```
app/helpers.php                # Fonctions helpers ⭐
```

**Fonctions disponibles :**
- `google_maps_api_key()` - Clé API Google Maps
- `google_maps_js_url()` - URL Google Maps JS

---

## 🎯 POINTS D'ENTRÉE PRINCIPAUX

### Pour Comprendre l'Architecture
1. `ARCHITECTURE_CODEBASE.md` - Analyse complète
2. `DIAGRAMME_ARCHITECTURE.md` - Diagrammes
3. `routes/web.php` - Routes web
4. `routes/api.php` - Routes API
5. `app/Http/Kernel.php` - Middleware

### Pour Développer une Feature
1. `routes/web.php` ou `routes/api.php` - Ajouter route
2. `app/Http/Controllers/` - Créer/modifier contrôleur
3. `app/Services/` - Logique métier
4. `app/Models/` - Modèles Eloquent
5. `database/migrations/` - Migrations si nécessaire
6. `resources/views/` - Templates si web

### Pour Intégrer un Service Externe
1. `config/external-services.php` - Ajouter configuration
2. `app/Services/` - Créer service
3. `app/Http/Controllers/` - Utiliser dans contrôleur
4. `.env` - Variables d'environnement

### Pour Déboguer
1. `storage/logs/` - Logs application
2. `app/Http/Controllers/` - Contrôleurs
3. `app/Services/` - Services
4. `routes/web.php` / `routes/api.php` - Routes

---

## 📚 DOCUMENTATION EXTERNE

### Laravel
- Documentation : https://laravel.com/docs
- Passport : https://laravel.com/docs/passport
- Sanctum : https://laravel.com/docs/sanctum

### Services Externes
- MTN MoMo : https://momodeveloper.mtn.com
- Airtel Money : https://openapi.airtel.africa
- Firebase : https://firebase.google.com/docs
- Google Maps : https://developers.google.com/maps

---

## 🔍 RECHERCHE RAPIDE

### Trouver une Route
```bash
# Chercher dans routes
grep -r "route_name" routes/
```

### Trouver un Contrôleur
```bash
# Chercher dans contrôleurs
grep -r "method_name" app/Http/Controllers/
```

### Trouver un Service
```bash
# Chercher dans services
grep -r "service_method" app/Services/
```

### Trouver un Modèle
```bash
# Chercher dans modèles
grep -r "model_name" app/
```

---

**Document généré le :** 2025-01-27  
**Version :** 1.0

