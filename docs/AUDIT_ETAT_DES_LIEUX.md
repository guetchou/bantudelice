# AUDIT DU SITE - ÉTAT DES LIEUX
## Analyse Complète de l'Application TheDrop247

**Date de l'audit :** $(date)  
**Projet :** TheDrop247  
**Type d'application :** Plateforme de livraison de nourriture (Food Delivery Platform)  
**Framework :** Laravel 10.10

---

## 1. VUE D'ENSEMBLE DU PROJET

### 1.1 Description Générale
TheDrop247 est une application web de livraison de nourriture développée en Laravel 10. L'application permet la gestion complète d'une plateforme de livraison avec :
- **Utilisateurs finaux** (Clients) : Commandes de nourriture
- **Restaurants** : Gestion des produits, commandes, paiements
- **Livreurs (Drivers)** : Gestion des livraisons
- **Administrateurs** : Gestion globale de la plateforme

### 1.2 Technologies Utilisées

#### Backend
- **PHP** : Version 8.1+
- **Laravel Framework** : Version 10.10
- **Laravel Passport** : Version 11.9 (Authentification API OAuth2)
- **Laravel Sanctum** : Version 3.2 (Authentification SPA/token)
- **Guzzle HTTP** : Version 7.2 (Client HTTP)

#### Frontend
- **Laravel Mix** : Version 5.0.1 (Build tool)
- **Webpack** : Compilation des assets
- **SASS** : Version 1.15.2 (Préprocesseur CSS)
- **Axios** : Version 0.19 (Client HTTP pour JavaScript)
- **Blade Templates** : Moteur de template Laravel

#### Base de Données
- **MySQL** (par défaut)
- Support pour PostgreSQL et SQLite

#### Authentification
- **Laravel Passport** pour l'API
- **Laravel Sanctum** pour les sessions web
- Middleware personnalisés pour les différents rôles

---

## 2. ARCHITECTURE ET STRUCTURE DU PROJET

### 2.1 Structure des Répertoires

```
/opt/thedrop247/
├── app/                    # Code source de l'application
│   ├── Http/
│   │   ├── Controllers/    # Contrôleurs MVC
│   │   └── Middleware/     # Middlewares d'authentification/autorisation
│   ├── Models/             # Modèles Eloquent
│   ├── Mail/               # Classes de notification email
│   └── Providers/          # Service providers
├── bootstrap/              # Fichiers de démarrage
├── config/                 # Fichiers de configuration
├── database/
│   └── migrations/         # Migrations de base de données
├── public/                 # Point d'entrée web
├── resources/
│   ├── js/                 # Assets JavaScript
│   ├── sass/               # Fichiers SASS
│   └── views/              # Templates Blade
├── routes/                 # Définition des routes
├── storage/                # Fichiers de stockage
└── vendor/                 # Dépendances Composer
```

### 2.2 Types d'Utilisateurs et Rôles

L'application définit 4 types d'utilisateurs :

1. **Admin** (`type === 'admin'`)
   - Gestion globale de la plateforme
   - Gestion des restaurants, livreurs, commandes
   - Paiements et statistiques

2. **Restaurant** (`type === 'restaurant'`)
   - Gestion des produits et catégories
   - Gestion des commandes
   - Suivi des paiements
   - Gestion des employés et horaires

3. **Delivery/Driver** (`type === 'delivery'`)
   - Gestion des livraisons
   - Suivi des commandes assignées
   - Historique des gains

4. **User/Customer** (`type === 'user'`)
   - Commandes de produits
   - Gestion du panier
   - Profil et adresses

---

## 3. COMPOSANTS PRINCIPAUX

### 3.1 Modèles (Models)

#### Modèles Principaux Identifiés

1. **User** (`app/User.php`)
   - Authentification via Laravel Passport
   - Relations : Restaurant, Orders, CancellationReasons
   - Champs : name, email, password, phone, type, image

2. **Restaurant** (`app/Restaurant.php`)
   - Informations du restaurant
   - Relations : Cuisines, Drivers, Orders, Products, Employees, WorkingHours
   - Champs : name, email, city, address, phone, latitude, longitude, min_order, etc.

