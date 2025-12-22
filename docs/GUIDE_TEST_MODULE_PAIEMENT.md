# GUIDE DE TEST - MODULE PAIEMENT & CHECKOUT

## Vue d'ensemble

Ce guide décrit comment tester le module de paiement et checkout de TheDrop247 de manière complète, du frontend au backend.

## Prérequis

1. **Base de données** : Migrations exécutées
   ```bash
   php artisan migrate
   ```

2. **Utilisateur test** : Un utilisateur avec panier rempli
   ```sql
   -- Vérifier qu'un utilisateur existe
   SELECT id, name, email, phone FROM users LIMIT 1;
   
   -- Vérifier qu'il a des produits dans le panier
   SELECT * FROM carts WHERE user_id = 1;
   ```

3. **Restaurants et produits** : Au moins un restaurant avec produits
   ```sql
   SELECT * FROM restaurants LIMIT 1;
   SELECT * FROM products WHERE restaurant_id = 1 LIMIT 5;
   ```

4. **Frais de livraison** : Table `charges` configurée
   ```sql
   SELECT * FROM charges;
   -- Si vide, les valeurs par défaut seront utilisées
   ```

## Tests Backend (API)

### 1. Test Checkout - Paiement Cash

**Endpoint** : `POST /api/checkout`

**Headers** :
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {token}  # Si authentification token
# OU cookies de session si authentification web
```

**Request** :
```json
{
  "payment_method": "cash",
  "delivery_address": "123 Avenue de la République, Brazzaville",
  "d_lat": "-4.2634",
  "d_lng": "15.2429",
  "driver_tip": 500,
  "voucher_code": null
}
```

**cURL** :
```bash
curl -X POST http://localhost:8000/api/checkout \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "X-CSRF-TOKEN: {csrf_token}" \
  --cookie "laravel_session={session_cookie}" \
  -d '{
    "payment_method": "cash",
    "delivery_address": "123 Avenue de la République",
    "d_lat": "-4.2634",
    "d_lng": "15.2429",
    "driver_tip": 500
  }'
```

**Response attendue** :
```json
{
  "status": true,
  "payment": {
    "id": 1,
    "status": "PAID",
    "amount": 15000,
    "currency": "XAF",
    "provider": "cash"
  },
  "requires_external_payment": false,
  "order": {
    "id": 1,
    "order_no": "TD-20250105-1234"
  }
}
```

**Vérifications** :
- [ ] Payment créé avec `status = PAID`
- [ ] Order créé avec `payment_status = paid`
- [ ] Delivery créée pour chaque order
- [ ] Panier vidé
- [ ] `order_no` généré au format `TD-YYYYMMDD-XXXX`

### 2. Test Checkout - Paiement MoMo (Mode démo)

**Request** :
```json
{
  "payment_method": "momo",
  "delivery_address": "123 Avenue de la République",
  "d_lat": "-4.2634",
  "d_lng": "15.2429"
}
```

**Response attendue** :
```json
{
  "status": true,
  "payment": {
    "id": 2,
    "status": "PENDING",
    "amount": 15000,
    "currency": "XAF",
    "provider": "momo"
  },
  "requires_external_payment": true,
  "payment_payload": {
    "provider_reference": "DEMO-...",
    "meta": {
      "demo": true,
      "provider": "momo"
    },
    "redirect_url": null
  }
}
```

**Vérifications** :
- [ ] Payment créé avec `status = PENDING`
- [ ] `provider_reference` généré
- [ ] Pas de commande créée (attente du callback)
- [ ] Panier conservé

### 3. Test Callback MoMo (Simulation)

**Endpoint** : `POST /api/payments/callback/momo`

**Request** :
```json
{
  "reference": "DEMO-2-1701878400",
  "status": "SUCCESS",
  "amount": 15000,
  "currency": "XAF",
  "transaction_id": "TXN-ABC123"
}
```

**cURL** :
```bash
curl -X POST http://localhost:8000/api/payments/callback/momo \
  -H "Content-Type: application/json" \
  -d '{
    "reference": "DEMO-2-1701878400",
    "status": "SUCCESS",
    "amount": 15000,
    "transaction_id": "TXN-ABC123"
  }'
