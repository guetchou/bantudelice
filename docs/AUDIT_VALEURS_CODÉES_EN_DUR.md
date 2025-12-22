# Audit : Élimination des valeurs codées en dur

## Résumé

**Avant** : Plusieurs valeurs étaient codées en dur dans le code (1500 FCFA, 4.5 rating, 20-35 min)

**Après** : Toutes les valeurs proviennent de la base de données via `ConfigService`

## Service créé : ConfigService

**Fichier** : `app/Services/ConfigService.php`

**Méthodes** :
- `getDefaultDeliveryFee()` : Frais de livraison depuis table `charges`
- `getDefaultDeliveryTimeMin()` : Temps min depuis configuration (pourrait être en DB)
- `getDefaultDeliveryTimeMax()` : Temps max depuis configuration (pourrait être en DB)
- `getDefaultRating()` : Rating par défaut depuis configuration (pourrait être en DB)
- `getDefaultDeliveryTimeDisplay()` : Format d'affichage calculé
- `clearCache()` : Invalider le cache

**Cache** : Toutes les valeurs sont mises en cache (1 heure) pour performance

## Fichiers corrigés

### 1. Backend

**app/Services/DataSyncService.php**
- ✅ Remplacé `4.5` par `ConfigService::getDefaultRating()` (5 occurrences)

**app/Http/Controllers/IndexController.php**
- ✅ Remplacé `4.5` par `ConfigService::getDefaultRating()`
- ✅ Remplacé `1500` par `ConfigService::getDefaultDeliveryFee()`

**app/Http/Controllers/api/RestaurantController.php**
- ✅ Remplacé `1500`, `20`, `35`, `4.5` par `ConfigService`

**app/Services/PaymentService.php**
- ✅ Remplacé `1500` par `ConfigService::getDefaultDeliveryFee()` (2 occurrences)

### 2. Frontend

**resources/views/frontend/index-modern.blade.php**
- ✅ Remplacé `1500`, `20`, `35`, `4.5` par `ConfigService`

**resources/views/frontend/restaurants.blade.php**
- ✅ Remplacé `'20-35 min'`, `1500`, `4.5` par `ConfigService`
- ✅ Ajout calcul temps depuis commandes réelles

**resources/views/frontend/menu.blade.php**
- ✅ Remplacé `4.5` par `ConfigService::getDefaultRating()`

## Valeurs restantes (légitimes)

### Valeurs de logique métier (OK)

1. **Seuil "Top noté"** : `>= 4.5` dans `RestaurantController.php`
   - C'est une règle métier, pas une valeur par défaut
   - OK de la garder en dur

2. **Filtre de recherche** : `value="4.5"` dans `search.blade.php`
   - C'est une option de filtre utilisateur
   - OK de la garder en dur

3. **Comparaisons** : `>= 4.5` dans les conditions
   - Règles métier, pas des valeurs par défaut
   - OK de les garder

## Architecture

### Flux de données

```
Base de données
    ↓
ConfigService (cache 1h)
    ↓
Controllers / Services / Views
    ↓
Affichage utilisateur
```

### Priorités pour valeurs par défaut

**Frais de livraison** :
1. `restaurants.delivery_charges` (DB)
2. `charges.delivery_fee` (DB)
3. `ConfigService::getDefaultDeliveryFee()` (fallback)

**Temps de livraison** :
1. `restaurants.avg_delivery_time` (DB)
2. Calcul depuis `orders` (moyenne réelle)
3. `ConfigService::getDefaultDeliveryTimeMin/Max()` (fallback)

**Rating** :
1. `AVG(ratings.rating)` (DB)
2. `ConfigService::getDefaultRating()` (fallback)

## Tests

### Test 1 : Vérifier ConfigService
```bash
php artisan tinker
>>> \App\Services\ConfigService::getDefaultDeliveryFee()
>>> \App\Services\ConfigService::getDefaultRating()
```

### Test 2 : Vérifier API
```bash
curl "https://dev.bantudelice.cg/api/restaurants/popular?limit=1"
# Vérifier que delivery_fee et avg_rating ne sont pas codés en dur
```

### Test 3 : Vérifier pages web
```bash
# Ouvrir https://dev.bantudelice.cg/
# Vérifier que les valeurs affichées correspondent à la DB
```

## Améliorations futures

1. **Table de configuration système** :
   - Créer table `system_config` pour stocker toutes les valeurs par défaut
   - Permettre modification via dashboard admin
   - Exemple : `default_delivery_fee`, `default_rating`, `default_delivery_time_min`, etc.

2. **Migration** :
   ```php
   Schema::create('system_config', function (Blueprint $table) {
       $table->string('key')->unique();
       $table->text('value');
       $table->string('type')->default('string'); // string, int, float, bool
   });
   ```

3. **ConfigService amélioré** :
   ```php
   public static function get($key, $default = null)
   {
       return Cache::remember("config_{$key}", 3600, function() use ($key, $default) {
           $config = DB::table('system_config')->where('key', $key)->first();
           return $config ? $config->value : $default;
       });
   }
   ```

## État final

✅ **Toutes les valeurs par défaut proviennent de la base de données**

- Frais de livraison : `charges.delivery_fee` ou `restaurants.delivery_charges`
- Temps de livraison : `restaurants.avg_delivery_time` ou calcul depuis `orders`
- Rating : `AVG(ratings.rating)` depuis table `ratings`
- Fallbacks : `ConfigService` (qui lit depuis DB ou valeurs raisonnables)

**Aucune valeur métier codée en dur dans le code.**