3. **Order** (`app/Order.php`)
   - Commandes clients
   - Relations : Restaurant, Product, Driver, User
   - Champs : restaurant_id, user_id, product_id, qty, price, delivery_address, scheduled_date, etc.

4. **Product** (`app/Product.php`)
   - Produits des restaurants
   - Relations : Restaurant, Category, Orders, Extras
   - Champs : restaurant_id, category_id, name, image, price, discount_price

5. **Driver** (`app/Driver.php`)
   - Livreurs
   - Authentification via Passport
   - Relations : Restaurant, Vehicle, Orders, DriverPayments
   - Champs : restaurant_id, name, email, phone, hourly_pay, vehicle, etc.

6. **Cart** (`app/Cart.php`)
   - Panier d'achat
   - Gestion des produits en attente de commande

7. **Category** (`app/Category.php`)
   - Catégories de produits

8. **Cuisine** (`app/Cuisine.php`)
   - Types de cuisines (relation many-to-many avec Restaurant)

9. **Voucher** (`app/Voucher.php`)
   - Codes promo/bons de réduction

10. **Rating/Review** (`app/Rating.php`, `app/Review.php`)
    - Système d'avis et de notation

### 3.2 Contrôleurs (Controllers)

#### Contrôleurs Frontend
- **IndexController** : Page d'accueil, détails restaurant, panier, checkout
- **HomeController** : Tableau de bord
- **LoginController** : Authentification
- **ContactController** : Formulaire de contact
- **DriverController** : Inscription livreur
- **PartnerController** : Inscription restaurant partenaire
- **PaypalController** : Gestion des paiements PayPal

#### Contrôleurs Admin (`app/Http/Controllers/admin/`)
- **DashboardController** : Tableau de bord admin
- **RestaurantController** : Gestion restaurants
- **DriverController** : Gestion livreurs
- **OrderController** : Gestion commandes
- **CuisineController** : Gestion cuisines
- **NewsController** : Actualités et notifications
- **ChargeController** : Gestion des frais
- **VehicleController** : Gestion véhicules
- **UserController** : Gestion utilisateurs
- **PayoutController** : Paiements restaurants/livreurs

#### Contrôleurs Restaurant (`app/Http/Controllers/restaurant/`)
- **DashboardController** : Tableau de bord restaurant
- **CategoryController** : Gestion catégories
- **ProductController** : Gestion produits
- **OrderController** : Gestion commandes
- **EmployeeController** : Gestion employés
- **WorkingHourController** : Horaires d'ouverture
- **VoucherController** : Gestion bons de réduction
- **PaymentHistoryController** : Historique paiements

#### Contrôleurs API (`app/Http/Controllers/api/`)
- **UserController** : API utilisateurs (inscription, login, profil)
- **DriverController** : API livreurs
- **IndexController** : API données publiques
- **CartController** : API panier
- **OrderController** : API commandes
- **RestaurantController** : API restaurants
- **VoucherController** : API bons
- **ReviewController** : API avis

### 3.3 Middleware

1. **AdminMiddleware** : Vérifie que l'utilisateur est admin
2. **RestaurantMiddleware** : Vérifie que l'utilisateur est restaurant
3. **DeliveryMiddleware** : Vérifie que l'utilisateur est livreur
4. **UserMiddleware** : Vérifie que l'utilisateur est client
5. **Authenticate** : Authentification standard Laravel
6. **VerifyCsrfToken** : Protection CSRF

### 3.4 Routes

#### Routes Web (`routes/web.php`)
- **Frontend** : ~30 routes publiques
- **Admin** : ~50 routes protégées (préfixe `/admin`)
- **Restaurant** : ~30 routes protégées (préfixe `/restaurant`)
- **Delivery** : ~10 routes protégées (préfixe `/restaurant/delivery`)

