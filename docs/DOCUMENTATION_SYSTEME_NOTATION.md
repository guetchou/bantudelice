# Documentation : Système de Notation des Restaurants

**Date :** 2025-12-05  
**Feature :** Notation complète des restaurants après commande livrée

---

## 📋 Résumé

Système complet permettant aux clients de noter un restaurant après une commande livrée, avec recalcul automatique de `avg_rating` et `rating_count` pour chaque restaurant.

---

## 🗂️ Fichiers Créés/Modifiés

### Backend

1. **Migration :** `database/migrations/2025_12_05_022940_add_order_id_to_ratings_table.php`
   - Ajoute `order_id` à la table `ratings`
   - Ajoute la clé étrangère vers `orders`

2. **Modèle :** `app/Rating.php`
   - Ajout de `order_id` dans `$fillable`
   - Ajout de la relation `order()`
   - Ajout de la relation `user()`
   - Correction de `restaurants()` → `restaurant()`

3. **Modèle :** `app/Order.php`
   - Ajout de la relation `rating()`

4. **Service :** `app/Services/RatingService.php` (NOUVEAU)
   - `rateOrder()` : Noter une commande
   - `recalculateRestaurantRating()` : Recalculer `avg_rating` et `rating_count`
   - `canRateOrder()` : Vérifier si une commande peut être notée

5. **Contrôleur :** `app/Http/Controllers/api/OrderRatingController.php` (NOUVEAU)
   - `store()` : POST `/api/orders/{order}/rating` - Noter une commande
   - `show()` : GET `/api/orders/{order}/rating` - Récupérer la note d'une commande
   - `check()` : GET `/api/orders/{order}/rating/check` - Vérifier si on peut noter

6. **Routes :** `routes/api.php`
   - `POST /api/orders/{order}/rating`
   - `GET /api/orders/{order}/rating`
   - `GET /api/orders/{order}/rating/check`

### Frontend

1. **Vue :** `resources/views/frontend/profile.blade.php`
   - Modification de la section "Mes Commandes"
   - Affichage des commandes depuis `orders` ET `completed_orders`
   - Bouton "Noter" pour les commandes complétées non notées
   - Badge "Noté (X/5)" pour les commandes déjà notées
   - Modal de notation avec sélection d'étoiles et commentaire
   - JavaScript AJAX pour soumettre les notes

---

## 🔄 Workflow Complet

### 1. Côté Utilisateur (Client)

1. Le client passe une commande sur BantuDelice
2. La commande passe au statut `completed` (livrée)
3. Le client accède à son profil → "Mes Commandes"
4. Pour chaque commande livrée non notée, un bouton **"Noter"** apparaît
5. Le client clique sur "Noter"
6. Un modal s'ouvre avec :
   - Sélection d'étoiles (1 à 5)
   - Champ commentaire optionnel
7. Le client valide
8. Le frontend envoie `POST /api/orders/{orderId}/rating`

### 2. Côté Backend

À la réception du `POST` :

1. **Validation :**
   - Utilisateur authentifié
   - Commande existe (dans `orders` ou `completed_orders`)
   - Commande appartient à l'utilisateur
   - Commande a le statut `completed`
   - Aucune note précédente n'existe pour cette commande

2. **Création du Rating :**
   - Création d'un enregistrement dans `ratings` avec :
     - `restaurant_id`
     - `user_id`
     - `order_id`
     - `rating` (1-5)
     - `reviews` (commentaire)

3. **Recalcul automatique :**
   - Calcul de `AVG(rating)` pour le restaurant
   - Calcul de `COUNT(*)` pour le restaurant
   - Mise à jour de `restaurants.avg_rating` et `restaurants.rating_count`

4. **Réponse JSON :**
   ```json
   {
     "status": true,
     "message": "Note enregistrée avec succès.",
     "data": {
       "rating": 5,
       "comment": "...",
       "created_at": "..."
     }
   }
   ```

### 3. Côté Frontend (Après Succès)

1. Affichage d'un message de confirmation
2. Fermeture du modal
3. Rechargement de la page après 1 seconde
4. Le bouton "Noter" est remplacé par "Noté (X/5)"

---

## 📡 Routes API

### POST `/api/orders/{order}/rating`

**Description :** Noter une commande

**Paramètres :**
- `order` : ID de la commande (dans `orders` ou `completed_orders`)

**Body (JSON) :**
```json
{
  "rating": 5,
  "comment": "Livraison rapide et repas bien chaud."
}
```

**Réponse Succès (201) :**
```json
{
  "status": true,
  "message": "Note enregistrée avec succès.",
  "data": {
    "rating": 5,
    "comment": "Livraison rapide et repas bien chaud.",
    "created_at": "2025-12-05T10:30:00.000000Z"
  }
}
```

