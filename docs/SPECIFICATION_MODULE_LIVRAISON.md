# Spécification : Module "Livraison & Livreurs"

**Date :** 2025-12-05  
**Feature :** Gestion des livraisons et assignation aux livreurs

---

## 📋 Contexte Existant

**Structure actuelle :**
- Table `drivers` existe (liée à `restaurant_id`)
- Table `orders` existe (avec `driver_id` nullable)
- Modèle `Driver` existe
- Les commandes sont créées dans `IndexController@getOrders()`

**Adaptations nécessaires :**
- Créer la table `deliveries` pour gérer le workflow de livraison
- Adapter le modèle `Driver` pour supporter les livreurs indépendants (optionnel)
- Créer le service `DeliveryService`
- Intégrer la création de livraison lors de la création de commande

---

## 🗂️ Fichiers à Créer/Modifier

### Backend

1. **Migration :** `database/migrations/2025_12_05_100000_create_deliveries_table.php` (NOUVEAU)
2. **Modèle :** `app/Delivery.php` (NOUVEAU)
3. **Service :** `app/Services/DeliveryService.php` (NOUVEAU)
4. **Controller API :** `app/Http/Controllers/api/DriverDeliveriesController.php` (NOUVEAU)
5. **Controller API :** `app/Http/Controllers/api/OrderTrackingController.php` (NOUVEAU)
6. **Modèle :** `app/Order.php` (MODIFIÉ - ajouter relation `delivery()`)
7. **Modèle :** `app/Driver.php` (MODIFIÉ - ajouter relation `deliveries()`)
8. **Controller :** `app/Http/Controllers/IndexController.php` (MODIFIÉ - intégrer création livraison)
9. **Routes :** `routes/api.php` (MODIFIÉ - ajouter routes API)

---

## 🔄 Workflow Complet

### 1. Client → Commande

1. Client valide sa commande (`IndexController@getOrders()`)
2. Commande créée dans `orders` avec `driver_id = null`
3. **NOUVEAU :** Livraison créée automatiquement dans `deliveries` avec `status = PENDING`

### 2. Assignation Livreur

1. Admin ou système assigne un livreur à la livraison
2. `DeliveryService::assignDriver()` met à jour :
   - `delivery.driver_id`
   - `delivery.status = ASSIGNED`
   - `delivery.assigned_at`
   - `driver.is_available = false`

### 3. Workflow Livreur

1. Livreur se connecte → voit ses livraisons (`GET /api/driver/deliveries`)
2. Livreur met à jour le statut :
   - `PICKED_UP` → récupéré au restaurant
   - `ON_THE_WAY` → en route
   - `DELIVERED` → livré

### 4. Suivi Client

1. Client consulte le statut (`GET /api/orders/{order}/tracking`)
2. Affichage du statut et du livreur (sans GPS pour l'instant)

---

## 📡 Routes API

```php
// Côté livreur
GET  /api/driver/deliveries
PATCH /api/driver/deliveries/{delivery}/status

// Côté client
GET  /api/orders/{order}/tracking
```

---

## ✅ Checklist

- [ ] Migration `deliveries` créée
- [ ] Modèle `Delivery` créé
- [ ] Service `DeliveryService` créé
- [ ] Controllers API créés
- [ ] Routes API ajoutées
- [ ] Intégration dans création de commande
- [ ] Relations modèles ajoutées
- [ ] Tests manuels effectués

