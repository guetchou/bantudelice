# Feature Complète : Suivi en Temps Réel des Commandes

## 📋 Résumé des Fichiers Modifiés/Créés

### Backend
1. **Migration** : `database/migrations/2024_12_06_100000_add_location_to_drivers_table.php`
   - Ajoute `latitude`, `longitude`, `status` à la table `drivers`

2. **Modèle** : `app/Driver.php`
   - Ajout de `latitude`, `longitude`, `status` dans `$fillable`

3. **Contrôleur API** : `app/Http/Controllers/IndexController.php`
   - Méthode `getOrderStatus($orderNo)` : Récupère le statut d'une commande en temps réel

4. **Contrôleur API** : `app/Http/Controllers/api/DriverController.php`
   - Méthode `updateLocation(Request $request, $driverId)` : Met à jour la position GPS du livreur

5. **Routes API** : `routes/api.php`
   - `GET /api/order/{orderNo}/status` : Récupérer le statut d'une commande
   - `POST /api/driver/{driverId}/location` : Mettre à jour la position du livreur

### Frontend
6. **Vue Client** : `resources/views/frontend/track_order.blade.php`
   - Rafraîchissement automatique AJAX toutes les 10 secondes
   - Mise à jour dynamique de la timeline, de la carte et de la position du livreur

7. **Vue Restaurant** : `resources/views/restaurant/order/show_orders.blade.php`
   - Rafraîchissement automatique AJAX toutes les 10 secondes
   - Mise à jour dynamique de la position du livreur sur la carte

---

## 🔄 Workflow Complet

### 1. Client : Suivi de Commande
```
Client ouvre /track-order/{orderNo}
    ↓
Page charge avec statut initial
    ↓
JavaScript démarre auto-refresh (toutes les 10s)
    ↓
AJAX GET /api/order/{orderNo}/status
    ↓
Mise à jour dynamique :
  - Timeline (progression)
  - Position du livreur sur la carte
  - Temps restant estimé
  - Notification si statut change
```

### 2. Restaurant : Suivi de Commande Assignée
```
Restaurant ouvre /restaurant/show_order/{orderNo}
    ↓
Page charge avec statut initial
    ↓
JavaScript démarre auto-refresh (toutes les 10s)
    ↓
AJAX GET /api/order/{orderNo}/status
    ↓
Mise à jour dynamique :
  - Position du livreur sur la carte
  - Statut de la commande
```

### 3. Livreur : Mise à Jour de Position
```
Livreur (app mobile) envoie position GPS
    ↓
POST /api/driver/{driverId}/location
Body: { "latitude": 48.8566, "longitude": 2.3522 }
    ↓
Backend met à jour :
  - drivers.latitude
  - drivers.longitude
  - drivers.status = 'online'
  - Enregistre dans driver_history (si table existe)
    ↓
Position visible immédiatement pour :
  - Client (sur /track-order)
  - Restaurant (sur /restaurant/show_order)
```

---

## 🧪 Tests avec cURL

### Test 1 : Récupérer le statut d'une commande
```bash
# Remplacer {orderNo} par un numéro de commande réel
curl -X GET "http://votre-domaine.com/api/order/ORD-2024-001/status" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json"
```

**Réponse attendue :**
```json
{
  "status": true,
  "order": {
    "order_no": "ORD-2024-001",
    "status": "assign",
    "progress": 75,
    "created_at": "2024-12-06 10:30:00",
    "estimated_time": 30,
    "remaining_minutes": 15,
    "restaurant": {
      "id": 1,
      "name": "Restaurant Test",
      "latitude": 48.8566,
      "longitude": 2.3522
    },
    "driver": {
      "id": 1,
      "name": "Jean Dupont",
      "phone": "+33612345678",
      "latitude": 48.8606,
      "longitude": 2.3376
    },
    "delivery_address": "123 Rue de la Paix, Paris",
    "delivery_latitude": 48.8566,
    "delivery_longitude": 2.3522,
    "total": 25.50
  },
  "items": [
    {
      "product_name": "Pizza Margherita",
      "qty": 2,
      "price": 12.50
    }
  ]
}
```

### Test 2 : Mettre à jour la position du livreur
```bash
# Remplacer {driverId} par un ID de livreur réel
curl -X POST "http://votre-domaine.com/api/driver/1/location" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "latitude": 48.8566,
    "longitude": 2.3522
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
    "latitude": 48.8566,
    "longitude": 2.3522,
    "status": "online"
  }
}
```

### Test 3 : Erreur - Données invalides
```bash
curl -X POST "http://votre-domaine.com/api/driver/1/location" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "latitude": 200,
    "longitude": 300
  }'
```

**Réponse attendue :**
```json
{
  "status": false,
  "message": "Données invalides",
  "errors": {
    "latitude": ["The latitude must be between -90 and 90."],
    "longitude": ["The longitude must be between -180 and 180."]
  }
}
```

### Test 4 : Erreur - Livreur non trouvé
```bash
curl -X POST "http://votre-domaine.com/api/driver/99999/location" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "latitude": 48.8566,
    "longitude": 2.3522
  }'
```

