# Documentation : Page "Voir tout" - Restaurants avec Filtres et Pagination

**Date :** 2025-12-05  
**Feature :** Page complète de liste des restaurants avec filtres, tri et pagination

---

## 📋 Résumé

Page complète permettant aux utilisateurs de voir tous les restaurants avec des filtres avancés (cuisine, note minimale, frais de livraison max, recherche), tri (popularité, note, frais, nom) et pagination.

---

## 🗂️ Fichiers Créés/Modifiés

### Backend

1. **Service :** `app/Services/RestaurantService.php` (NOUVEAU)
   - `searchRestaurants()` : Recherche avec filtres, tri et pagination

2. **Contrôleur :** `app/Http/Controllers/api/RestaurantController.php`
   - Ajout de `index()` : API endpoint pour la liste avec filtres
   - Injection de `RestaurantService` dans le constructeur

3. **Contrôleur :** `app/Http/Controllers/IndexController.php`
   - Modification de `allRestaurants()` : Support AJAX + vue initiale

4. **Routes :** `routes/api.php`
   - `GET /api/restaurants` : Liste avec filtres et pagination

### Frontend

1. **Vue :** `resources/views/frontend/restaurants.blade.php` (MODIFIÉ)
   - Filtres avancés (cuisine, note, frais, recherche, tri)
   - Pagination
   - JavaScript AJAX pour chargement dynamique

2. **Partial :** `resources/views/frontend/partials/restaurants_list.blade.php` (NOUVEAU)
   - Composant réutilisable pour afficher la liste de restaurants

---

## 🔄 Workflow Complet

### 1. Côté Utilisateur

1. L'utilisateur clique sur **"Voir tout"** dans la section "Restaurants populaires"
2. Redirection vers `/restaurants`
3. La page affiche :
   - Tous les restaurants avec pagination (12 par page)
   - Filtres : Cuisine, Note minimale, Frais max, Recherche, Tri
4. L'utilisateur applique des filtres
5. Le JavaScript envoie une requête AJAX à `/restaurants?filters...`
6. Les résultats se mettent à jour sans rechargement de page
7. L'utilisateur peut naviguer entre les pages

### 2. Côté Backend

**Requête initiale (GET `/restaurants`) :**
- `IndexController@allRestaurants()` charge les restaurants via `RestaurantService`
- Retourne la vue avec les données initiales

**Requête AJAX (GET `/restaurants?filters...`) :**
- `IndexController@allRestaurants()` détecte `$request->ajax()`
- Appelle `RestaurantService::searchRestaurants($filters)`
- Retourne JSON avec pagination

**API (GET `/api/restaurants?filters...`) :**
- `RestaurantController@index()` traite les filtres
- Appelle `RestaurantService::searchRestaurants($filters)`
- Retourne JSON formaté avec `data` et `meta`

### 3. Filtres Disponibles

- **Cuisine** : Filtre par type de cuisine (depuis DB)
- **Note minimale** : Filtre par note ≥ X (depuis `ratings` table)
- **Frais max** : Filtre par frais de livraison ≤ X (depuis `delivery_charges` ou `ConfigService`)
- **Recherche** : Recherche textuelle (nom, adresse, description)
- **Tri** : Popularité, Meilleure note, Frais de livraison, Nom (A-Z)

---

## 📡 Routes API

### GET `/api/restaurants`

**Description :** Liste des restaurants avec filtres, tri et pagination

**Paramètres de requête :**
- `city` : Ville (optionnel)
- `min_rating` : Note minimale (optionnel, ex: 4.5)
- `max_delivery_fee` : Frais de livraison maximum (optionnel, ex: 2000)
- `cuisine` : ID de cuisine ou liste séparée par virgules (optionnel)
- `search` : Recherche textuelle (optionnel)
- `sort` : Tri (`popular`, `rating`, `delivery_fee`, `name`) - défaut: `popular`
- `per_page` : Nombre par page (défaut: 12)
- `page` : Numéro de page (défaut: 1)

**Réponse Succès (200) :**
```json
{
  "status": true,
  "data": [
    {
      "id": 1,
      "name": "Espace Malebo",
      "avg_rating": 4.5,
      "rating_count": 25,
      "delivery_fee": 1500,
      "eta_min": 20,
      "eta_max": 35,
      "eta_display": "20-35 min",
      "cuisines": ["Cuisine congolaise", "Grillades"],
      "cuisines_display": "Cuisine congolaise · Grillades",
      "is_top_rated": true,
      "is_featured": false,
      "thumbnail_url": "https://...",
      "city": "Brazzaville",
      "address": "...",
      "min_order": 0
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 12,
    "total": 58,
    "from": 1,
    "to": 12
  },
  "BASE_URL_RESTAURANT": "https://..."
}
```

