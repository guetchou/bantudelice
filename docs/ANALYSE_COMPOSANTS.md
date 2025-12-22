# ANALYSE DÉTAILLÉE DES COMPOSANTS

Document d'analyse approfondie de tous les composants de l'application TheDrop247.

---

## 1. MODÈLES (MODELS)

### 1.1 Modèles Principaux

#### User (`app/User.php`)
**Description** : Modèle d'authentification pour tous les types d'utilisateurs  
**Authentification** : Laravel Passport (HasApiTokens)

**Champs fillable :**
- name, email, password, phone, type, image

**Relations :**
- `restaurant()` : hasOne - Relation avec un restaurant (si type='restaurant')
- `order()` : hasMany - Commandes de l'utilisateur
- `cancellation_reasons()` : hasMany - Raisons d'annulation

**Type utilisateur :**
- `admin` : Administrateur
- `restaurant` : Restaurant
- `delivery` : Livreur
- `user` : Client

---

#### Restaurant (`app/Restaurant.php`)
**Description** : Modèle représentant un restaurant

**Champs fillable :**
- user_id, name, email, password, city, address, phone
- description, user_name, slogan, logo, cover_image
- latitude, longitude, min_order, avg_delivery_time
- services, account_name, account_number, bank_name, branch_name

**Relations :**
- `cuisines()` : belongsToMany - Types de cuisines
- `drivers()` : hasMany - Livreurs associés
- `users()` : hasMany - Utilisateurs (employés)
- `orders()` : hasMany - Commandes
- `payments()` : hasMany - Paiements reçus
- `categories()` : hasMany - Catégories de produits
- `products()` : hasMany - Produits
- `employees()` : hasMany - Employés
- `working_hours()` : hasMany - Horaires d'ouverture
- `ratings()` : hasMany - Avis/notes
- `cart()` : hasMany - Paniers
- `vouchers()` : hasMany - Bons de réduction

**Méthodes :**
- `hasCuisine($cuisineId)` : Vérifie si le restaurant a une cuisine

---

#### Order (`app/Order.php`)
**Description** : Modèle représentant une commande

**Champs fillable :**
- restaurant_id, user_id, product_id, qty, price
- latitude, longitude, offer_discount, tax
- delivery_charges, sub_total, total
- admin_commission, restaurant_commission, driver_tip
- delivery_address, scheduled_date, ordered_time, delivered_time

**Relations :**
- `restaurant()` : belongsTo
- `product()` : belongsTo
- `driver()` : belongsTo
- `user()` : belongsTo

**Statuts possibles :**
- pending : En attente
- preparing : En préparation
- assigned : Assignée à un livreur
- delivering : En cours de livraison
- completed : Terminée
- cancelled : Annulée

---

#### Product (`app/Product.php`)
**Description** : Modèle représentant un produit/plat

**Champs fillable :**
- restaurant_id, category_id, name, image
- price, discount_price, description, size

**Relations :**
- `extras()` : hasMany - Options/extras
- `orders()` : hasMany - Commandes contenant ce produit
- `restaurants()` : belongsTo - Restaurant propriétaire
- `categories()` : belongsTo - Catégorie

---

#### Driver (`app/Driver.php`)
**Description** : Modèle représentant un livreur

**Authentification** : Laravel Passport (HasApiTokens)

**Champs fillable :**
- restaurant_id, name, hourly_pay, email, cnic
- password, phone, image, address
- account_name, account_number, bank_name, branch_name, branch_address
- licence_image, hours, vehicle, days

**Relations :**
- `restaurant()` : belongsTo
- `vehicle()` : hasOne - Véhicule assigné
- `orders()` : hasMany - Commandes assignées
- `payouts()` : hasMany - Paiements reçus

---

#### Cart (`app/Cart.php`)
**Description** : Modèle représentant un panier d'achat

**Relations :**
- Appartient à un utilisateur
- Contient des produits
- Peut contenir des extras

---

#### Category (`app/Category.php`)
**Description** : Catégories de produits d'un restaurant

**Relations :**
- `restaurant()` : belongsTo
- `products()` : hasMany

---

#### Cuisine (`app/Cuisine.php`)
**Description** : Types de cuisines (Italienne, Chinoise, etc.)

**Relations :**
- `restaurants()` : belongsToMany - Restaurants proposant cette cuisine

---

#### Voucher (`app/Voucher.php`)
**Description** : Codes promo et bons de réduction