**Réponse attendue :**
```json
{
  "status": false,
  "message": "Livreur non trouvé"
}
```

---

## 📝 Étapes de Test Manuel

### Scénario 1 : Client suit sa commande

1. **Créer une commande** (via le frontend)
   - Aller sur `/`
   - Ajouter des produits au panier
   - Passer commande
   - Noter le `order_no` (ex: `ORD-2024-001`)

2. **Ouvrir la page de suivi**
   - URL : `/track-order/ORD-2024-001`
   - Vérifier que la page charge avec :
     - Timeline avec statut "Commande confirmée" (vert)
     - Carte avec restaurant et adresse de livraison
     - Temps estimé affiché

3. **Vérifier le rafraîchissement automatique**
   - Ouvrir la console du navigateur (F12)
   - Vérifier les requêtes AJAX toutes les 10 secondes vers `/api/order/ORD-2024-001/status`
   - Vérifier qu'il n'y a pas d'erreurs

4. **Simuler un changement de statut** (via admin/restaurant)
   - Aller dans l'admin/restaurant
   - Changer le statut de la commande à "prepairing"
   - Retourner sur la page de suivi
   - Vérifier que la timeline se met à jour automatiquement

5. **Simuler l'assignation d'un livreur**
   - Assigner un livreur à la commande (via admin/restaurant)
   - Mettre à jour la position du livreur via l'API (Test 2)
   - Vérifier que le marqueur du livreur apparaît sur la carte
   - Vérifier que le marqueur se déplace si on met à jour la position

### Scénario 2 : Restaurant suit une commande assignée

1. **Ouvrir la page de commande** (en tant que restaurant)
   - URL : `/restaurant/show_order/{orderId}`
   - Vérifier que la carte s'affiche avec :
     - Position du restaurant
     - Adresse de livraison
     - Itinéraire tracé

2. **Vérifier le rafraîchissement automatique**
   - Ouvrir la console du navigateur
   - Vérifier les requêtes AJAX toutes les 10 secondes
   - Mettre à jour la position du livreur via l'API
   - Vérifier que le marqueur du livreur se met à jour sur la carte

### Scénario 3 : Livreur met à jour sa position

1. **Simuler la mise à jour de position** (via API)
   ```bash
   # Utiliser le Test 2 avec différentes positions
   # Simuler un trajet : restaurant → client
   ```

2. **Vérifier la visibilité**
   - Ouvrir la page de suivi client (`/track-order/{orderNo}`)
   - Vérifier que la position du livreur se met à jour en temps réel
   - Ouvrir la page restaurant (`/restaurant/show_order/{orderId}`)
   - Vérifier que la position se met à jour également

---

## ✅ Checklist de Production

### Backend
- [x] Migration créée et exécutée
- [x] Modèle Driver mis à jour
- [x] Méthode `getOrderStatus()` créée dans IndexController
- [x] Méthode `updateLocation()` créée dans DriverController
- [x] Routes API ajoutées dans `routes/api.php`
- [x] Validation des données (latitude/longitude)
- [x] Gestion des erreurs (404, 422, 500)
- [x] Logging des erreurs

### Frontend
- [x] Vue `track_order.blade.php` mise à jour avec AJAX
- [x] Vue `show_orders.blade.php` (restaurant) mise à jour avec AJAX
- [x] Rafraîchissement automatique toutes les 10 secondes
- [x] Mise à jour dynamique de la timeline
- [x] Mise à jour dynamique de la carte Google Maps
- [x] Mise à jour dynamique de la position du livreur
- [x] Notifications visuelles lors des changements de statut
- [x] Arrêt du rafraîchissement pour les commandes terminées/annulées

### Intégration
- [x] Google Maps API configurée
- [x] Routes API testées avec curl
- [x] Workflow complet documenté
- [x] Exemples de test fournis

### Tests
- [ ] Tests unitaires pour `getOrderStatus()`
- [ ] Tests unitaires pour `updateLocation()`
- [ ] Tests d'intégration pour le workflow complet
- [ ] Tests de performance (rafraîchissement toutes les 10s)

---

## 🚀 Prochaines Étapes (Optionnel)

1. **Optimisation** :
   - Réduire la fréquence de rafraîchissement si la commande est en statut "pending"
   - Implémenter WebSockets pour un vrai temps réel (au lieu de polling)

2. **Fonctionnalités supplémentaires** :
   - Estimation du temps d'arrivée basée sur la position du livreur
   - Historique des positions du livreur
   - Notifications push lors des changements de statut

3. **Sécurité** :
   - Ajouter l'authentification pour les routes API (middleware `auth:api`)
   - Vérifier que seul le client peut voir sa commande
   - Vérifier que seul le livreur peut mettre à jour sa position

---

## 📞 Support

En cas de problème :
1. Vérifier les logs Laravel : `storage/logs/laravel.log`
2. Vérifier la console du navigateur pour les erreurs JavaScript
3. Vérifier que la migration a été exécutée : `php artisan migrate:status`
4. Vérifier que les routes API sont accessibles : `php artisan route:list | grep order`