```

**Response attendue** :
```json
{
  "message": "OK"
}
```

**Vérifications en base** :
```sql
-- Vérifier le paiement
SELECT id, status, provider_reference, order_id FROM payments WHERE id = 2;
-- Status doit être PAID

-- Vérifier la commande créée
SELECT * FROM orders WHERE payment_id = 2 OR id IN (
  SELECT order_id FROM payments WHERE id = 2
);
-- Doit exister

-- Vérifier les livraisons
SELECT * FROM deliveries WHERE order_id IN (
  SELECT id FROM orders WHERE payment_id = 2 OR id IN (
    SELECT order_id FROM payments WHERE id = 2
  )
);
```

### 4. Test Récupération Statut Paiement

**Endpoint** : `GET /api/payments/{payment_id}`

**cURL** :
```bash
curl -X GET http://localhost:8000/api/payments/1 \
  -H "Accept: application/json" \
  --cookie "laravel_session={session_cookie}"
```

**Response attendue** :
```json
{
  "status": true,
  "data": {
    "id": 1,
    "status": "PAID",
    "amount": 15000,
    "currency": "XAF",
    "provider": "cash",
    "provider_reference": null,
    "order_id": 1,
    "meta": {
      "cash_on_delivery": true
    },
    "created_at": "2025-01-05T10:00:00Z",
    "updated_at": "2025-01-05T10:00:00Z"
  }
}
```

## Tests Frontend

### 1. Test Page Checkout

**URL** : `http://localhost:8000/checkout`

**Scénario** :
1. Se connecter en tant qu'utilisateur
2. Ajouter des produits au panier
3. Aller sur `/checkout`
4. Vérifier l'affichage :
   - [ ] Adresse de livraison (Google Maps)
   - [ ] Résumé du panier
   - [ ] Totaux calculés (sous-total, taxe, livraison, total)
   - [ ] Options de paiement (Cash, MoMo, PayPal)
   - [ ] Champ pour pourboire driver
   - [ ] Champ pour code promo

### 2. Test Checkout Cash

**Scénario** :
1. Sélectionner "Paiement à la livraison"
2. Remplir l'adresse de livraison
3. Cliquer sur "Passer la commande"
4. Vérifications :
   - [ ] Toast de succès affiché
   - [ ] Redirection vers `/track-order/{order_no}`
   - [ ] Panier vidé (badge à 0)
   - [ ] Commande visible dans "Mes commandes"

### 3. Test Checkout MoMo (Mode démo)

**Scénario** :
1. Sélectionner "Mobile Money"
2. Remplir l'adresse
3. Cliquer sur "Passer la commande"
4. Vérifications :
   - [ ] Toast "Redirection vers la page de paiement..."
   - [ ] Message d'instructions MoMo (en mode démo)
   - [ ] Polling du statut activé
   - [ ] Pas de redirection (pas de `redirect_url` en démo)

### 4. Test Polling Statut Paiement

**Scénario** :
1. Après initiation d'un paiement MoMo
2. Ouvrir la console navigateur (F12)
3. Vérifier les appels API :
   - [ ] Appels `GET /api/payments/{id}` toutes les 2 secondes
   - [ ] Arrêt du polling si `status = PAID` ou `FAILED`
4. Simuler un callback (via curl backend)
5. Vérifier :
   - [ ] Polling détecte le changement de statut
   - [ ] Redirection automatique vers `/track-order/{order_no}`

## Tests Base de Données

### Vérification des données

**1. Table `payments`** :
```sql
SELECT 
  id,
  user_id,
  order_id,
  provider,
  provider_reference,
  status,
  amount,
  currency,
  created_at
FROM payments
ORDER BY id DESC
LIMIT 10;
```

**Vérifications** :
- [ ] `status` : PENDING, PAID, ou FAILED
- [ ] `provider` : cash, momo, ou paypal
- [ ] `amount` : Montant correct
- [ ] `provider_reference` : Présent pour paiements externes

**2. Table `orders`** :
```sql
SELECT 
  id,
  user_id,
  order_no,
  payment_method,
  payment_status,
  status,
  total,
  delivery_address,
  created_at
FROM orders
ORDER BY id DESC
LIMIT 10;
```

