# ✅ Implémentation : Dispatch Automatique Intelligent

**Date :** 2025-12-17  
**Sprint :** 1/6 - Devenir Leader  
**Statut :** ✅ TERMINÉ

---

## 📋 Fichiers Créés/Modifiés

### Nouveaux Fichiers

1. **`app/Services/DispatchService.php`** (NOUVEAU)
   - Service de dispatch automatique intelligent
   - Algorithme de scoring basé sur :
     - Distance restaurant → livreur → client (40%)
     - Charge de travail livreur (30%)
     - Historique performance (20%)
     - Disponibilité immédiate (10%)
     - Bonus si livreur du restaurant (10%)

2. **`app/Jobs/AutoAssignDeliveryJob.php`** (NOUVEAU)
   - Job Laravel pour assignation asynchrone
   - Retry automatique (3 tentatives avec backoff: 30s, 60s, 120s)

3. **`app/Console/Commands/ProcessPendingDeliveries.php`** (NOUVEAU)
   - Commande Artisan pour traiter manuellement les livraisons en attente
   - Usage: `php artisan dispatch:process-pending [--limit=10]`

### Fichiers Modifiés

1. **`app/Http/Controllers/IndexController.php`** (MODIFIÉ)
   - Ligne ~1014 : Après création de livraison, déclenche `AutoAssignDeliveryJob::dispatch($delivery)`
   - Le dispatch se fait automatiquement à chaque nouvelle commande

2. **`app/Console/Kernel.php`** (MODIFIÉ)
   - Scheduler ajouté : exécute `DispatchService::processPendingDeliveries()` toutes les 2 minutes
   - Traite jusqu'à 20 livraisons en attente par exécution

---

## 🔄 Workflow Complet

### 1. Client passe commande

```
POST /orders (IndexController@getOrders)
↓
Commande créée dans `orders`
↓
Livraison créée dans `deliveries` (status = PENDING)
↓
AutoAssignDeliveryJob::dispatch($delivery) déclenché
```

### 2. Dispatch automatique (immédiat ou via scheduler)

```
AutoAssignDeliveryJob exécuté
↓
DispatchService::findBestDriver($delivery)
↓
Calcul score pour chaque livreur disponible
↓
Meilleur livreur sélectionné
↓
DeliveryService::assignDriver($delivery, $driver)
↓
Livraison status = ASSIGNED
Order status = assign
Driver is_available = false
```

### 3. Scheduler (backup)

```
Toutes les 2 minutes
↓
DispatchService::processPendingDeliveries(20)
↓
Traite les livraisons PENDING restantes
```

---

## 🧪 Tests Manuels

### Test 1 : Créer une commande → Vérifier assignation auto

1. **Prérequis :**
   - Au moins 1 livreur avec `status = 'online'` et coordonnées GPS
   - Au moins 1 restaurant avec coordonnées GPS
   - Utilisateur connecté avec panier rempli

2. **Étapes :**
   ```bash
   # 1. Vérifier qu'il y a des livreurs disponibles
   mysql -u root -p thedrop247 -e "SELECT id, name, status, latitude, longitude FROM drivers WHERE status = 'online' LIMIT 5;"
   
   # 2. Passer une commande via l'interface web
   # Aller sur https://bantudelice.cg/cart
   # Remplir l'adresse de livraison
   # Valider la commande
   
   # 3. Vérifier que la livraison a été créée et assignée
   mysql -u root -p thedrop247 -e "SELECT d.id, d.status, d.driver_id, d.assigned_at, dr.name as driver_name FROM deliveries d LEFT JOIN drivers dr ON d.driver_id = dr.id ORDER BY d.id DESC LIMIT 5;"
   ```

3. **Résultat attendu :**
   - Livraison créée avec `status = 'ASSIGNED'` (ou `PENDING` si aucun livreur disponible)
   - `driver_id` rempli si assignation réussie
   - `assigned_at` timestamp présent

### Test 2 : Commande Artisan (traitement manuel)

```bash
# Traiter 10 livraisons en attente
php artisan dispatch:process-pending --limit=10
```

**Résultat attendu :**
```
✅ Traitées: 10
✅ Assignées: 8
❌ Échecs: 2
```

### Test 3 : Scheduler (traitement automatique)

```bash
# Vérifier que le scheduler est configuré
php artisan schedule:list

# Exécuter le scheduler manuellement (pour test)
php artisan schedule:run
```