**Relations :**
- `restaurant()` : belongsTo

---

#### Rating/Review (`app/Rating.php`, `app/Review.php`)
**Description** : Système d'avis et de notation

**Relations :**
- `restaurant()` : belongsTo
- `user()` : belongsTo
- `driver()` : belongsTo (pour avis livreurs)

---

### 1.2 Modèles Secondaires

- **Address** : Adresses des utilisateurs
- **Charge** : Frais de service
- **Extra** : Options supplémentaires pour produits
- **Optional** : Options optionnelles
- **Required** : Options obligatoires
- **Employee** : Employés de restaurant
- **WorkingHour** : Horaires d'ouverture
- **Vehicle** : Véhicules des livreurs
- **News** : Actualités/Notifications
- **Contact** : Messages de contact
- **CancellationReason** : Raisons d'annulation
- **CompletedOrder** : Historique commandes complétées
- **DriverHistory** : Historique livreur
- **DriverPayment** : Paiements livreurs
- **RestaurantPayment** : Paiements restaurants
- **Filter** / **SearchFilter** : Filtres de recherche
- **Type** : Types divers
- **UserToken** / **DriverToken** : Tokens d'authentification

---

## 2. CONTRÔLEURS (CONTROLLERS)

### 2.1 Contrôleurs Frontend

#### IndexController
**Routes principales :**
- `home()` : Page d'accueil
- `resturantDetail($id)` : Détails restaurant
- `proDetail($id)` : Détails produit
- `cartDeatil()` : Affichage panier
- `Login()` : Connexion utilisateur
- `forgot()` : Mot de passe oublié
- `SignUp()` : Inscription
- `addToCart()` : Ajout au panier
- `checkout()` : Page checkout
- `getOrders()` : Validation commande
- `searchResult()` : Résultats de recherche

**Responsabilités :**
- Affichage des pages publiques
- Gestion du panier
- Processus de commande
- Recherche et filtrage

---

#### HomeController
**Routes :**
- `index()` : Page d'accueil
- `dashboard()` : Tableau de bord admin

---

#### LoginController
**Routes :**
- `login_view()` : Formulaire de connexion
- `login()` : Traitement connexion
- `logout()` : Déconnexion

---

#### ContactController
**Routes :**
- `ContactUs()` : Traitement formulaire contact

---

#### DriverController (Frontend)
**Routes :**
- `driver()` : Formulaire inscription livreur
- `driverRegistration()` : Traitement inscription

---

#### PartnerController
**Routes :**
- `partner()` : Formulaire inscription restaurant
- `partnerRegistration()` : Traitement inscription

---

#### PaypalController
**Routes :**
- `payWithpaypal()` : Initiation paiement PayPal
- `getPaymentStatus()` : Vérification statut paiement

---

### 2.2 Contrôleurs Admin (`app/Http/Controllers/admin/`)

#### DashboardController
**Routes :**
- Tableau de bord administrateur
- Statistiques globales

#### RestaurantController
**Routes :**
- CRUD restaurants
- `pending()` : Restaurants en attente d'approbation
- `get_service_charges()` : Récupérer frais de service
- `set_service_charges()` : Définir frais de service
- `change_restaurant_active_status()` : Activer/désactiver restaurant
- `change_restaurant_featured_status()` : Mettre en avant

#### DriverController
**Routes :**
- CRUD livreurs
- `change_driver_active_status()` : Activer/désactiver
- `get_hourly_pay()` : Récupérer salaire horaire
- `set_hourly_pay()` : Définir salaire horaire

#### OrderController
**Routes :**
- `all_orders()` : Toutes les commandes
- `complete_orders()` : Commandes terminées
- `prepaire_orders()` : Commandes en préparation
- `cancel_orders()` : Commandes annulées
- `pending_orders()` : Commandes en attente
- `schedule_orders()` : Commandes programmées
- `cancel_order()` : Annuler une commande
- `assign_order()` : Assigner une commande
- `show_order()` : Détails commande
- `assign_driver()` : Assigner un livreur
- `prepaire_order()` : Marquer en préparation
- `deliver_order()` : Marquer comme livrée

#### CuisineController
**Routes :**
- CRUD cuisines

#### NewsController
**Routes :**
- CRUD actualités
- `sentNotification()` : Envoyer notification push

#### ChargeController
**Routes :**
- CRUD frais de service

#### VehicleController
**Routes :**
- CRUD véhicules