**Réponse Erreur (422) :**
```json
{
  "status": false,
  "message": "Cette commande a déjà été notée."
}
```

### GET `/api/orders/{order}/rating`

**Description :** Récupérer la note d'une commande

**Réponse Succès (200) :**
```json
{
  "status": true,
  "data": {
    "rating": 5,
    "comment": "...",
    "created_at": "..."
  }
}
```

### GET `/api/orders/{order}/rating/check`

**Description :** Vérifier si une commande peut être notée

**Réponse (200) :**
```json
{
  "status": true,
  "can_rate": true,
  "message": "Vous pouvez noter cette commande.",
  "existing_rating": null
}
```

---

## 🧪 Tests

### Exemple d'appel HTTP (curl)

```bash
# Noter une commande
curl -X POST "https://dev.bantudelice.cg/api/orders/123/rating" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: YOUR_CSRF_TOKEN" \
  -d '{
    "rating": 5,
    "comment": "Livraison rapide et repas bien chaud."
  }'
```

### Étapes pour tester manuellement

1. **Créer une commande de test :**
   - Se connecter en tant que client
   - Passer une commande
   - Attendre que la commande soit marquée comme `completed`

2. **Tester la notation :**
   - Aller sur `/profile` → onglet "Mes Commandes"
   - Vérifier qu'un bouton "Noter" apparaît pour la commande complétée
   - Cliquer sur "Noter"
   - Sélectionner une note (1-5 étoiles)
   - Optionnel : ajouter un commentaire
   - Cliquer sur "Enregistrer"

3. **Vérifier le résultat :**
   - Message de confirmation affiché
   - Page rechargée
   - Bouton "Noter" remplacé par "Noté (X/5)"
   - En base de données :
     - Une ligne créée dans `ratings`
     - `restaurants.avg_rating` mis à jour
     - `restaurants.rating_count` incrémenté

4. **Vérifier l'impact sur "Restaurants populaires" :**
   - Aller sur la page d'accueil
   - Vérifier que la note du restaurant dans "Restaurants populaires" reflète la nouvelle note
   - Appeler `/api/restaurants/popular` et vérifier que `avg_rating` est correct

---

## ✅ Checklist de Complétude

### Backend
- [x] Migration créée et exécutée
- [x] Modèle `Rating` modifié (relation `order()`)
- [x] Modèle `Order` modifié (relation `rating()`)
- [x] `RatingService` créé avec logique complète
- [x] `OrderRatingController` créé
- [x] Routes API ajoutées
- [x] Validation complète (utilisateur, commande, statut)
- [x] Recalcul automatique de `avg_rating` et `rating_count`
- [x] Gestion d'erreurs complète

### Frontend
- [x] Section "Mes Commandes" modifiée
- [x] Affichage des commandes depuis `orders` ET `completed_orders`
- [x] Bouton "Noter" pour commandes complétées non notées
- [x] Badge "Noté (X/5)" pour commandes déjà notées
- [x] Modal de notation créé
- [x] Sélection d'étoiles (1-5)
- [x] Champ commentaire optionnel
- [x] JavaScript AJAX pour soumission
- [x] Messages de confirmation/erreur
- [x] Rechargement automatique après notation

### Workflow
- [x] Scénario utilisateur complet décrit
- [x] Intégration avec `completed_orders` et `orders`
- [x] Recalcul automatique des stats restaurant
- [x] Impact sur "Restaurants populaires" vérifié

### Intégration
- [x] Aucune route fictive
- [x] Pas de TODO bloquant
- [x] Compatible avec l'existant (`sendReviewsToRestaurant` reste fonctionnel)

---

## 🔗 Intégration avec "Restaurants Populaires"

Le système de notation est **intégré** avec la section "Restaurants populaires" :

1. Quand un client note un restaurant, `avg_rating` et `rating_count` sont recalculés
2. L'endpoint `/api/restaurants/popular` utilise ces valeurs pour classer les restaurants
3. Le badge "Top noté" utilise `ConfigService::getTopRatedThreshold()` et `getTopRatedMinReviews()`
4. Les notes sont dynamiques et reflètent les avis réels des clients

---

## 📝 Notes Techniques

- **Double source de commandes :** Le système gère à la fois `orders` et `completed_orders` pour compatibilité
- **Un seul avis par commande :** Une commande ne peut être notée qu'une seule fois
- **Recalcul automatique :** Les stats du restaurant sont recalculées à chaque nouvelle note
- **Cache :** `ConfigService::clearCache()` est appelé après recalcul pour invalider le cache

---

## 🚀 État : Feature Complète

**✅ La feature est complète et prête pour les tests en production.**

Tous les éléments de la checklist sont remplis. Le système permet aux clients de noter les restaurants après commande, avec recalcul automatique des notes moyennes utilisées par la section "Restaurants populaires".

