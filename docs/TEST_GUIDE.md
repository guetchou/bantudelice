# Guide de Test - Suivi de Commandes en Temps Réel

## 🎯 Objectif
Tester le système de suivi de commandes en temps réel pour les 3 rôles : Client, Restaurant, Livreur

## 📋 Prérequis
1. Avoir au moins :
   - 1 compte client
   - 1 compte restaurant
   - 1 compte livreur
   - 1 produit dans le restaurant
2. Les comptes doivent être actifs et validés

---

## 🔵 TEST 1 : CLIENT - Passer une commande et suivre

### Étape 1 : Connexion Client
```
URL: https://dev.bantudelice.cg/login
Type: user
```

### Étape 2 : Passer une commande
1. Aller sur la page d'accueil
2. Sélectionner un restaurant
3. Ajouter des produits au panier
4. Aller au checkout (`/cart/checkout`)
5. **Vérifier la carte Google Maps** :
   - La carte doit s'afficher
   - Vous pouvez rechercher une adresse
   - Vous pouvez cliquer sur la carte pour placer le marqueur
   - Les coordonnées doivent être mises à jour automatiquement
6. Remplir l'adresse de livraison
7. Choisir le mode de paiement (Cash, Mobile Money, PayPal)
8. Cliquer sur "Passer la commande"

### Étape 3 : Suivre la commande
1. Après la commande, vous serez redirigé vers la page de remerciement
2. Cliquer sur "Suivre ma commande" ou aller sur `/track-order/{orderNo}`
3. **Vérifier** :
   - La timeline de statut s'affiche
   - La carte montre l'adresse de livraison (marqueur rouge)
   - Le restaurant est visible (marqueur bleu) si disponible
   - L'itinéraire est tracé entre restaurant et livraison
   - Le temps estimé de livraison s'affiche
   - Les détails de la commande sont visibles

### Étape 4 : Vérifier les notifications
- Une notification push doit être reçue : "Commande confirmée"

---

## 🟢 TEST 2 : RESTAURANT - Recevoir et gérer la commande

### Étape 1 : Connexion Restaurant
```
URL: https://dev.bantudelice.cg/restaurant
Type: restaurant
```

### Étape 2 : Voir les nouvelles commandes
1. Aller sur `/restaurant/all_orders`
2. **Vérifier** :
   - La nouvelle commande apparaît dans la liste
   - Le numéro de commande est visible
   - L'adresse de livraison est affichée
   - Le total est correct

### Étape 3 : Voir les détails de la commande
1. Cliquer sur "Voir" pour une commande
2. **Vérifier** :
   - Les détails du client sont visibles
   - Les produits commandés sont listés
   - L'adresse de livraison est affichée
   - La carte Google Maps montre l'itinéraire (si disponible)

### Étape 4 : Préparer la commande
1. Retourner à `/restaurant/all_orders`
2. Cocher la commande
3. Cliquer sur "Prepair Orders"
4. **Vérifier** :
   - Le statut passe à "prepairing"
   - Une notification est envoyée au client

### Étape 5 : Assigner un livreur
1. Aller sur `/restaurant/show_order/{orderNo}`
2. Cliquer sur "Assigner un livreur"
3. Sélectionner un livreur disponible
4. **Vérifier** :
   - Le livreur est assigné
   - Le statut passe à "assign"
   - Une notification est envoyée au client et au livreur

---

## 🟡 TEST 3 : LIVREUR - Recevoir et livrer la commande

### Étape 1 : Connexion Livreur
```
URL: https://dev.bantudelice.cg/login
Type: delivery (ou driver selon la configuration)
```

### Étape 2 : Voir les commandes assignées
1. Aller sur le dashboard livreur
2. **Vérifier** :
   - Les commandes assignées apparaissent
   - Les détails de chaque commande sont visibles
   - L'adresse de livraison est affichée

### Étape 3 : Accepter la commande
1. Cliquer sur une commande assignée
2. Voir les détails (produits, adresse, client)
3. Accepter la commande
4. **Vérifier** :
   - Le statut passe à "pickup" ou "onway"
   - Une notification est envoyée au client