#### Routes API (`routes/api.php`)
- **Authentification** : register, login, forgot_password
- **User** : profil, adresses, commandes
- **Driver** : profil, commandes, gains
- **Restaurant** : recherche, détails
- **Cart** : ajout, modification, suppression
- **Order** : création, historique
- **Voucher** : validation

### 3.5 Vues (Views)

#### Frontend (`resources/views/frontend/`)
- `index.blade.php` : Page d'accueil
- `restaurant.blade.php` : Liste restaurants
- `product_detail.blade.php` : Détail produit
- `cart.blade.php` : Panier
- `checkout.blade.php` : Finalisation commande
- `profile.blade.php` : Profil utilisateur
- `login.blade.php`, `signup.blade.php` : Authentification

#### Admin (`resources/views/admin/`)
- Tableaux de bord et formulaires de gestion
- Gestion restaurants, commandes, utilisateurs

#### Restaurant (`resources/views/restaurant/`)
- Interface de gestion restaurant
- Produits, commandes, statistiques

---

## 4. VARIABLES D'ENVIRONNEMENT

### 4.1 Fichiers .env

**⚠️ IMPORTANT :** Aucun fichier `.env` ou `.env.example` n'a été trouvé dans le projet.  
Il est **essentiel** de créer un fichier `.env` pour le fonctionnement de l'application.

### 4.2 Variables d'Environnement Identifiées

#### Application Générale
```
APP_NAME=Laravel                    # Nom de l'application
APP_ENV=production                  # Environnement (local, staging, production)
APP_KEY=                            # Clé de chiffrement (OBLIGATOIRE)
APP_DEBUG=false                     # Mode debug (false en production)
APP_URL=http://localhost            # URL de base de l'application
ASSET_URL=                          # URL des assets (optionnel)
```

#### Base de Données
```
DB_CONNECTION=mysql                 # Type de base (mysql, pgsql, sqlite)
DB_HOST=127.0.0.1                   # Hôte de la base de données
DB_PORT=3306                        # Port MySQL (3306) ou PostgreSQL (5432)
DB_DATABASE=forge                   # Nom de la base de données
DB_USERNAME=forge                   # Utilisateur de la base
DB_PASSWORD=                        # Mot de passe de la base
DB_SOCKET=                          # Socket Unix (optionnel)
MYSQL_ATTR_SSL_CA=                  # Certificat SSL MySQL (optionnel)
DB_FOREIGN_KEYS=true                # Clés étrangères
```

#### Cache et Sessions
```
CACHE_DRIVER=file                   # Driver de cache (file, redis, memcached)
SESSION_DRIVER=file                 # Driver de session (file, redis, database)
SESSION_LIFETIME=120                # Durée de session en minutes
SESSION_DOMAIN=                     # Domaine de session (optionnel)
SESSION_SECURE_COOKIE=false         # Cookie sécurisé HTTPS (true en production)
SESSION_CONNECTION=                 # Connexion Redis pour session
SESSION_STORE=                      # Store pour session
```

#### Redis (si utilisé)
```
REDIS_CLIENT=phpredis               # Client Redis
REDIS_HOST=127.0.0.1                # Hôte Redis
REDIS_PASSWORD=                     # Mot de passe Redis
REDIS_PORT=6379                     # Port Redis
REDIS_DB=0                          # Base de données Redis
REDIS_CACHE_DB=1                    # Base Redis pour cache
REDIS_PREFIX=                       # Préfixe des clés Redis
REDIS_CLUSTER=                      # Cluster Redis (optionnel)
REDIS_URL=                          # URL Redis complète (optionnel)
```

#### Queue (File d'attente)
```
QUEUE_CONNECTION=sync               # Connexion queue (sync, database, redis, sqs)
QUEUE_FAILED_DRIVER=database        # Driver pour jobs échoués
```

#### Mail (Email)
```
MAIL_DRIVER=smtp                    # Driver mail (smtp, mailgun, postmark, ses)
MAIL_HOST=smtp.mailgun.org          # Serveur SMTP
MAIL_PORT=587                       # Port SMTP
MAIL_USERNAME=                      # Utilisateur SMTP
MAIL_PASSWORD=                      # Mot de passe SMTP
MAIL_ENCRYPTION=tls                 # Chiffrement (tls, ssl)
MAIL_FROM_ADDRESS=hello@example.com # Adresse expéditeur
MAIL_FROM_NAME="Example"            # Nom expéditeur
MAIL_LOG_CHANNEL=                   # Canal de log pour emails
```