**Résultat attendu :**
- Logs dans `storage/logs/laravel.log` :
  ```
  Dispatch automatique exécuté {"processed":5,"assigned":4,"failed":1}
  ```

---

## 📊 Algorithme de Scoring

Le score d'un livreur est calculé sur 100 points :

| Critère | Poids | Détails |
|---------|-------|---------|
| **Distance restaurant → livreur** | 40% | 0-5km = 0 pénalité<br>5-10km = -10<br>10-20km = -20<br>20+km = -40 |
| **Charge de travail** | 30% | 0 livraison = +10<br>1 = 0<br>2 = -15<br>3+ = -30 |
| **Historique performance** | 20% | Basé sur taux livraison à temps + temps moyen |
| **Disponibilité** | 10% | Non disponible = -50 |
| **Bonus restaurant** | 10% | Si livreur du restaurant = +10 |

**Exemple :**
- Livreur A : Distance 3km, 0 livraison active, performance 80%, disponible, livreur du restaurant
  - Score = 100 - 0 + 10 + 12 + 0 + 10 = **132** (clampé à 100) = **100**

- Livreur B : Distance 15km, 2 livraisons actives, performance 60%, disponible
  - Score = 100 - 20 - 15 + 4 + 0 + 0 = **69**

→ **Livreur A est sélectionné**

---

## ⚙️ Configuration

### Queue Driver

Par défaut, Laravel utilise `QUEUE_CONNECTION=sync` (traitement immédiat).

Pour activer le traitement asynchrone :

1. **Activer la queue database :**
   ```bash
   # .env
   QUEUE_CONNECTION=database
   ```

2. **Créer la table jobs (si pas déjà fait) :**
   ```bash
   php artisan queue:table
   php artisan migrate
   ```

3. **Lancer un worker :**
   ```bash
   php artisan queue:work
   ```

### Scheduler (Cron)

Pour que le scheduler s'exécute automatiquement, ajouter dans crontab :

```bash
* * * * * cd /opt/bantudelice242 && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🐛 Dépannage

### Problème : Aucun livreur assigné

**Causes possibles :**
1. Aucun livreur avec `status = 'online'`
2. Tous les livreurs ont déjà 3+ livraisons actives
3. Coordonnées GPS manquantes (restaurant ou livreur)

**Solution :**
```bash
# Vérifier les livreurs disponibles
mysql -u root -p thedrop247 -e "SELECT id, name, status, latitude, longitude, (SELECT COUNT(*) FROM deliveries WHERE driver_id = drivers.id AND status IN ('ASSIGNED', 'PICKED_UP', 'ON_THE_WAY')) as active_deliveries FROM drivers WHERE status = 'online';"

# Vérifier les coordonnées restaurant
mysql -u root -p thedrop247 -e "SELECT id, name, latitude, longitude FROM restaurants WHERE latitude IS NULL OR longitude IS NULL LIMIT 10;"
```

### Problème : Job ne s'exécute pas

**Vérifier :**
```bash
# Si queue = 'sync', le job s'exécute immédiatement
# Si queue = 'database', vérifier qu'un worker tourne
ps aux | grep "queue:work"

# Si pas de worker, lancer :
php artisan queue:work
```

---

## ✅ Checklist de Validation

- [x] Service `DispatchService` créé avec algorithme de scoring
- [x] Job `AutoAssignDeliveryJob` créé avec retry logic
- [x] Intégration dans workflow commande (`IndexController`)
- [x] Scheduler configuré (toutes les 2 minutes)
- [x] Commande Artisan créée (`dispatch:process-pending`)
- [ ] **Test manuel :** Créer commande → Vérifier assignation auto
- [ ] **Test manuel :** Exécuter commande Artisan
- [ ] **Test manuel :** Vérifier scheduler (logs)

---

## 🎯 Prochaine Étape

**Sprint 2 : Tracking Temps Réel**
- Table `driver_locations` (géolocalisation)
- API mise à jour position livreur
- Frontend tracking (carte interactive)

---

## 📝 Notes Techniques

- **Performance :** L'algorithme calcule la distance avec la formule Haversine (précision ~1%)
- **Scalabilité :** Le scheduler limite à 20 livraisons par exécution pour éviter la surcharge
- **Retry :** Les jobs échoués sont retentés 3 fois avec backoff exponentiel (30s, 60s, 120s)
- **Logs :** Toutes les actions sont loggées dans `storage/logs/laravel.log`

