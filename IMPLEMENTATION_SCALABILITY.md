# ✅ Implémentation : Scalabilité (Sprint 5)

**Date :** 2025-12-17  
**Sprint :** 5/6 - Devenir Leader  
**Statut :** ✅ TERMINÉ

---

## 📋 Fichiers Créés/Modifiés

### Nouveaux Fichiers

1. **`app/Jobs/ProcessOrderJob.php`** (NOUVEAU)
   - Job pour traiter la création de commande de manière asynchrone
   - Découpe en étapes : création orders → livraisons → dispatch → fidélité → notifications

2. **`app/Jobs/SendOrderNotificationsJob.php`** (NOUVEAU)
   - Job pour envoyer les notifications (client + restaurant)
   - Séparé de ProcessOrderJob pour ne pas bloquer

3. **`app/Services/CacheOptimizationService.php`** (NOUVEAU)
   - Service d'optimisation du cache
   - Invalidation groupée, TTL adaptatifs, préchargement

4. **`app/Console/Commands/OptimizeDatabase.php`** (NOUVEAU)
   - Commande Artisan pour optimiser les tables DB
   - Usage: `php artisan db:optimize [--analyze]`

5. **`database/migrations/2025_12_17_195011_add_indexes_for_performance.php`** (NOUVEAU)
   - Migration pour ajouter des index de performance
   - Index composites pour requêtes fréquentes

6. **`CONFIGURATION_WORKERS.md`** (NOUVEAU)
   - Guide de configuration des workers Laravel
   - Supervisor et systemd

### Fichiers Modifiés

