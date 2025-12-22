# Spécification : Amélioration de la Fiche Restaurant

**Date :** 2025-12-05  
**Feature :** Statut ouvert/fermé, avis détaillés, promos, panier UI amélioré

---

## 📋 Objectif

Améliorer la fiche restaurant (`/resturant/view/{id}`) avec :
1. **Statut ouvert/fermé** basé sur les horaires (`working_hours`)
2. **Affichage des avis détaillés** (liste complète des reviews)
3. **Promos actives** (vouchers, réductions)
4. **Panier UI amélioré** (notifications, toasts, badge)

---

## 🗂️ Fichiers à Créer/Modifier

### Backend

1. **Service :** `app/Services/RestaurantStatusService.php` (NOUVEAU)
   - `isOpen()` : Vérifier si le restaurant est ouvert maintenant
   - `getNextOpeningTime()` : Prochain horaire d'ouverture
   - `getCurrentDaySchedule()` : Horaires du jour actuel

2. **Contrôleur :** `app/Http/Controllers/IndexController.php`
   - Modifier `resturantDetail()` : Ajouter horaires, avis, promos

3. **Contrôleur API :** `app/Http/Controllers/api/RestaurantController.php`
   - Ajouter `getReviews()` : Liste paginée des avis
   - Ajouter `getActivePromos()` : Promos actives du restaurant

4. **Routes :** `routes/api.php`
   - `GET /api/restaurants/{id}/reviews` : Liste des avis
   - `GET /api/restaurants/{id}/promos` : Promos actives

### Frontend

1. **Vue :** `resources/views/frontend/menu.blade.php`
   - Ajouter badge "Ouvert/Fermé" dans le hero
   - Ajouter section "Avis clients" avec liste paginée
   - Ajouter section "Promotions" avec vouchers actifs
   - Améliorer le panier UI (toasts, notifications)

2. **Partial :** `resources/views/frontend/partials/restaurant_reviews.blade.php` (NOUVEAU)
   - Composant pour afficher les avis

3. **JavaScript :** Améliorer les fonctions de panier dans `app-modern.blade.php`

---

## 🔄 Workflow Complet

### 1. Statut Ouvert/Fermé

**Côté Utilisateur :**
1. L'utilisateur accède à la fiche restaurant
2. Un badge "Ouvert" (vert) ou "Fermé" (rouge) s'affiche
3. Si fermé, afficher "Réouvre à [heure]"

**Côté Backend :**
1. `RestaurantStatusService::isOpen($restaurantId)` :
   - Récupère les `working_hours` du restaurant
   - Vérifie le jour actuel (lundi, mardi, etc.)
   - Compare l'heure actuelle avec `opening_time` et `closing_time`
   - Retourne `true/false` + prochaine ouverture

**Côté Frontend :**
1. Afficher le badge dans le hero
2. Optionnel : désactiver "Ajouter au panier" si fermé

### 2. Avis Détaillés

**Côté Utilisateur :**
1. L'utilisateur voit une section "Avis clients" sur la fiche
2. Liste paginée des avis (note + commentaire + auteur + date)
3. Possibilité de voir tous les avis

**Côté Backend :**
1. `RestaurantController@getReviews($id)` :
   - Récupère les `ratings` avec `user` (relation)
   - Pagination (10 par page)
   - Retourne JSON avec avis formatés

**Côté Frontend :**
1. Section "Avis clients" avec liste
2. Pagination AJAX
3. Design moderne (cartes, étoiles, dates)

### 3. Promos Actives

**Côté Utilisateur :**
1. L'utilisateur voit une section "Promotions" sur la fiche
2. Liste des vouchers actifs (nom, réduction, dates)
3. Badge "Promo" sur les produits concernés

**Côté Backend :**
1. `RestaurantController@getActivePromos($id)` :
   - Récupère les `vouchers` où `start_date <= now()` et `end_date >= now()`
   - Retourne JSON avec promos formatées

**Côté Frontend :**
1. Section "Promotions" avec badges
2. Affichage des réductions disponibles

### 4. Panier UI Amélioré

**Côté Utilisateur :**
1. L'utilisateur clique sur "Ajouter au panier"
2. Un toast de confirmation s'affiche
3. Le badge du panier se met à jour immédiatement
4. Optionnel : drawer panier à droite

**Côté Backend :**
- Déjà implémenté (voir `addToCart` dans `IndexController`)

**Côté Frontend :**
1. Améliorer les toasts (design moderne)
2. Mise à jour du badge en temps réel
3. Optionnel : drawer panier

---

## 📡 Routes API

### GET `/api/restaurants/{id}/reviews`

**Description :** Liste paginée des avis d'un restaurant

**Paramètres :**
- `page` : Numéro de page (défaut: 1)
- `per_page` : Nombre par page (défaut: 10)

**Réponse :**
```json
{
  "status": true,
  "data": [
    {
      "id": 1,
      "user_name": "John Doe",
      "user_image": "...",
      "rating": 5,
      "comment": "Excellent restaurant !",
      "created_at": "2025-12-01T10:30:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 3,
    "total": 25
  }
}
```

### GET `/api/restaurants/{id}/promos`

**Description :** Promos actives d'un restaurant

**Réponse :**
```json
{
  "status": true,
  "data": [
    {
      "id": 1,
      "name": "Réduction 20%",
      "discount": 20,
      "start_date": "2025-12-01",
      "end_date": "2025-12-31"
    }
  ]
}
```

---

## ✅ Checklist de Complétude

### Backend
- [ ] `RestaurantStatusService` créé
- [ ] `IndexController@resturantDetail()` modifié
- [ ] `RestaurantController@getReviews()` créé
- [ ] `RestaurantController@getActivePromos()` créé
- [ ] Routes API ajoutées
- [ ] Toutes les données depuis la DB

### Frontend
- [ ] Badge "Ouvert/Fermé" dans le hero
- [ ] Section "Avis clients" avec pagination
- [ ] Section "Promotions"
- [ ] Toasts améliorés pour le panier
- [ ] Badge panier mis à jour en temps réel

### Workflow
- [ ] Scénario complet décrit
- [ ] Intégration avec l'existant

### Intégration
- [ ] Aucune route fictive
- [ ] Pas de TODO bloquant
- [ ] Toutes les données depuis la DB