#### Services Tiers

**Mailgun**
```
MAILGUN_DOMAIN=                     # Domaine Mailgun
MAILGUN_SECRET=                     # Clé secrète Mailgun
MAILGUN_ENDPOINT=api.mailgun.net    # Endpoint Mailgun
```

**Postmark**
```
POSTMARK_TOKEN=                     # Token Postmark
```

**AWS (S3, SES, SQS)**
```
AWS_ACCESS_KEY_ID=                  # Clé d'accès AWS
AWS_SECRET_ACCESS_KEY=              # Clé secrète AWS
AWS_DEFAULT_REGION=us-east-1        # Région AWS
AWS_BUCKET=                         # Bucket S3
AWS_URL=                            # URL S3
```

**PayPal** (configuré dans `config/paypal.php`)
```
PAYPAL_CLIENT_ID=                   # ID client PayPal
PAYPAL_SECRET=                      # Secret PayPal
PAYPAL_MODE=sandbox                 # Mode (sandbox ou live)
```

**Pusher** (Broadcasting - non utilisé actuellement)
```
PUSHER_APP_ID=                      # ID application Pusher
PUSHER_APP_KEY=                     # Clé Pusher
PUSHER_APP_SECRET=                  # Secret Pusher
PUSHER_APP_CLUSTER=                 # Cluster Pusher
```

#### Logging
```
LOG_CHANNEL=stack                   # Canal de log (stack, single, daily, slack)
LOG_SLACK_WEBHOOK_URL=              # URL webhook Slack (optionnel)
PAPERTRAIL_URL=                     # URL Papertrail (optionnel)
PAPERTRAIL_PORT=                    # Port Papertrail (optionnel)
LOG_STDERR_FORMATTER=               # Formatteur stderr (optionnel)
```

#### Broadcasting
```
BROADCAST_DRIVER=null               # Driver broadcasting (null, pusher, redis)
```

#### Hashing
```
BCRYPT_ROUNDS=10                    # Rounds pour bcrypt
```

#### Filesystem
```
FILESYSTEM_DRIVER=local             # Driver de stockage (local, s3)
FILESYSTEM_CLOUD=s3                 # Driver cloud (s3)
```

#### Memcached (si utilisé)
```
MEMCACHED_PERSISTENT_ID=            # ID persistant Memcached
MEMCACHED_USERNAME=                 # Utilisateur Memcached
MEMCACHED_PASSWORD=                 # Mot de passe Memcached
MEMCACHED_HOST=127.0.0.1            # Hôte Memcached
MEMCACHED_PORT=11211                # Port Memcached
```

#### DynamoDB (si utilisé)
```
DYNAMODB_CACHE_TABLE=cache          # Table DynamoDB pour cache
DYNAMODB_ENDPOINT=                  # Endpoint DynamoDB
```

### 4.3 Variables Non Définies mais Utilisées

Certaines variables peuvent être nécessaires mais non explicitement définies :
- Variables spécifiques à l'application (non détectées dans les configs standards)
- Clés API pour services de paiement (Stripe, autres)
- Tokens pour notifications push

---

## 5. CONFIGURATIONS IMPORTANTES

### 5.1 Configuration Base de Données (`config/database.php`)
- **Par défaut** : MySQL
- Support PostgreSQL, SQLite, SQL Server
- Charset : utf8mb4
- Collation : utf8mb4_unicode_ci
- Strict mode : **désactivé** (strict: false)

### 5.2 Configuration PayPal (`config/paypal.php`)
- Mode sandbox par défaut
- Logging activé vers `storage/logs/paypal.log`
- Timeout : 30 secondes

### 5.3 Configuration Mail (`config/mail.php`)
- Driver SMTP par défaut
- Support Mailgun, Postmark, SES