#### UserController
**Routes :**
- CRUD utilisateurs
- `change_block_status()` : Bloquer/débloquer utilisateur
- `profile()` : Profil admin
- `passwordUpdate()` : Mettre à jour mot de passe

#### PayoutController
**Routes :**
- `restaurant_payout()` : Liste paiements restaurants
- `driver_payout()` : Liste paiements livreurs
- `restaurant_pay()` : Effectuer paiement restaurant
- `driver_pay()` : Effectuer paiement livreur

---

### 2.3 Contrôleurs Restaurant (`app/Http/Controllers/restaurant/`)

#### DashboardController
**Routes :**
- Tableau de bord restaurant
- `notifications()` : Notifications

#### CategoryController
**Routes :**
- CRUD catégories
- `search()` : Recherche de catégories

#### ProductController
**Routes :**
- CRUD produits
- `change_product_featured_status()` : Mettre en avant

#### OrderController
**Routes :**
- `all_orders()` : Toutes les commandes
- `complete_orders()` : Commandes terminées
- `cancel_orders()` : Commandes annulées
- `prepaire_order()` : Marquer en préparation
- `getPreparingOrders()` : Commandes en préparation
- `pending_orders()` : Commandes assignées
- `schedule_orders()` : Commandes programmées
- `assign_order()` : Assigner commande
- `show_order()` : Détails commande
- `assign_driver()` : Assigner livreur

#### EmployeeController
**Routes :**
- CRUD employés

#### WorkingHourController
**Routes :**
- CRUD horaires d'ouverture

#### VoucherController
**Routes :**
- CRUD bons de réduction

#### PaymentHistoryController
**Routes :**
- Historique des paiements restaurant

#### HomeController
**Routes :**
- `delivery_boundary()` : Zone de livraison

#### UserController
**Routes :**
- `profile()` : Profil restaurant
- `profile_update()` : Mettre à jour profil

---

### 2.4 Contrôleurs Delivery (`app/Http/Controllers/delivery/`)

#### DashboardController
**Routes :**
- Tableau de bord livreur

#### OrderController
**Routes :**
- `all_orders()` : Toutes les commandes
- `complete_orders()` : Commandes terminées
- `cancel_orders()` : Commandes annulées
- `pending_orders()` : Commandes en attente
- `schedule_orders()` : Commandes programmées
- `cancel_order()` : Annuler commande

#### PaymentHistoryController
**Routes :**
- Historique des gains livreur

---

### 2.5 Contrôleurs API (`app/Http/Controllers/api/`)

#### UserController
**Endpoints :**
- `POST /api/register` : Inscription
- `POST /api/login` : Connexion
- `GET /api/user_profile/{user}` : Profil utilisateur
- `POST /api/update_profile` : Mettre à jour profil
- `POST /api/forgot_password` : Mot de passe oublié
- `POST /api/add_user_address` : Ajouter adresse
- `GET /api/get_user_address/{user}` : Récupérer adresses
- `POST /api/track_pendings_orders` : Suivre commandes en attente
- `POST /api/track_completed_orders` : Suivre commandes terminées
- `POST /api/reviews_and_ratings` : Envoyer avis
- `POST /api/user_device_token` : Token device notifications

#### DriverController
**Endpoints :**
- `POST /api/driver_register` : Inscription livreur
- `POST /api/driver_login` : Connexion livreur
- `GET /api/driver_profile/{driver}` : Profil livreur
- `POST /api/driver_update_profile` : Mettre à jour profil
- `POST /api/driver_forgot_password` : Mot de passe oublié
- `POST /api/set_driver_online/{driver}` : Mettre en ligne
- `GET /api/set_driver_offline/{driver}` : Mettre hors ligne
- `POST /api/set_time_for_online` : Définir horaire en ligne
- `GET /api/order_request/{driver}` : Demandes de commandes
- `POST /api/order_accept_by_driver` : Accepter commande
- `GET /api/ordered_product/{orderno}` : Produits d'une commande
- `POST /api/driver_earning_history/{driver}` : Historique gains
- `GET /api/delivery_summary/{driver}` : Résumé livraisons
- `GET /api/latest_news` : Dernières actualités

#### IndexController
**Endpoints :**
- `POST /api/home_data` : Données page d'accueil
- `GET /api/product_detail/{product}` : Détails produit
- `POST /api/search_filters` : Recherche avec filtres
- `POST /api/search_by_keyword` : Recherche par mot-clé
- `GET /api/restaurant_detail/{restaurant}` : Détails restaurant

