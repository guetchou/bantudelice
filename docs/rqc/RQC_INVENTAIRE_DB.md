# RQC - INVENTAIRE BASE DE DONNÉES BANTUDELICE

**Date**: 2025-12-15  
**Statut**: ⚠️ **PARTIELLEMENT VALIDÉ** (impossible de vérifier l'état réel)

---

## 1. ÉTAT DES MIGRATIONS

### 1.1 Nombre de migrations

**Total**: 32 migrations détectées

**Répartition**:
- Migrations Laravel de base: 3
- Migrations métier (2020): 18
- Migrations récentes (2024-2025): 11

### 1.2 Vérification de l'état

**❌ IMPOSSIBLE DE VÉRIFIER**:
```
could not find driver (Connection: mysql, SQL: select * from information_schema.tables...)
```

**Cause**: Extension PDO MySQL non installée ou configuration DB incorrecte.

**Impact**: 
- Impossible d'exécuter `php artisan migrate:status`
- Impossible de vérifier quelles migrations ont été exécutées
- Impossible d'inspecter le schéma réel de la base

**Priorité**: CRITIQUE - Résoudre avant toute validation.

---

## 2. SCHÉMA DE BASE DE DONNÉES

### 2.1 Tables principales

#### Utilisateurs & Authentification
- `users` - Utilisateurs (user, admin, restaurant)
- `password_resets` - Réinitialisation de mots de passe
- `failed_jobs` - Jobs échoués (queue)

#### Restaurants & Produits
- `restaurants` - Restaurants
- `cuisines` - Types de cuisines
- `cuisine_restaurant` - Relation many-to-many
- `categories` - Catégories de produits
- `products` - Produits
- `extras` - Extras/options produits
- `types` - Types d'extras
- `cart_extras` - Extras dans le panier

#### Commandes & Livraisons
- `orders` - Commandes
- `carts` - Paniers
- `completed_orders` - Commandes complétées
- `deliveries` - Livraisons (nouveau module)
- `cancellation_reasons` - Raisons d'annulation

#### Paiements & Finances
- `payments` - Paiements (nouveau module)
- `driver_payments` - Paiements livreurs
- `restaurant_payments` - Paiements restaurants
- `charges` - Frais/charges
- `vouchers` - Codes promo

#### Livreurs & Véhicules
- `drivers` - Livreurs
- `vehicles` - Véhicules
- `driver_history` - Historique livreurs (si existe)

#### Autres
- `employees` - Employés restaurants
- `working_hours` - Horaires d'ouverture
- `ratings` - Notes/évaluations
- `loyalty_points` - Points de fidélité
- `loyalty_transactions` - Transactions fidélité
- `system_config` - Configuration système
- `news` - Actualités

### 2.2 Tables critiques

#### `users`
```php
Schema::create('users', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->string('name')->nullable();
    $table->string('email')->unique();
    $table->string('password');
    $table->string('firebase_id')->nullable()->unique();
    $table->string('image')->nullable();
    $table->string('phone')->nullable();
    $table->boolean('blocked')->default(false);
    $table->enum('type', ['user','admin', 'restaurant'])->default('user');
    $table->timestamps();
});
```

**✅ BON**:
- `email` unique
- `firebase_id` unique (nullable)
- `type` avec enum

**⚠️ À VÉRIFIER**:
- Index sur `type` (si requêtes fréquentes)
- Index sur `phone` (si utilisé pour recherche)

#### `restaurants`
```php
Schema::create('restaurants', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->unsignedBigInteger('user_id');
    $table->string('name');
    $table->string('user_name')->unique()->nullable();
    $table->string('email')->unique();
    $table->string('password');  // ⚠️ Doublon avec users?
    $table->string('slogan')->nullable();
    $table->string('logo')->nullable();
    $table->string('cover_image')->nullable();
    $table->string('services');
    $table->double('service_charges')->nullable();
    $table->double('delivery_charges');
    $table->string('city');
    $table->double('tax',8,2);
    $table->string('address');
    $table->float('latitude',10,2)->nullable();
    $table->float('longitude',10,2)->nullable();
    $table->string('phone')->unique();
    $table->string('description')->nullable();
    $table->integer('min_order')->nullable();
    $table->time('avg_delivery_time')->nullable();
    $table->integer('delivery_range')->nullable();
    $table->double('admin_commission');
    $table->boolean('approved')->default(false);
    $table->boolean('featured')->default(false);
    $table->string('account_name');
    $table->string('account_number');
    $table->timestamps();
    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
});
```

**✅ BON**:
- `email` unique
- `phone` unique
- `user_name` unique (nullable)
- Foreign key sur `user_id` avec cascade

**⚠️ PROBLÈMES**:
- `password` dans restaurants (doublon avec users?) - **À VÉRIFIER**
- Pas d'index sur `approved` (si filtrage fréquent)
- Pas d'index sur `featured` (si filtrage fréquent)
- Pas d'index sur `city` (si recherche par ville)
- Pas d'index géospatial sur `latitude`/`longitude` (si recherche par proximité)

**🔴 CRITIQUE**: Données bancaires (`account_name`, `account_number`) en clair dans la table.

**Recommandation**: 
- Chiffrer `account_number` (ou utiliser service externe)
- Ajouter index sur colonnes fréquemment requêtées

#### `orders`
```php
Schema::create('orders', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->unsignedBigInteger('user_id');
    $table->unsignedBigInteger('restaurant_id');
    $table->unsignedBigInteger('driver_id');
    $table->bigInteger('total_items');
    $table->double('offer_discount',6,2);
    $table->double('tax',6,2);
    $table->double('delivery_charges',6,2);
    $table->double('sub_total',6,2);
    $table->double('total',6,2);
    $table->double('admin_commission',6,2);
    $table->double('restaurant_commission',6,2);
    $table->double('driver_tip',6,2);
    $table->enum('status', ['pending','assign', 'completed','cancelled','scheduled'])->default('pending');
    $table->string('delivery_address');
    $table->dateTime('scheduled_date')->nullable();
    $table->string('d_lat');
    $table->string('d_lng');
    $table->dateTime('ordered_time');
    $table->dateTime('delivered_time');
    $table->timestamps();
    $table->foreign('restaurant_id')->references('id')->on('restaurants')->onDelete('cascade');
    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
    $table->foreign('driver_id')->references('id')->on('drivers')->onDelete('cascade');
});
```

**✅ BON**:
- Foreign keys avec cascade
- Enum pour `status`

**⚠️ PROBLÈMES**:
- `driver_id` non nullable mais peut être null pour commandes non assignées
- Pas d'index sur `status` (requêtes fréquentes)
- Pas d'index sur `user_id` (requêtes fréquentes)
- Pas d'index sur `restaurant_id` (requêtes fréquentes)
- Pas d'index sur `ordered_time` (tri par date)
- `d_lat` et `d_lng` en string (devrait être decimal/float)

**🔴 CRITIQUE**: `driver_id` obligatoire alors que commande peut ne pas avoir de livreur assigné.

**Recommandation**: 
- Rendre `driver_id` nullable
- Ajouter index sur colonnes fréquemment requêtées
- Changer type de `d_lat`/`d_lng` en decimal

#### `payments` (nouveau module)
```php
Schema::create('payments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
    $table->enum('provider', ['mtn_momo', 'airtel_money', 'stripe', 'paypal'])->default('mtn_momo');
    $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])->default('pending');
    $table->decimal('amount', 15, 2);
    $table->string('currency', 3)->default('XAF');
    $table->string('provider_reference')->nullable();
    $table->text('provider_response')->nullable();
    $table->text('callback_data')->nullable();
    $table->timestamp('paid_at')->nullable();
    $table->timestamps();
    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
    $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
    $table->index('provider_reference');
    $table->index('status');
});
```

**✅ BON**:
- Foreign keys avec cascade/set null
- Index sur `provider_reference` et `status`
- Enum pour `provider` et `status`

**⚠️ À VÉRIFIER**:
- Index sur `user_id` (si requêtes fréquentes)
- Index sur `order_id` (si requêtes fréquentes)
- Index sur `created_at` (si tri par date)

#### `deliveries` (nouveau module)
```php
Schema::create('deliveries', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('order_id')->unique();
    $table->foreignId('restaurant_id')->constrained()->onDelete('cascade');
    $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
    $table->enum('status', ['pending', 'assigned', 'picked_up', 'in_transit', 'delivered', 'cancelled'])->default('pending');
    $table->decimal('pickup_latitude', 10, 8)->nullable();
    $table->decimal('pickup_longitude', 11, 8)->nullable();
    $table->decimal('delivery_latitude', 10, 8)->nullable();
    $table->decimal('delivery_longitude', 11, 8)->nullable();
    $table->timestamp('assigned_at')->nullable();
    $table->timestamp('picked_up_at')->nullable();
    $table->timestamp('delivered_at')->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();
    $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
    $table->foreign('restaurant_id')->references('id')->on('restaurants')->onDelete('cascade');
    $table->foreign('driver_id')->references('id')->on('drivers')->nullOnDelete();
    $table->index('status');
    $table->index('driver_id');
});
```

**✅ BON**:
- Foreign keys avec cascade/nullOnDelete
- Index sur `status` et `driver_id`
- Types decimal pour coordonnées GPS
- `order_id` unique (1-1 avec orders)

**⚠️ À VÉRIFIER**:
- Index sur `restaurant_id` (si requêtes fréquentes)
- Index sur `created_at` (si tri par date)

---

## 3. CONTRAINTES & INTÉGRITÉ

### 3.1 Foreign Keys

**✅ BON**: 
- La majorité des relations ont des foreign keys
- `onDelete('cascade')` configuré sur la plupart des FK
- `onDelete('set null')` ou `nullOnDelete()` pour relations optionnelles

**Exemples**:
- `orders.user_id` → `users.id` (cascade)
- `orders.restaurant_id` → `restaurants.id` (cascade)
- `orders.driver_id` → `drivers.id` (cascade) - **⚠️ Problème: non nullable**
- `payments.order_id` → `orders.id` (set null)
- `deliveries.driver_id` → `drivers.id` (nullOnDelete)

### 3.2 Contraintes d'unicité

**✅ BON**: 
- `users.email` unique
- `users.firebase_id` unique (nullable)
- `restaurants.email` unique
- `restaurants.phone` unique
- `restaurants.user_name` unique (nullable)
- `drivers.email` unique
- `drivers.phone` unique
- `drivers.user_name` unique
- `drivers.cnic` unique
- `employees.email` unique
- `employees.phone` unique
- `deliveries.order_id` unique

### 3.3 Indexes

**✅ BON**: 
- Index automatiques sur foreign keys
- Index sur colonnes uniques
- Index explicites sur `payments.provider_reference` et `payments.status`
- Index explicites sur `deliveries.status` et `deliveries.driver_id`

**⚠️ INDEXES MANQUANTS** (à vérifier selon usage):
- `orders.status`
- `orders.user_id`
- `orders.restaurant_id`
- `orders.ordered_time`
- `restaurants.approved`
- `restaurants.featured`
- `restaurants.city`
- `users.type`
- `users.phone`

**Recommandation**: Analyser les requêtes fréquentes et ajouter indexes si nécessaire.

---

## 4. TYPES DE DONNÉES

### 4.1 Problèmes détectés

#### ❌ **PROBLÈME 1**: Coordonnées GPS en string
```php
// orders table
$table->string('d_lat');
$table->string('d_lng');
```

**Impact**: 
- Pas de validation de format
- Pas de calculs géospatiaux possibles
- Risque d'erreurs de conversion

**Recommandation**: Changer en `decimal(10, 8)` ou `float`.

#### ✅ **BON**: Coordonnées GPS en decimal
```php
// deliveries table
$table->decimal('pickup_latitude', 10, 8)->nullable();
$table->decimal('pickup_longitude', 11, 8)->nullable();
```

### 4.2 Précision monétaire

**✅ BON**: 
- Utilisation de `double` ou `decimal` pour montants
- Précision appropriée (6,2 ou 15,2)

**Exemples**:
- `orders.total` → `double(6,2)`
- `payments.amount` → `decimal(15, 2)`

---

## 5. DONNÉES SENSIBLES

### 5.1 Données bancaires

**🔴 CRITIQUE**: 
```php
// restaurants table
$table->string('account_name');
$table->string('account_number');
```

**Problème**: Données bancaires en clair dans la base.

**Recommandation**: 
- Chiffrer `account_number` (Laravel Encryption)
- Ou utiliser service externe (Stripe Connect, etc.)
- Ne jamais logger ces données

### 5.2 Mots de passe

**✅ BON**: 
- `users.password` (chiffré par Laravel Hash)
- `restaurants.password` (⚠️ Doublon? À vérifier)

**⚠️ À VÉRIFIER**: Pourquoi `restaurants.password` si relation avec `users`?

### 5.3 Données personnelles

**⚠️ À VÉRIFIER**: 
- Conformité RGPD/CNIL
- Droit à l'oubli
- Anonymisation des données

---

## 6. MIGRATIONS RÉCENTES

### 6.1 Migrations 2024-2025

1. `2024_12_03_000001_add_missing_columns_to_orders.php`
2. `2024_12_03_000002_create_completed_orders_table.php`
3. `2024_12_04_000001_create_ratings_table.php`
4. `2024_12_04_000002_create_charges_table.php`
5. `2024_12_04_100000_add_payment_columns_to_orders.php`
6. `2024_12_04_100001_add_sub_total_to_carts.php`
7. `2024_12_05_100000_create_loyalty_points_table.php`
8. `2024_12_06_100000_add_location_to_drivers_table.php`
9. `2025_12_02_090149_add_foreign_keys_to_extras_table.php`
10. `2025_12_05_020206_create_system_config_table.php`
11. `2025_12_05_022940_add_order_id_to_ratings_table.php`
12. `2025_12_05_093102_add_social_auth_to_users_table.php`
13. `2025_12_05_100000_create_deliveries_table.php`
14. `2025_12_05_120000_create_payments_table.php`

**✅ BON**: Migrations récentes bien structurées avec foreign keys et indexes.

---

## 7. ANOMALIES DÉTECTÉES

### 7.1 Blocages

#### ❌ **BLOCAGE 1**: Driver MySQL non disponible
```
could not find driver (Connection: mysql)
```
**Impact**: Impossible de vérifier l'état réel de la base  
**Priorité**: CRITIQUE

### 7.2 Problèmes de schéma

#### 🔴 **CRITIQUE 1**: `orders.driver_id` non nullable
**Problème**: Commande ne peut pas exister sans livreur assigné  
**Impact**: Impossible de créer commande sans livreur  
**Fix**: Rendre nullable

#### 🟡 **PROBLÈME 2**: `restaurants.password` (doublon?)
**Problème**: Mot de passe dans restaurants alors que relation avec users  
**Impact**: Incohérence, maintenance difficile  
**Fix**: Vérifier si nécessaire, sinon supprimer

#### 🟡 **PROBLÈME 3**: Coordonnées GPS en string
**Problème**: `orders.d_lat` et `orders.d_lng` en string  
**Impact**: Pas de calculs géospatiaux, validation difficile  
**Fix**: Changer en decimal

#### 🔴 **CRITIQUE 4**: Données bancaires en clair
**Problème**: `restaurants.account_number` non chiffré  
**Impact**: Violation sécurité, non conforme RGPD  
**Fix**: Chiffrer ou utiliser service externe

### 7.3 Indexes manquants

**⚠️ À VÉRIFIER** selon usage réel:
- `orders.status`
- `orders.user_id`
- `orders.restaurant_id`
- `orders.ordered_time`
- `restaurants.approved`
- `restaurants.featured`
- `restaurants.city`
- `users.type`
- `users.phone`

---

## 8. RECOMMANDATIONS

### 8.1 Actions critiques (P0)
1. ✅ Installer extension PDO MySQL
2. ✅ Vérifier état des migrations (`php artisan migrate:status`)
3. ✅ Corriger `orders.driver_id` (rendre nullable)
4. ✅ Chiffrer `restaurants.account_number`

### 8.2 Actions importantes (P1)
1. Changer `orders.d_lat`/`d_lng` en decimal
2. Vérifier nécessité de `restaurants.password`
3. Ajouter indexes manquants (selon analyse requêtes)
4. Créer backup automatique de la base

### 8.3 Actions recommandées (P2)
1. Implémenter soft deletes sur tables critiques
2. Ajouter colonnes `created_by`/`updated_by` pour audit
3. Créer vues matérialisées pour rapports
4. Documenter schéma dans diagramme ER

---

## 9. CRITÈRES D'ACCEPTATION

Le schéma DB sera considéré "VALIDÉ" uniquement si :

- [ ] Extension PDO MySQL installée
- [ ] État des migrations vérifié
- [ ] `orders.driver_id` rendu nullable
- [ ] `restaurants.account_number` chiffré
- [ ] Coordonnées GPS en decimal
- [ ] Indexes critiques ajoutés
- [ ] Backup automatique configuré

---

## 10. STATUT FINAL

**STATUT**: ⚠️ **PARTIELLEMENT VALIDÉ**

**Raisons**:
- Blocage technique (driver MySQL)
- Problèmes de schéma détectés
- Données sensibles non chiffrées

**Prochaines étapes**: Voir `RQC_PLAN_REMEDIATION.md`

