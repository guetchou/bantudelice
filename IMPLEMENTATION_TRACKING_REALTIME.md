# ✅ Implémentation : Tracking Temps Réel (Sprint 2)

**Date :** 2025-12-17  
**Sprint :** 2/6 - Devenir Leader  
**Statut :** ✅ TERMINÉ

---

## 📋 Fichiers Créés/Modifiés

### Nouveaux Fichiers

1. **`database/migrations/2025_12_17_190827_create_driver_locations_table.php`** (NOUVEAU)
   - Table pour l'historique des positions GPS des livreurs
   - Colonnes : `driver_id`, `latitude`, `longitude`, `accuracy`, `heading`, `speed`, `timestamp`
   - Index sur `(driver_id, timestamp)` pour requêtes rapides

2. **`app/DriverLocation.php`** (NOUVEAU)
   - Modèle Eloquent pour `driver_locations`
   - Scopes : `latestForDriver()`, `recent()`
   - Relation avec `Driver`

### Fichiers Modifiés

1. **`app/Driver.php`** (MODIFIÉ)
   - Ajout relation `locations()`
   - Ajout méthode `getLatestLocation()`

2. **`app/Http/Controllers/api/DriverController.php`** (MODIFIÉ)
   - Méthode `updateLocation()` améliorée :
     - Enregistre dans `driver_locations` (historique)
     - Conserve rétrocompatibilité avec `DriverHistory` si existe
     - Support métadonnées GPS (accuracy, heading, speed)

3. **`app/Http/Controllers/api/OrderTrackingController.php`** (MODIFIÉ)
   - Méthode `show()` améliorée :
     - Retourne position GPS du livreur depuis `driver_locations` (dernière position)
     - Fallback sur `drivers.latitude/longitude` si pas d'historique
     - Retourne positions restaurant et livraison (client)

4. **`app/Http/Controllers/IndexController.php`** (MODIFIÉ)
   - Méthode `getOrderStatus()` améliorée :
     - Retourne position GPS du livreur avec métadonnées
     - Structure JSON améliorée avec `location` objects

---

## 🔄 Workflow Complet

### 1. Livreur met à jour sa position

```
App mobile livreur (GPS)
  ↓
POST /api/driver/{driverId}/location
Body: {
  "latitude": -4.2767,
  "longitude": 15.2832,
  "accuracy": 10.5,  // optionnel (mètres)
  "heading": 180,     // optionnel (degrés)
  "speed": 45.2       // optionnel (km/h)
}
  ↓
Backend :
  - Met à jour drivers.latitude/longitude
  - Enregistre dans driver_locations (historique)
  - Met à jour drivers.status = 'online'
```

### 2. Client suit sa commande (temps réel)

```
Client ouvre page tracking
  ↓
JavaScript : Polling toutes les 10 secondes
  ↓
GET /api/order/{orderNo}/status
  OU
GET /api/orders/{orderId}/tracking (avec auth)
  ↓
Réponse JSON avec :
  - driver.location (latitude, longitude, accuracy, heading, speed, timestamp)
  - restaurant.location
  - delivery_location
  ↓
Frontend met à jour carte Google Maps
  - Marqueur livreur (position temps réel)
  - Marqueur restaurant
  - Marqueur livraison (client)
  - Ligne de route (optionnel)
```

---

## 🧪 Tests Manuels

### Test 1 : Mettre à jour position livreur

```bash
# Remplacer {driverId} par un ID réel
curl -X POST "https://bantudelice.cg/api/driver/1/location" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "latitude": -4.2767,
    "longitude": 15.2832,
    "accuracy": 10.5,
    "heading": 180,
    "speed": 45.2
  }'
```

**Réponse attendue :**
```json
{
  "status": true,
  "message": "Position mise à jour avec succès",
  "driver": {
    "id": 1,
    "name": "Jean Dupont",
    "latitude": -4.2767,
    "longitude": 15.2832,
    "status": "online"
  }
}
```

**Vérification DB :**
```sql
-- Vérifier que la position est enregistrée dans driver_locations
SELECT * FROM driver_locations WHERE driver_id = 1 ORDER BY timestamp DESC LIMIT 5;

-- Vérifier que drivers.latitude/longitude est mis à jour
SELECT id, name, latitude, longitude, status FROM drivers WHERE id = 1;
```

### Test 2 : Récupérer position livreur via tracking

```bash
# Remplacer {orderNo} par un numéro de commande réel
curl -X GET "https://bantudelice.cg/api/order/TD-20251217-1234/status" \
  -H "Accept: application/json"
```

**Réponse attendue :**
```json
{
  "status": true,
  "order": {
    "order_no": "TD-20251217-1234",
    "status": "assign",
    "progress": 50,
    "driver": {
      "id": 1,
      "name": "Jean Dupont",
      "phone": "+242061234567",
      "vehicle": "Moto",
      "location": {
        "latitude": -4.2767,
        "longitude": 15.2832,
        "accuracy": 10.5,
        "heading": 180,
        "speed": 45.2,
        "timestamp": "2025-12-17T19:30:00+00:00"
      }
    },
    "restaurant": {
      "id": 1,
      "name": "Restaurant Test",
      "address": "123 Rue de la Paix",
      "location": {
        "latitude": -4.2800,
        "longitude": 15.2900
      }
    },
    "delivery_location": {
      "latitude": -4.2700,
      "longitude": 15.2800
    }
  }
}
```