#### CartController
**Endpoints :**
- `POST /api/add_to_cart` : Ajouter au panier
- `GET /api/show_cart_details/{user}` : Détails panier
- `POST /api/update_cart_details` : Mettre à jour panier
- `DELETE /api/delete_cart_product/{cart}` : Supprimer produit panier
- `DELETE /api/delete_previous_cart/{user}` : Vider panier

#### OrderController
**Endpoints :**
- `POST /api/place_orders` : Passer commande
- `GET /api/user_pending_orders/{user}` : Commandes en attente
- `GET /api/user_completed_order_history/{user}` : Historique commandes
- `POST /api/complete_orders` : Marquer comme complétée

#### RestaurantController
**Endpoints :**
- `POST /api/search_restaurant` : Rechercher restaurants
- `GET /api/restaurants_with_category/{cuisine}` : Restaurants par cuisine
- `GET /api/get_filters` : Récupérer filtres disponibles
- `GET /api/about_restaurant/{restaurant}` : À propos restaurant

#### VoucherController
**Endpoints :**
- `POST /api/get_voucher` : Valider code promo

#### ReasonController
**Endpoints :**
- `GET /api/get_reason` : Récupérer raisons d'annulation
- `POST /api/reject_order_request` : Refuser commande

#### ReviewController
**Endpoints :**
- `GET /api/driver_reviews/{driver}` : Avis sur livreur

---

## 3. MIDDLEWARE

### 3.1 Middleware Personnalisés

#### AdminMiddleware
**Fichier :** `app/Http/Middleware/AdminMiddleware.php`  
**Vérification :** `auth()->user()->type === 'admin'`  
**Action si échec :** Redirection en arrière

#### RestaurantMiddleware
**Fichier :** `app/Http/Middleware/RestaurantMiddleware.php`  
**Vérification :** `auth()->user()->type === 'restaurant'`  
**Action si échec :** Redirection en arrière

#### DeliveryMiddleware
**Fichier :** `app/Http/Middleware/DeliveryMiddleware.php`  
**Vérification :** Type utilisateur = 'delivery'  
**Action si échec :** Redirection

#### UserMiddleware
**Fichier :** `app/Http/Middleware/UserMiddleware.php`  
**Vérification :** Type utilisateur = 'user'  
**Action si échec :** Redirection

### 3.2 Middleware Laravel Standards

- **Authenticate** : Vérification authentification
- **RedirectIfAuthenticated** : Redirection si déjà authentifié
- **VerifyCsrfToken** : Protection CSRF
- **EncryptCookies** : Chiffrement cookies
- **TrustProxies** : Confiance dans les proxies
- **TrimStrings** : Nettoyage chaînes
- **CheckForMaintenanceMode** : Mode maintenance

---

## 4. ROUTES

### 4.1 Routes Web (`routes/web.php`)

**Total : ~90 routes**

#### Routes Publiques
- Page d'accueil
- Détails restaurant/produit
- Panier et checkout
- Authentification (login, signup, logout)
- Contact, About, Terms, Privacy
- Inscription livreur/restaurant partenaire

#### Routes Admin (Préfixe `/admin`)
- Dashboard
- Gestion restaurants
- Gestion livreurs
- Gestion commandes
- Gestion utilisateurs
- Paiements (payouts)
- Configuration (frais, véhicules, etc.)

#### Routes Restaurant (Préfixe `/restaurant`)
- Dashboard
- Gestion produits/catégories
- Gestion commandes
- Gestion employés
- Horaires d'ouverture
- Bons de réduction
- Historique paiements

#### Routes Delivery (Préfixe `/restaurant/delivery`)
- Dashboard
- Gestion commandes
- Historique gains

### 4.2 Routes API (`routes/api.php`)

**Total : ~35 endpoints**

- Authentification (register, login, forgot password)
- Profils utilisateurs/livreurs
- Données publiques (restaurants, produits)
- Panier
- Commandes
- Recherche et filtres
- Avis et notations
- Notifications

---

## 5. VUES (VIEWS)

### 5.1 Structure des Vues