### 5.4 Configuration Session (`config/session.php`)
- Driver file par défaut
- Durée : 120 minutes
- Cookie sécurisé : false (à activer en production avec HTTPS)

---

## 6. POINTS D'ATTENTION ET SÉCURITÉ

### 6.1 Points Critiques Identifiés

1. **⚠️ Fichier .env manquant**
   - Aucun fichier `.env` trouvé
   - Créer un `.env.example` avec toutes les variables documentées

2. **⚠️ Mode Debug**
   - Vérifier que `APP_DEBUG=false` en production
   - Ne jamais exposer les erreurs en production

3. **⚠️ Clé d'Application**
   - `APP_KEY` doit être générée : `php artisan key:generate`
   - Essentielle pour le chiffrement des sessions

4. **⚠️ Sécurité des Cookies**
   - `SESSION_SECURE_COOKIE=false` : activer à `true` avec HTTPS
   - Configurer `SESSION_DOMAIN` si nécessaire

5. **⚠️ Base de Données**
   - Changer les credentials par défaut (`forge/forge`)
   - Utiliser des mots de passe forts
   - Activer SSL pour les connexions distantes

6. **⚠️ Middleware d'Authentification**
   - Vérifications basiques (type === 'admin')
   - Pas de vérification d'email vérifié
   - Pas de vérification de compte actif

7. **⚠️ Protection CSRF**
   - Activée pour les routes web
   - Vérifier que les routes API sont correctement protégées

8. **⚠️ PayPal en Mode Sandbox**
   - Vérifier que `PAYPAL_MODE` est bien en `sandbox` pour les tests
   - Changer en `live` uniquement après validation complète

### 6.2 Recommandations de Sécurité

1. **Créer un fichier `.env.example`**
   - Documenter toutes les variables nécessaires
   - Sans valeurs sensibles

2. **Mettre à jour les dépendances**
   - Vérifier les vulnérabilités avec `composer audit`
   - Mettre à jour Laravel et packages régulièrement

3. **Sécuriser les uploads**
   - Valider les types de fichiers
   - Stocker hors de `public/`
   - Limiter la taille des uploads

4. **Logs et Monitoring**
   - Configurer les logs appropriés
   - Ne pas logger de données sensibles
   - Surveiller les tentatives d'intrusion

5. **HTTPS**
   - Forcer HTTPS en production
   - Configurer les cookies sécurisés

6. **Rate Limiting**
   - API : 60 requêtes/minute (configuré)
   - Ajouter rate limiting sur les formulaires critiques

---

## 7. FONCTIONNALITÉS PRINCIPALES

### 7.1 Frontend Client
- ✅ Consultation des restaurants
- ✅ Recherche de restaurants
- ✅ Filtrage par cuisine
- ✅ Détails produits
- ✅ Panier d'achat
- ✅ Checkout avec adresse de livraison
- ✅ Paiements (Stripe, PayPal)
- ✅ Système de bons de réduction
- ✅ Historique des commandes
- ✅ Profil utilisateur
- ✅ Avis et notations

### 7.2 Backend Restaurant
- ✅ Gestion des produits et catégories
- ✅ Gestion des commandes
- ✅ Gestion des employés
- ✅ Horaires d'ouverture
- ✅ Bons de réduction
- ✅ Historique des paiements
- ✅ Notifications

### 7.3 Backend Livreur
- ✅ Acceptation/refus de commandes
- ✅ Suivi des livraisons
- ✅ Historique des gains
- ✅ Profil et disponibilité

### 7.4 Backend Admin
- ✅ Gestion des restaurants
- ✅ Gestion des livreurs
- ✅ Gestion des commandes
- ✅ Gestion des utilisateurs
- ✅ Configuration des frais
- ✅ Paiements restaurants/livreurs
- ✅ Actualités et notifications

### 7.5 API REST
- ✅ Authentification (register, login)
- ✅ Gestion profil utilisateur
- ✅ Recherche restaurants
- ✅ Panier
- ✅ Commandes
- ✅ Avis et notations
- ✅ API livreur complète