### Étape 4 : Suivre l'itinéraire
1. Voir la carte avec l'itinéraire
2. **Vérifier** :
   - La position du restaurant (point de départ)
   - L'adresse de livraison (destination)
   - L'itinéraire est tracé
   - La distance et le temps estimé sont affichés

### Étape 5 : Marquer comme livré
1. Après la livraison, marquer la commande comme "Livrée"
2. **Vérifier** :
   - Le statut passe à "completed"
   - Une notification est envoyée au client
   - La commande apparaît dans les commandes complétées

---

## 🔄 TEST 4 : SUIVI EN TEMPS RÉEL

### Pour le Client
1. Ouvrir `/track-order/{orderNo}` dans un onglet
2. La page se rafraîchit automatiquement toutes les 30 secondes
3. **Vérifier** :
   - Le statut se met à jour automatiquement
   - La timeline progresse
   - La carte se met à jour si le livreur a une position

### Pour le Restaurant
1. Ouvrir `/restaurant/all_orders` dans un onglet
2. Les notifications apparaissent en temps réel
3. **Vérifier** :
   - Les nouvelles commandes apparaissent sans rafraîchir
   - Le compteur de notifications se met à jour

### Pour le Livreur
1. Ouvrir le dashboard livreur
2. **Vérifier** :
   - Les nouvelles commandes assignées apparaissent
   - Les notifications sont reçues en temps réel

---

## ✅ Checklist de Validation

### Fonctionnalités Google Maps
- [ ] Carte s'affiche correctement dans le checkout
- [ ] Recherche d'adresse fonctionne
- [ ] Glisser-déposer du marqueur fonctionne
- [ ] Clic sur la carte place le marqueur
- [ ] Coordonnées sont sauvegardées
- [ ] Carte de suivi affiche l'itinéraire
- [ ] Marqueurs (rouge, bleu, vert) s'affichent correctement

### Notifications
- [ ] Client reçoit notification de confirmation
- [ ] Client reçoit notification de préparation
- [ ] Client reçoit notification d'assignation livreur
- [ ] Client reçoit notification de livraison
- [ ] Restaurant reçoit notification de nouvelle commande
- [ ] Livreur reçoit notification de nouvelle commande assignée

### Statuts de commande
- [ ] pending → prepairing
- [ ] prepairing → assign
- [ ] assign → pickup/onway
- [ ] pickup/onway → completed

### Interface
- [ ] Timeline de statut s'affiche correctement
- [ ] Progression visuelle fonctionne
- [ ] Détails de commande sont complets
- [ ] Boutons d'action fonctionnent

---

## 🐛 Problèmes Potentiels

### Si la carte ne s'affiche pas
1. Vérifier la clé API Google Maps dans `.env`
2. Vérifier les restrictions de domaine dans Google Cloud Console
3. Vérifier la console du navigateur pour les erreurs

### Si les notifications ne fonctionnent pas
1. Vérifier que les tokens FCM sont enregistrés
2. Vérifier les clés API FCM dans `NotificationService`
3. Vérifier les logs Laravel

### Si le suivi ne se met pas à jour
1. Vérifier que le rafraîchissement automatique est activé
2. Vérifier la connexion WebSocket (si implémenté)
3. Vérifier les logs du serveur

---

## 📝 Notes
- Les coordonnées par défaut sont pour Brazzaville, Congo (-4.2634, 15.2429)
- Le rafraîchissement automatique est de 30 secondes
- Les notifications nécessitent des tokens FCM valides

---

## 🚀 Commandes Utiles

```bash
# Voir les logs en temps réel
tail -f storage/logs/laravel.log

# Vérifier les commandes en base
php artisan tinker
>>> \App\Order::latest()->take(5)->get(['order_no', 'status', 'created_at']);

# Vérifier les tokens FCM
>>> \App\UserToken::where('user_id', 1)->first();
```