**Aucun fichier modifié** (les jobs sont prêts à être utilisés, mais l'intégration dans `IndexController@getOrders` est optionnelle pour ne pas casser le workflow existant)

---

## 🔄 Workflow Complet

### 1. Création Commande (Optionnel : avec ProcessOrderJob)

```
Client passe commande
  ↓
IndexController@getOrders()
  ↓
Option A (actuel) : Traitement synchrone
  - Création orders
  - Création livraisons
  - Dispatch auto
  - Notifications
  ↓
Réponse immédiate

Option B (recommandé pour production) : Traitement asynchrone
  - Préparer données commande
  - ProcessOrderJob::dispatch()
  ↓
Réponse immédiate (commande en cours de traitement)
  ↓
ProcessOrderJob exécuté en arrière-plan :
  1. Création orders
  2. Création livraisons
  3. Dispatch auto
  4. Points fidélité
  5. SendOrderNotificationsJob::dispatch()
```

### 2. Optimisation Cache

```
Requête données (restaurants, produits)
  ↓
CacheOptimizationService::remember()
  ↓
TTL adaptatif :
  - Temps réel : 60s
  - Catalogue : 300s (5 min)
  - Autre : 3600s (1h)
  ↓
Cache hit → Retour immédiat
Cache miss → Calcul + mise en cache
```

### 3. Optimisation DB

```
Requête avec index
  ↓
MySQL utilise index composite
  ↓
Requête optimisée (ex: orders par restaurant + status)
```

---

## 🧪 Tests Manuels

### Test 1 : Vérifier les index DB

```bash
# Exécuter la migration
php artisan migrate --path=database/migrations/2025_12_17_195011_add_indexes_for_performance.php

# Vérifier les index créés
mysql -u root -p thedrop247 -e "SHOW INDEX FROM orders;" | grep -E "orders_restaurant_status|orders_order_no"
```

**Résultat attendu :**
```
orders_restaurant_status_index
orders_order_no_index
orders_user_created_index
orders_status_created_index
```

### Test 2 : Optimiser les tables

```bash
# Analyser les tables
php artisan db:optimize --analyze

# Optimiser les tables
php artisan db:optimize
```

**Résultat attendu :**
```
✓ orders
✓ restaurants
✓ products
...
Optimisation terminée.
```

### Test 3 : Tester les jobs (si queue activée)

```bash
# Activer la queue
# .env: QUEUE_CONNECTION=database

# Lancer un worker
php artisan queue:work database --once

# Vérifier les jobs en attente
mysql -u root -p thedrop247 -e "SELECT COUNT(*) FROM jobs WHERE queue = 'default';"
```

### Test 4 : Précharger le cache

```bash
php artisan tinker
>>> \App\Services\CacheOptimizationService::preloadFrequentData();
```

---

## 📊 Index Ajoutés

### Table `orders`
- `orders_restaurant_status_index` : `(restaurant_id, status)`
- `orders_order_no_index` : `order_no`
- `orders_user_created_index` : `(user_id, created_at)`
- `orders_status_created_index` : `(status, created_at)`

### Table `deliveries`
- `deliveries_status_index` : `status`
- `deliveries_driver_status_index` : `(driver_id, status)`

### Table `payments`
- `payments_status_index` : `status`
- `payments_provider_ref_index` : `(provider, provider_reference)`
- `payments_user_created_index` : `(user_id, created_at)`

### Table `products`
- `products_restaurant_available_index` : `(restaurant_id, is_available)`
- `products_featured_index` : `featured`

### Table `categories`
- `categories_restaurant_available_index` : `(restaurant_id, is_available)`

### Table `carts`
- `carts_user_index` : `user_id`

---

## ⚙️ Configuration

### Queue Driver

**Option 1 : Database (Recommandé pour début)**
```env
QUEUE_CONNECTION=database
```

**Option 2 : Redis (Recommandé pour production)**
```env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### Workers

**Supervisor (Recommandé) :**
- Voir `CONFIGURATION_WORKERS.md` pour la configuration complète
- 2 workers par défaut (ajuster selon charge)

**Commandes :**
```bash
# Démarrer workers
sudo supervisorctl start bantudelice-worker:*

# Arrêter workers
sudo supervisorctl stop bantudelice-worker:*

# Redémarrer workers
sudo supervisorctl restart bantudelice-worker:*
```

### Cache

**Driver recommandé : Redis**
```env
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

**Ou File (par défaut)**
```env
CACHE_DRIVER=file
```

---

## 🐛 Dépannage

### Problème : Index non créés

**Vérifier :**
```bash
# Vérifier si la migration a été exécutée
php artisan migrate:status | grep add_indexes

# Vérifier les index dans MySQL
mysql -u root -p thedrop247 -e "SHOW INDEX FROM orders;"
```

### Problème : Jobs ne sont pas traités

**Vérifier :**
1. Queue connection : `php artisan tinker --execute="echo config('queue.default');"`
2. Workers actifs : `sudo supervisorctl status`
3. Jobs en attente : `php artisan queue:monitor database:default`

### Problème : Cache ne fonctionne pas

**Vérifier :**
```bash
# Vérifier le driver cache
php artisan tinker --execute="echo config('cache.default');"

# Nettoyer le cache
php artisan cache:clear

# Tester le cache
php artisan tinker
>>> Cache::put('test', 'value', 60);
>>> Cache::get('test');
```

---

## ✅ Checklist de Validation

- [x] Job `ProcessOrderJob` créé
- [x] Job `SendOrderNotificationsJob` créé
- [x] Service `CacheOptimizationService` créé
- [x] Migration index créée
- [x] Commande `db:optimize` créée
- [x] Guide configuration workers créé
- [ ] **Test manuel :** Exécuter migration index → Vérifier index créés
- [ ] **Test manuel :** Optimiser tables → Vérifier performance
- [ ] **Test manuel :** Activer queue → Vérifier workers traitent jobs
- [ ] **Test manuel :** Précharger cache → Vérifier données en cache

---

## 🎯 Prochaine Étape

**Sprint 6 : Différenciants (Optionnel)**
- Multi-services réels
- Fidélité avancée
- Support client

**OU**

**Validation finale :**
- Tests end-to-end complets
- Documentation utilisateur
- Guide de déploiement

---

## 📝 Notes Techniques

- **Performance :** Index composites pour requêtes fréquentes (restaurant + status, user + created_at)
- **Scalabilité :** Jobs asynchrones pour ne pas bloquer les requêtes HTTP
- **Cache :** TTL adaptatifs selon le type de donnée (temps réel vs catalogue)
- **Robustesse :** Retry automatique pour les jobs (3 tentatives avec backoff)

---

## 🚀 Intégration ProcessOrderJob (Optionnel)

Pour activer le traitement asynchrone des commandes, modifier `IndexController@getOrders` :

```php
// Au lieu de créer les orders directement, dispatcher le job
$cartItemsArray = $cartItems->map(function($item) {
    return [
        'restaurant_id' => $item->restaurant_id,
        'product_id' => $item->product_id,
        'qty' => $item->qty,
        'price' => $product->discount_price > 0 ? $product->discount_price : $product->price,
    ];
})->toArray();

$orderData = [
    'sub_total' => $subTotal,
    'total' => $total,
    'tax' => $tax,
    'delivery_fee' => $charges->delivery_fee,
    'discount' => $discount + $loyaltyDiscount,
    'driver_tip' => $driverTip,
    'delivery_address' => $request->delivery_address,
    'd_lat' => $request->d_lat ?? '-4.2767',
    'd_lng' => $request->d_lng ?? '15.2832',
    'payment_method' => $request->payment_method,
    'admin_commission' => 2,
    'restaurant_commission' => 4,
];

ProcessOrderJob::dispatch($userId, $orderNo, $cartItemsArray, $orderData);
```

**Note :** Cette modification est optionnelle. Le système actuel fonctionne en mode synchrone. Pour activer l'asynchrone, il faut aussi gérer l'affichage "commande en cours de traitement" côté frontend.