---

## 🧪 Tests

### Exemple d'appel HTTP (curl)

```bash
# Liste simple
curl -X GET "https://dev.bantudelice.cg/api/restaurants?per_page=12" \
  -H "Accept: application/json"

# Avec filtres
curl -X GET "https://dev.bantudelice.cg/api/restaurants?min_rating=4.0&max_delivery_fee=2000&sort=rating&per_page=12" \
  -H "Accept: application/json"

# Avec recherche
curl -X GET "https://dev.bantudelice.cg/api/restaurants?search=malebo&sort=name" \
  -H "Accept: application/json"
```

### Étapes pour tester manuellement

1. **Accéder à la page :**
   - Aller sur la page d'accueil
   - Cliquer sur "Voir tout" dans la section "Restaurants populaires"
   - Vérifier que la page `/restaurants` s'affiche

2. **Tester les filtres :**
   - Sélectionner une cuisine dans le filtre
   - Sélectionner une note minimale (ex: 4.5+)
   - Sélectionner un frais max (ex: ≤ 2000 FCFA)
   - Vérifier que les résultats se mettent à jour via AJAX

3. **Tester le tri :**
   - Changer le tri (Popularité → Meilleure note → Frais → Nom)
   - Vérifier que l'ordre change

4. **Tester la recherche :**
   - Taper un nom de restaurant dans le champ recherche
   - Vérifier que les résultats se filtrent (debounce 500ms)

5. **Tester la pagination :**
   - Si plus de 12 restaurants, cliquer sur "Suivant"
   - Vérifier que la page suivante se charge
   - Cliquer sur "Précédent"
   - Vérifier que la page précédente se charge

6. **Vérifier l'API directement :**
   - Appeler `/api/restaurants?per_page=2`
   - Vérifier que le JSON contient `status: true`, `data` et `meta`

---

## ✅ Checklist de Complétude

### Backend
- [x] `RestaurantService` créé avec `searchRestaurants()`
- [x] Filtres implémentés (cuisine, note, frais, recherche)
- [x] Tri implémenté (popular, rating, delivery_fee, name)
- [x] Pagination implémentée
- [x] `RestaurantController@index()` créé
- [x] Route API `GET /api/restaurants` ajoutée
- [x] `IndexController@allRestaurants()` modifié pour supporter AJAX
- [x] Toutes les données depuis la DB (pas de valeurs codées en dur)

### Frontend
- [x] Vue `restaurants.blade.php` améliorée avec filtres
- [x] Partial `restaurants_list.blade.php` créé
- [x] JavaScript AJAX pour chargement dynamique
- [x] Filtres interactifs (select, input)
- [x] Pagination fonctionnelle
- [x] Mise à jour de l'URL sans rechargement
- [x] Design cohérent avec la section "Restaurants populaires"

### Workflow
- [x] Bouton "Voir tout" pointe vers `/restaurants`
- [x] Page charge les restaurants initialement
- [x] Filtres mettent à jour les résultats via AJAX
- [x] Pagination fonctionne
- [x] Intégration avec l'API `/api/restaurants`

### Intégration
- [x] Aucune route fictive
- [x] Pas de TODO bloquant
- [x] Compatible avec l'existant
- [x] Toutes les données depuis la DB

---

## 🔗 Intégration avec "Restaurants Populaires"

La page "Voir tout" est **intégrée** avec la section "Restaurants populaires" :

1. Le bouton "Voir tout" redirige vers `/restaurants`
2. Les cartes utilisent le même design que la section populaire
3. Les données proviennent de la même source (`RestaurantService`)
4. Les filtres utilisent les mêmes valeurs (`ConfigService`)

---

## 📝 Notes Techniques

- **Tri par rating/popularité :** Le tri se fait après récupération pour éviter les conflits avec les relations Eloquent
- **Pagination :** 12 restaurants par page par défaut
- **AJAX :** Les filtres mettent à jour les résultats sans rechargement
- **Debounce :** La recherche a un debounce de 500ms pour éviter trop de requêtes
- **URL :** L'URL est mise à jour avec les filtres pour permettre le partage

---

## 🚀 État : Feature Complète

**✅ La feature est complète et prête pour les tests en production.**

Tous les éléments de la checklist sont remplis. Le système permet aux utilisateurs de voir tous les restaurants avec filtres, tri et pagination, alimenté entièrement par la base de données.

