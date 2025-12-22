# RQC - PLAN DE REMÉDIATION BANTUDELICE

**Date**: 2025-12-15  
**Statut**: ⚠️ **EN COURS**

---

## 1. PRIORISATION

### P0 - CRITIQUE (Blocages immédiats)
- Empêche le fonctionnement de l'application
- Risque sécurité élevé
- Doit être résolu avant toute mise en production

### P1 - IMPORTANT (Risques sécurité)
- Risque sécurité moyen/élevé
- Impact fonctionnel significatif
- Doit être résolu rapidement

### P2 - RECOMMANDÉ (Améliorations)
- Amélioration qualité/maintenabilité
- Impact fonctionnel faible
- Peut être planifié

---

## 2. PLAN DE REMÉDIATION PAR PRIORITÉ

### 2.1 P0 - CRITIQUE

#### ✅ **TÂCHE 1**: Installer extensions PHP manquantes

**Objectif**: Résoudre erreurs `DOMDocument not found` et `could not find driver`

**Commandes**:
```bash
# Vérifier extensions installées
php -m | grep -E "dom|xml|pdo_mysql|mysqli"

# Installer extensions (Ubuntu/Debian)
sudo apt-get update
sudo apt-get install -y php-xml php-mysql php-mysqli

# Redémarrer PHP-FPM (si applicable)
sudo systemctl restart php8.1-fpm
```

**Critères d'acceptation**:
- [ ] `php artisan config:show app` s'exécute sans erreur
- [ ] `php artisan migrate:status` s'exécute sans erreur
- [ ] Extension `pdo_mysql` visible dans `php -m`

**Rollback**: N/A (installation système)

**Preuve**: Sortie de `php -m` et `php artisan --version`

---

#### ✅ **TÂCHE 2**: Initialiser Git

**Objectif**: Créer dépôt Git pour versioning et rollback

**Commandes**:
```bash
cd /opt/thedrop247
git init
git add .
git commit -m "Initial commit - État avant remédiation RQC"
git branch -M main
```

**Critères d'acceptation**:
- [ ] `git status` fonctionne
- [ ] Commit initial créé
- [ ] `.gitignore` configuré (inclut `.env`, `vendor/`, `node_modules/`)

**Rollback**: N/A (création dépôt)

**Preuve**: Sortie de `git log --oneline`

---

#### ✅ **TÂCHE 3**: Corriger DeliveryMiddleware

**Objectif**: Éviter erreur fatale si `restaurant()` retourne null

**Fichier**: `app/Http/Middleware/DeliveryMiddleware.php`

**Avant**:
```php
public function handle($request, Closure $next)
{
    if (auth()->check() and auth()->user()->restaurant()->first()->services === 'delivery')
        return $next($request);
    
    return redirect()->back();
}
```

**Après**:
```php
public function handle($request, Closure $next)
{
    if (!auth()->check()) {
        return redirect()->route('login')->with('error', 'Veuillez vous connecter.');
    }
    
    $restaurant = auth()->user()->restaurant()->first();
    
    if (!$restaurant) {
        return redirect('/')->with('error', 'Aucun restaurant associé à votre compte.');
    }
    
    if ($restaurant->services === 'delivery') {
        return $next($request);
    }
    
    return redirect()->back()->with('error', 'Accès refusé. Cette page est réservée aux services de livraison.');
}
```

**Critères d'acceptation**:
- [ ] Pas d'erreur si `restaurant()` retourne null
- [ ] Message d'erreur explicite affiché
- [ ] Tests manuels passent (utilisateur sans restaurant, utilisateur avec restaurant non-delivery)

**Rollback**:
```bash
git checkout app/Http/Middleware/DeliveryMiddleware.php
```

**Preuve**: 
- Code modifié
- Test manuel avec utilisateur sans restaurant
- Logs d'erreur vides

---

#### ✅ **TÂCHE 4**: Protéger routes API sensibles

**Objectif**: Ajouter `auth:sanctum` sur endpoints critiques

**Fichier**: `routes/api.php`