---

## 8. BASE DE DONNÉES

### 8.1 Tables Principales (Migrations Identifiées)

1. `users` - Utilisateurs (admin, restaurant, delivery, user)
2. `restaurants` - Restaurants
3. `cuisines` - Types de cuisines
4. `cuisine_restaurant` - Relation many-to-many
5. `drivers` - Livreurs
6. `vehicles` - Véhicules
7. `extras` - Extras pour produits
8. `orders` - Commandes
9. `driver_payments` - Paiements livreurs
10. `restaurant_payments` - Paiements restaurants
11. `categories` - Catégories de produits
12. `products` - Produits
13. `employees` - Employés restaurants
14. `working_hours` - Horaires d'ouverture
15. `cancellation_reasons` - Raisons d'annulation
16. `carts` - Paniers
17. `cart_extras` - Extras dans panier
18. `types` - Types (non spécifié)
19. `vouchers` - Bons de réduction
20. `password_resets` - Réinitialisation mots de passe
21. `failed_jobs` - Jobs échoués

---

## 9. RECOMMANDATIONS

### 9.1 Actions Immédiates Requises

1. **Créer le fichier `.env`**
   ```bash
   cp .env.example .env  # Si .env.example existe
   php artisan key:generate
   ```

2. **Configurer la base de données**
   - Créer la base de données
   - Configurer les credentials dans `.env`
   - Exécuter les migrations : `php artisan migrate`

3. **Installer les dépendances**
   ```bash
   composer install
   npm install
   npm run production
   ```

4. **Configurer les permissions**
   ```bash
   chmod -R 775 storage bootstrap/cache
   chown -R www-data:www-data storage bootstrap/cache
   ```

5. **Générer les clés Passport**
   ```bash
   php artisan passport:install
   ```

### 9.2 Améliorations Recommandées

1. **Documentation**
   - Créer un README.md détaillé
   - Documenter l'installation
   - Documenter les APIs

2. **Tests**
   - Ajouter des tests unitaires
   - Tests d'intégration
   - Tests fonctionnels

3. **Sécurité**
   - Audit de sécurité complet
   - Validation des entrées
   - Protection contre les injections SQL (déjà géré par Eloquent)
   - Protection XSS (déjà géré par Blade)

4. **Performance**
   - Cache des requêtes fréquentes
   - Optimisation des images
   - CDN pour les assets statiques

5. **Code Quality**
   - Respect des PSR-12
   - Refactoring du code dupliqué
   - Amélioration de la structure des contrôleurs

---

## 10. FICHIER .env RECOMMANDÉ

Voici un modèle de fichier `.env` recommandé (à personnaliser) :

```env
APP_NAME="TheDrop247"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://votre-domaine.com

LOG_CHANNEL=stack

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=thedrop247
DB_USERNAME=thedrop247_user
DB_PASSWORD=votre_mot_de_passe_securise

BROADCAST_DRIVER=null
CACHE_DRIVER=file
FILESYSTEM_DRIVER=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@thedrop247.com"
MAIL_FROM_NAME="${APP_NAME}"

PAYPAL_CLIENT_ID=votre_client_id
PAYPAL_SECRET=votre_secret
PAYPAL_MODE=sandbox

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_APP_CLUSTER=mt1

MIX_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
MIX_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
```

---

## 11. CONCLUSION

L'application TheDrop247 est une plateforme de livraison de nourriture complète avec :
- ✅ Architecture Laravel moderne (10.10)
- ✅ Séparation des rôles claire
- ✅ API REST fonctionnelle
- ✅ Gestion complète des commandes et paiements
- ⚠️ Configuration d'environnement à compléter
- ⚠️ Sécurisation à renforcer (HTTPS, cookies, etc.)

**Prochaines étapes prioritaires :**
1. Créer et configurer le fichier `.env`
2. Installer et configurer la base de données
3. Générer la clé d'application
4. Configurer les clés Passport
5. Effectuer un audit de sécurité complet

---

**Fin du rapport d'audit**