### Test 3 : Tracking avec authentification

```bash
# Récupérer un token (si auth:sanctum)
# Puis :
curl -X GET "https://bantudelice.cg/api/orders/123/tracking" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

---

## 📊 Structure de Données

### Table `driver_locations`

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | bigint | PK |
| `driver_id` | bigint | FK vers `drivers` |
| `latitude` | decimal(10,8) | Latitude GPS |
| `longitude` | decimal(11,8) | Longitude GPS |
| `accuracy` | decimal(8,2) | Précision GPS en mètres (nullable) |
| `heading` | decimal(5,2) | Direction en degrés 0-360 (nullable) |
| `speed` | decimal(6,2) | Vitesse en km/h (nullable) |
| `timestamp` | timestamp | Timestamp de la position |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Index :**
- `(driver_id, timestamp)` pour requêtes rapides
- `timestamp` pour requêtes récentes

---

## 🎨 Frontend (À Implémenter)

### Exemple JavaScript (Polling)

```javascript
// Polling toutes les 10 secondes
let trackingInterval;

function startTracking(orderNo) {
    trackingInterval = setInterval(() => {
        fetch(`/api/order/${orderNo}/status`)
            .then(res => res.json())
            .then(data => {
                if (data.status && data.order.driver?.location) {
                    updateDriverMarker(data.order.driver.location);
                    updateRoute(data.order.restaurant.location, 
                               data.order.delivery_location,
                               data.order.driver.location);
                }
            })
            .catch(err => console.error('Erreur tracking:', err));
    }, 10000); // 10 secondes
}

function stopTracking() {
    if (trackingInterval) {
        clearInterval(trackingInterval);
    }
}

function updateDriverMarker(location) {
    // Mettre à jour le marqueur livreur sur Google Maps
    if (window.driverMarker) {
        window.driverMarker.setPosition({
            lat: location.latitude,
            lng: location.longitude
        });
        
        // Rotation si heading disponible
        if (location.heading) {
            window.driverMarker.setIcon({
                path: google.maps.SymbolPath.FORWARD_CLOSED_ARROW,
                rotation: location.heading,
                scale: 4
            });
        }
    }
}
```

---

## ⚙️ Configuration

### Fréquence de mise à jour recommandée

- **App livreur :** Toutes les 10-30 secondes (selon vitesse)
- **Frontend client :** Polling toutes les 10 secondes
- **Nettoyage historique :** Supprimer positions > 7 jours (job cron)

### Job de nettoyage (optionnel)

```php
// app/Console/Commands/CleanupDriverLocations.php
public function handle()
{
    \App\DriverLocation::where('timestamp', '<', now()->subDays(7))->delete();
    $this->info('Positions GPS anciennes supprimées');
}
```

Ajouter dans `Kernel.php` :
```php
$schedule->command('cleanup:driver-locations')->daily();
```

---

## 🐛 Dépannage

### Problème : Position livreur non mise à jour

**Vérifier :**
```sql
-- Vérifier que la position est enregistrée
SELECT * FROM driver_locations WHERE driver_id = {id} ORDER BY timestamp DESC LIMIT 1;

-- Vérifier que drivers.latitude/longitude est à jour
SELECT latitude, longitude, updated_at FROM drivers WHERE id = {id};
```

### Problème : Position non retournée dans API

**Vérifier :**
1. Livreur assigné à la livraison
2. Position enregistrée dans `driver_locations` ou `drivers`
3. Logs : `tail -f storage/logs/laravel.log | grep "driver.*location"`

---

## ✅ Checklist de Validation

- [x] Migration `driver_locations` créée
- [x] Modèle `DriverLocation` créé
- [x] Endpoint `POST /api/driver/{driverId}/location` amélioré
- [x] Endpoint `GET /api/order/{orderNo}/status` retourne position GPS
- [x] Endpoint `GET /api/orders/{orderId}/tracking` retourne position GPS
- [ ] **Test manuel :** Mettre à jour position livreur → Vérifier DB
- [ ] **Test manuel :** Récupérer position via API → Vérifier JSON
- [ ] **Test manuel :** Frontend polling → Vérifier mise à jour carte

---

## 🎯 Prochaine Étape

**Sprint 3 : Paiements Robustes**
- Réconciliation automatique
- Retry callbacks
- Anti-fraude basique

---

## 📝 Notes Techniques

- **Performance :** Index sur `(driver_id, timestamp)` pour requêtes rapides
- **Scalabilité :** Nettoyage automatique positions > 7 jours (optionnel)
- **Précision :** Support métadonnées GPS (accuracy, heading, speed) pour UX avancée
- **Rétrocompatibilité :** Fallback sur `drivers.latitude/longitude` si pas d'historique