**Routes à protéger**:
- `POST /api/place_orders/`
- `POST /api/add_to_cart`
- `POST /api/update_cart_details`
- `DELETE /api/delete_cart_product/{cart}`
- `DELETE /api/delete_previous_cart/{user}`
- `GET /api/show_cart_details/{user}`
- `GET /api/user_profile/{user}`
- `POST /api/update_profile/`
- `POST /api/complete_orders`
- `GET /api/user_pending_orders/{user}`
- `GET /api/user_completed_order_history/{user}`
- `POST /api/add_user_address`
- `GET /api/get_user_address/{user}`
- `POST /api/track_pendings_orders`
- `POST /api/track_completed_orders`
- `POST /api/reviews_and_ratings`
- `POST /api/user_device_token`

**Modification**:
```php
// Avant
Route::post('place_orders/', 'OrderController@getOrders');

// Après
Route::middleware('auth:sanctum')->group(function () {
    Route::post('place_orders/', 'OrderController@getOrders');
    // ... autres routes
});
```

**Critères d'acceptation**:
- [ ] Routes protégées retournent 401 si non authentifié
- [ ] Routes protégées fonctionnent avec token Sanctum
- [ ] Documentation API mise à jour

**Rollback**:
```bash
git checkout routes/api.php
```

**Preuve**:
- Code modifié
- Test avec `curl` (sans token → 401, avec token → 200)
- Logs d'accès

---

#### ✅ **TÂCHE 5**: Vérifier autorisation sur routes avec IDs utilisateurs

**Objectif**: Empêcher accès aux données d'autres utilisateurs

**Fichiers**: Contrôleurs API

**Exemple**: `app/Http/Controllers/api/UserController.php`

**Avant**:
```php
public function profile($user)
{
    $user = User::findOrFail($user);
    return response()->json($user);
}
```

**Après**:
```php
public function profile($user)
{
    $requestedUser = User::findOrFail($user);
    
    // Vérifier que l'utilisateur authentifié accède à son propre profil
    if (auth()->id() != $requestedUser->id) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }
    
    return response()->json($requestedUser);
}
```

**Routes concernées**:
- `GET /api/user_profile/{user}`
- `GET /api/show_cart_details/{user}`
- `GET /api/get_user_address/{user}`
- `GET /api/driver_profile/{driver}` (vérifier si driver appartient à user)

**Critères d'acceptation**:
- [ ] Accès à profil autre utilisateur retourne 403
- [ ] Accès à son propre profil fonctionne
- [ ] Tests unitaires ajoutés

**Rollback**:
```bash
git checkout app/Http/Controllers/api/
```

**Preuve**:
- Code modifié
- Test avec ID autre utilisateur → 403
- Test avec son propre ID → 200

---

### 2.2 P1 - IMPORTANT

#### ✅ **TÂCHE 6**: Migrer guard API vers Sanctum

**Objectif**: Remplacer guard `token` déprécié par `sanctum`

**Fichier**: `config/auth.php`

**Avant**:
```php
'api' => [
    'driver' => 'token',
    'provider' => 'users',
    'hash' => false,
],
```

**Après**:
```php
'api' => [
    'driver' => 'sanctum',
    'provider' => 'users',
],
```

**Critères d'acceptation**:
- [ ] Guard `api` utilise `sanctum`
- [ ] Routes API fonctionnent avec tokens Sanctum
- [ ] Anciens tokens `token` migrés (si nécessaire)

**Rollback**:
```bash
git checkout config/auth.php
```

**Preuve**:
- Configuration modifiée
- Test avec token Sanctum → 200

---

#### ✅ **TÂCHE 7**: Implémenter Policies critiques

**Objectif**: Ajouter contrôle d'accès granulaire

**Policies à créer**:
1. `OrderPolicy` - User peut voir/modifier ses propres commandes
2. `RestaurantPolicy` - Restaurant peut modifier ses propres données
3. `DriverPolicy` - Driver peut voir ses propres livraisons

**Exemple**: `app/Policies/OrderPolicy.php`
```php
<?php

namespace App\Policies;

use App\User;
use App\Order;

class OrderPolicy
{
    public function view(User $user, Order $order)
    {
        return $user->id === $order->user_id;
    }
    
    public function update(User $user, Order $order)
    {
        return $user->id === $order->user_id && $order->status === 'pending';
    }
}
```

**Enregistrer dans**: `app/Providers/AuthServiceProvider.php`
```php
protected $policies = [
    Order::class => OrderPolicy::class,
    Restaurant::class => RestaurantPolicy::class,
    Driver::class => DriverPolicy::class,
];
```

**Utiliser dans contrôleurs**:
```php
$this->authorize('view', $order);
```