**Vérifications** :
- [ ] `order_no` : Format `TD-YYYYMMDD-XXXX`
- [ ] `payment_method` : Correspond au provider
- [ ] `payment_status` : `paid` ou `pending`
- [ ] `total` : Montant correct

**3. Table `deliveries`** :
```sql
SELECT 
  d.id,
  d.order_id,
  d.driver_id,
  d.status,
  d.created_at,
  o.order_no
FROM deliveries d
JOIN orders o ON d.order_id = o.id
ORDER BY d.id DESC
LIMIT 10;
```

**Vérifications** :
- [ ] Une livraison par commande
- [ ] `status` : `pending` initialement
- [ ] `driver_id` : NULL initialement

**4. Table `carts`** :
```sql
SELECT * FROM carts WHERE user_id = {user_id};
```

**Vérifications** :
- [ ] Panier vidé après paiement cash réussi
- [ ] Panier vidé après callback MoMo réussi
- [ ] Panier conservé si paiement en attente

## Tests d'erreurs

### 1. Panier vide

**Request** :
```json
{
  "payment_method": "cash",
  "delivery_address": "123 Rue Test"
}
```

**Response attendue** :
```json
{
  "status": false,
  "message": "Le panier est vide."
}
```

### 2. Données invalides

**Request** :
```json
{
  "payment_method": "invalid",
  "delivery_address": ""
}
```

**Response attendue** :
```json
{
  "status": false,
  "message": "Données invalides",
  "errors": {
    "payment_method": ["Le champ payment method doit être l'un des suivants : cash, momo, paypal."],
    "delivery_address": ["Le champ delivery address est obligatoire."]
  }
}
```

### 3. Utilisateur non authentifié

**Request** : Sans cookie de session ni token

**Response attendue** :
```json
{
  "status": false,
  "message": "Non authentifié"
}
```

### 4. Callback avec référence invalide

**Request** :
```json
{
  "reference": "INVALID-REF",
  "status": "SUCCESS"
}
```

**Response attendue** :
```json
{
  "message": "Error processing callback",
  "error": "Paiement non trouvé pour la référence: INVALID-REF"
}
```

## Tests de performance

### 1. Temps de réponse checkout

**Objectif** : < 2 secondes

**Test** :
```bash
time curl -X POST http://localhost:8000/api/checkout \
  -H "Content-Type: application/json" \
  --cookie "laravel_session={session}" \
  -d '{...}'
```

### 2. Temps de traitement callback

**Objectif** : < 1 seconde

**Test** :
```bash
time curl -X POST http://localhost:8000/api/payments/callback/momo \
  -H "Content-Type: application/json" \
  -d '{...}'
```

## Checklist complète

### Backend
- [ ] Migration `payments` exécutée
- [ ] Modèle `Payment` fonctionnel
- [ ] `CheckoutService` opérationnel
- [ ] `PaymentService` opérationnel
- [ ] Routes API configurées
- [ ] Callbacks fonctionnels
- [ ] Gestion d'erreurs complète

### Frontend
- [ ] Page checkout accessible
- [ ] Formulaire fonctionnel
- [ ] Intégration Google Maps
- [ ] Appels API corrects
- [ ] Gestion des erreurs
- [ ] Polling statut paiement
- [ ] Redirections correctes

### Intégration
- [ ] Workflow cash complet
- [ ] Workflow MoMo (démo) complet
- [ ] Callbacks testés
- [ ] Base de données cohérente
- [ ] Logs fonctionnels

### Sécurité
- [ ] Authentification requise
- [ ] CSRF protection
- [ ] Validation des données
- [ ] Vérification des signatures (callbacks)

## Commandes utiles

```bash
# Vider les logs
> storage/logs/laravel.log

# Voir les dernières lignes
tail -f storage/logs/laravel.log

# Tester une route
php artisan route:list | grep checkout

# Vérifier les migrations
php artisan migrate:status

# Tester avec tinker
php artisan tinker
>>> $user = App\User::first();
>>> $cart = App\Cart::where('user_id', $user->id)->get();
>>> $cart->count();
```

---

**Dernière mise à jour** : 2025-01-XX
**Version** : 1.0