#### Frontend (`resources/views/frontend/`)
- **Layouts :** `layouts/app.blade.php`
- **Pages :**
  - `index.blade.php` : Page d'accueil
  - `restaurant.blade.php` : Liste restaurants
  - `product_detail.blade.php` : Détail produit
  - `cart.blade.php` : Panier
  - `checkout.blade.php` : Finalisation commande
  - `profile.blade.php` : Profil utilisateur
  - `login.blade.php`, `signup.blade.php` : Authentification
  - `forgot.blade.php` : Mot de passe oublié
  - `contact.blade.php` : Contact
  - `about.blade.php` : À propos
  - `terms.blade.php` : Conditions
  - `driver.blade.php` : Inscription livreur
  - `search.blade.php` : Résultats recherche

#### Admin (`resources/views/admin/`)
- Dashboard
- Formulaires de gestion (CRUD)
- Listes et tableaux
- Profil admin

#### Restaurant (`resources/views/restaurant/`)
- Dashboard restaurant
- Gestion produits
- Gestion commandes
- Statistiques

#### Auth (`resources/views/auth/`)
- Formulaires d'authentification standard Laravel

---

## 6. ASSETS FRONTEND

### 6.1 JavaScript (`resources/js/`)
- `app.js` : Point d'entrée principal
- `bootstrap.js` : Configuration Bootstrap

### 6.2 Styles (`resources/sass/`)
- Fichiers SASS compilés via Laravel Mix

### 6.3 Assets Publics (`public/`)
- `assets/` : Assets compilés
- `dist/` : Distribution
- `frontend/` : Assets frontend
- `images/` : Images
- `plugins/` : Plugins tiers (jQuery, Bootstrap, etc.)

---

## 7. MIGRATIONS BASE DE DONNÉES

### 7.1 Tables Principales

1. `users` - Utilisateurs
2. `restaurants` - Restaurants
3. `drivers` - Livreurs
4. `products` - Produits
5. `categories` - Catégories
6. `orders` - Commandes
7. `carts` - Paniers
8. `cuisines` - Types de cuisines
9. `cuisine_restaurant` - Relation many-to-many
10. `vehicles` - Véhicules
11. `extras` - Extras produits
12. `cart_extras` - Extras dans panier
13. `employees` - Employés
14. `working_hours` - Horaires
15. `vouchers` - Bons de réduction
16. `driver_payments` - Paiements livreurs
17. `restaurant_payments` - Paiements restaurants
18. `cancellation_reasons` - Raisons annulation
19. `types` - Types divers
20. `password_resets` - Réinitialisation MDP
21. `failed_jobs` - Jobs échoués

---

## 8. FONCTIONNALITÉS PAR MODULE

### 8.1 Gestion des Utilisateurs
- ✅ Inscription/Connexion multi-rôles
- ✅ Profils utilisateurs
- ✅ Gestion des adresses
- ✅ Réinitialisation mot de passe
- ✅ Tokens API (Passport)

### 8.2 Gestion des Restaurants
- ✅ CRUD restaurants
- ✅ Gestion produits/catégories
- ✅ Gestion employés
- ✅ Horaires d'ouverture
- ✅ Zones de livraison
- ✅ Statuts (actif/inactif, featured)

### 8.3 Gestion des Commandes
- ✅ Création de commandes
- ✅ Panier d'achat
- ✅ Statuts de commande
- ✅ Assignation livreur
- ✅ Suivi en temps réel
- ✅ Historique

### 8.4 Gestion des Livreurs
- ✅ Inscription/Connexion
- ✅ Gestion véhicules
- ✅ Acceptation/refus commandes
- ✅ Statut en ligne/hors ligne
- ✅ Historique gains

### 8.5 Paiements
- ✅ Intégration PayPal
- ✅ Intégration Stripe (mentionné dans routes)
- ✅ Calcul commissions
- ✅ Paiements restaurants/livreurs
- ✅ Bons de réduction

### 8.6 Recherche et Filtrage
- ✅ Recherche par mot-clé
- ✅ Filtres par cuisine
- ✅ Filtres par critères
- ✅ Tri et pagination

### 8.7 Notifications
- ✅ Notifications push
- ✅ Notifications par email
- ✅ Actualités système

---

## 9. DÉPENDANCES PRINCIPALES

### PHP (Composer)
- Laravel Framework 10.10
- Laravel Passport 11.9
- Laravel Sanctum 3.2
- Guzzle HTTP 7.2

### JavaScript (NPM)
- Laravel Mix 5.0.1
- Axios 0.19
- SASS 1.15.2
- Webpack

---

**Fin de l'analyse des composants**