**Critères d'acceptation**:
- [ ] 3 Policies créées
- [ ] Policies enregistrées dans AuthServiceProvider
- [ ] Utilisées dans au moins 3 contrôleurs
- [ ] Tests unitaires passent

**Rollback**:
```bash
git checkout app/Policies/
git checkout app/Providers/AuthServiceProvider.php
```

**Preuve**:
- Fichiers créés
- Tests unitaires
- Logs d'accès

---

#### ✅ **TÂCHE 8**: Protéger callback payment

**Objectif**: Sécuriser endpoint de callback payment

**Fichier**: `routes/api.php`

**Option 1**: IP Whitelist (middleware custom)
```php
Route::post('payments/callback/{provider}', 'PaymentCallbackController@handle')
    ->middleware('ip.whitelist')
    ->name('api.payments.callback');
```

**Option 2**: Vérification signature HMAC (dans contrôleur)
```php
public function handle(Request $request, $provider)
{
    // Vérifier signature HMAC du provider
    if (!$this->verifySignature($request, $provider)) {
        return response()->json(['error' => 'Invalid signature'], 403);
    }
    
    // Traiter callback
}
```

**Critères d'acceptation**:
- [ ] Callback protégé (IP whitelist ou signature)
- [ ] Callback fonctionne avec provider valide
- [ ] Callback rejette requêtes non autorisées

**Rollback**:
```bash
git checkout routes/api.php
git checkout app/Http/Controllers/api/PaymentCallbackController.php
```

**Preuve**:
- Code modifié
- Test avec IP autorisée → 200
- Test avec IP non autorisée → 403

---

#### ✅ **TÂCHE 9**: Corriger schéma DB - `orders.driver_id` nullable

**Objectif**: Permettre création de commande sans livreur assigné

**Fichier**: Nouvelle migration

**Commande**:
```bash
php artisan make:migration make_driver_id_nullable_in_orders_table
```

**Migration**:
```php
public function up()
{
    Schema::table('orders', function (Blueprint $table) {
        $table->unsignedBigInteger('driver_id')->nullable()->change();
    });
}
```

**Critères d'acceptation**:
- [ ] Migration créée et exécutée
- [ ] `orders.driver_id` est nullable
- [ ] Commande peut être créée sans `driver_id`
- [ ] Foreign key toujours fonctionnelle

**Rollback**:
```bash
php artisan migrate:rollback --step=1
```

**Preuve**:
- Migration exécutée
- Test création commande sans `driver_id` → succès

---

#### ✅ **TÂCHE 10**: Chiffrer données bancaires

**Objectif**: Chiffrer `restaurants.account_number`

**Fichier**: `app/Restaurant.php`

**Modifier modèle**:
```php
use Illuminate\Database\Eloquent\Casts\Encrypted;

protected $casts = [
    'account_number' => Encrypted::class,
];
```

**Migration** (si données existantes):
```php
// Chiffrer données existantes avant migration
DB::table('restaurants')->get()->each(function ($restaurant) {
    DB::table('restaurants')
        ->where('id', $restaurant->id)
        ->update(['account_number' => encrypt($restaurant->account_number)]);
});
```

**Critères d'acceptation**:
- [ ] `account_number` chiffré dans DB
- [ ] Lecture/décryptage automatique via Eloquent
- [ ] Données existantes migrées
- [ ] Tests passent

**Rollback**:
```bash
# Déchiffrer et restaurer
# (nécessite backup des données)
```

**Preuve**:
- Données chiffrées dans DB (vérification directe)
- Lecture via Eloquent → décryptage automatique

---

### 2.3 P2 - RECOMMANDÉ

#### ✅ **TÂCHE 11**: Ajouter indexes manquants

**Objectif**: Améliorer performances requêtes fréquentes

**Migration**:
```php
public function up()
{
    Schema::table('orders', function (Blueprint $table) {
        $table->index('status');
        $table->index('user_id');
        $table->index('restaurant_id');
        $table->index('ordered_time');
    });
    
    Schema::table('restaurants', function (Blueprint $table) {
        $table->index('approved');
        $table->index('featured');
        $table->index('city');
    });
    
    Schema::table('users', function (Blueprint $table) {
        $table->index('type');
        $table->index('phone');
    });
}
```

**Critères d'acceptation**:
- [ ] Indexes créés
- [ ] Performances requêtes améliorées
- [ ] Pas de régression

**Rollback**:
```bash
php artisan migrate:rollback --step=1
```

---

#### ✅ **TÂCHE 12**: Changer type coordonnées GPS

**Objectif**: `orders.d_lat`/`d_lng` en decimal

**Migration**:
```php
public function up()
{
    Schema::table('orders', function (Blueprint $table) {
        $table->decimal('d_lat', 10, 8)->change();
        $table->decimal('d_lng', 11, 8)->change();
    });
}
```

**Critères d'acceptation**:
- [ ] Type changé en decimal
- [ ] Données existantes converties
- [ ] Pas de perte de données

**Rollback**:
```bash
php artisan migrate:rollback --step=1
```

---

#### ✅ **TÂCHE 13**: Activer SESSION_SECURE_COOKIE en production

**Objectif**: Cookies sécurisés en HTTPS

**Fichier**: `.env`

```env
SESSION_SECURE_COOKIE=true
```

**Critères d'acceptation**:
- [ ] Variable définie en production
- [ ] Cookies sécurisés (flag Secure)
- [ ] Application fonctionne en HTTPS

**Rollback**:
```bash
# Modifier .env
SESSION_SECURE_COOKIE=false
```

---

## 3. ORDRE D'EXÉCUTION RECOMMANDÉ

### Phase 1 - Blocages (Jour 1)
1. Tâche 1: Installer extensions PHP
2. Tâche 2: Initialiser Git
3. Tâche 3: Corriger DeliveryMiddleware

### Phase 2 - Sécurité critique (Jour 2-3)
4. Tâche 4: Protéger routes API
5. Tâche 5: Vérifier autorisation routes avec IDs
6. Tâche 9: Corriger `orders.driver_id`

### Phase 3 - Sécurité importante (Jour 4-5)
7. Tâche 6: Migrer guard API
8. Tâche 7: Implémenter Policies
9. Tâche 8: Protéger callback payment
10. Tâche 10: Chiffrer données bancaires

### Phase 4 - Améliorations (Jour 6+)
11. Tâche 11: Ajouter indexes
12. Tâche 12: Changer type coordonnées
13. Tâche 13: Activer SESSION_SECURE_COOKIE

---

## 4. TESTS DE NON-RÉGRESSION

### 4.1 Tests à exécuter après chaque modification

1. **Authentification**:
   - Login utilisateur
   - Login restaurant
   - Login admin
   - Logout

2. **Routes API**:
   - Création commande (avec/sans auth)
   - Ajout au panier (avec/sans auth)
   - Profil utilisateur (avec/sans auth)

3. **Middlewares**:
   - Accès admin (utilisateur admin/non-admin)
   - Accès restaurant (utilisateur restaurant/non-restaurant)
   - Accès delivery (utilisateur avec/sans restaurant delivery)

4. **Base de données**:
   - Création commande sans `driver_id`
   - Lecture `account_number` (décryptage)

### 4.2 Commandes de test

```bash
# Tests unitaires (si existants)
php artisan test

# Tests manuels
curl -X POST http://localhost/api/place_orders/ -H "Authorization: Bearer TOKEN"
curl -X GET http://localhost/api/user_profile/1
```

---

## 5. CRITÈRES DE VALIDATION FINALE

Le projet sera considéré "VALIDÉ" uniquement si :

### Blocages (P0)
- [x] Toutes les extensions PHP requises sont installées
- [x] Git est initialisé
- [x] DeliveryMiddleware gère les cas null
- [x] Routes API sensibles sont protégées
- [x] Autorisation vérifiée sur routes avec IDs

### Sécurité (P1)
- [ ] Guard API migré vers `sanctum`
- [ ] Au moins 3 Policies implémentées
- [ ] Callback payment protégé
- [ ] `orders.driver_id` nullable
- [ ] Données bancaires chiffrées

### Améliorations (P2)
- [ ] Indexes critiques ajoutés
- [ ] Coordonnées GPS en decimal
- [ ] `SESSION_SECURE_COOKIE` activé

### Tests
- [ ] Tests de non-régression passent
- [ ] Tests manuels validés
- [ ] Aucune régression détectée

---

## 6. STATUT

**STATUT**: ⚠️ **EN COURS**

**Progression**:
- Phase 1: 0/3 (0%)
- Phase 2: 0/3 (0%)
- Phase 3: 0/5 (0%)
- Phase 4: 0/3 (0%)

**Prochaine étape**: Exécuter Phase 1 (Tâches 1-3)

