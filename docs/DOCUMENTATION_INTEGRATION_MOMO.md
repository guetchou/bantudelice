# DOCUMENTATION - INTÉGRATION MOBILE MONEY (MoMo)

## Vue d'ensemble

Ce document décrit comment intégrer Mobile Money (MoMo) dans le module de paiement de TheDrop247. L'architecture est conçue pour être générique et adaptable à différents opérateurs (MTN, Airtel, Orange Money, etc.).

## Architecture

### Structure des fichiers

```
app/
├── Services/
│   ├── PaymentService.php      # Service principal de paiement
│   └── CheckoutService.php     # Service de checkout
├── Http/Controllers/Api/
│   ├── CheckoutController.php  # Endpoint de checkout
│   ├── PaymentController.php   # Gestion des paiements
│   └── PaymentCallbackController.php  # Webhooks PSP
└── Payment.php                 # Modèle Payment

routes/
└── api.php                     # Routes API

database/migrations/
└── 2025_12_05_120000_create_payments_table.php
```

## Configuration

### Variables d'environnement

Ajoutez ces variables dans votre fichier `.env` :

```env
# ==========================================
# MOBILE MONEY (MoMo)
# ==========================================
MOMO_API_KEY=votre_cle_api_momo
MOMO_API_SECRET=votre_secret_api_momo
MOMO_API_URL=https://api.momo.cg/v1
MOMO_ENVIRONMENT=sandbox
```

**Note** : 
- `MOMO_ENVIRONMENT` peut être `sandbox` (test) ou `production` (live)
- En mode sandbox, utilisez les credentials de test fournis par votre opérateur
- En production, utilisez les credentials réels

### Obtention des credentials

1. **MTN Mobile Money** : https://momodeveloper.mtn.com/
2. **Airtel Money** : Contactez Airtel Business
3. **Orange Money** : Contactez Orange Business

## Flux de paiement MoMo

### 1. Initiation du paiement

**Endpoint** : `POST /api/checkout`

**Request Body** :
```json
{
  "payment_method": "momo",
  "delivery_address": "123 Avenue de la République, Brazzaville",
  "d_lat": "-4.2634",
  "d_lng": "15.2429",
  "driver_tip": 500,
  "voucher_code": null
}
```

**Response** :
```json
{
  "status": true,
  "payment": {
    "id": 123,
    "status": "PENDING",
    "amount": 15000,
    "currency": "XAF",
    "provider": "momo"
  },
  "requires_external_payment": true,
  "payment_payload": {
    "provider_reference": "MOMO-123-1701878400",
    "meta": {
      "momo_reference": "MOMO-123-1701878400",
      "payment_url": "https://pay.momo.cg/pay/...",
      "qr_code": "data:image/png;base64,...",
      "ussd_code": "*123#",
      "amount": 15000,
      "currency": "XAF"
    },
    "redirect_url": "https://pay.momo.cg/pay/..."
  }
}
```

### 2. Redirection vers le PSP

Le frontend redirige l'utilisateur vers `payment_payload.redirect_url` ou affiche les instructions (QR code, USSD, etc.).

### 3. Callback du PSP

**Endpoint** : `POST /api/payments/callback/momo`

**Payload typique** :
```json
{
  "reference": "MOMO-123-1701878400",
  "transaction_id": "TXN-ABC123",
  "status": "SUCCESS",
  "amount": 15000,
  "currency": "XAF",
  "phone": "+242061234567",
  "signature": "abc123def456..."
}
```

**Vérification** :
- La signature est vérifiée avec `MOMO_API_SECRET`
- Le paiement est retrouvé via `reference`
- Le statut est mis à jour
- La commande est créée automatiquement si le paiement réussit

### 4. Confirmation côté client

Le frontend peut poller le statut via :
**Endpoint** : `GET /api/payments/{payment_id}`

## Implémentation dans PaymentService

### Méthode `initiateMoMoPayment()`

Cette méthode :
1. Vérifie la configuration MoMo
2. Génère une référence unique
3. Prépare les données de paiement
4. Appelle l'API MoMo
5. Retourne les informations de paiement

**Exemple de code** :
```php
protected function initiateMoMoPayment($payment, array $checkoutData = []): array
{
    $momoApiKey = env('MOMO_API_KEY');
    $momoApiSecret = env('MOMO_API_SECRET');
    
    if (!$momoApiKey || !$momoApiSecret) {
        // Mode démo
        return $this->getDemoResponse($payment);
    }
    
    $reference = 'MOMO-' . $payment->id . '-' . time();
    $callbackUrl = route('api.payments.callback', ['provider' => 'momo']);
    
    $requestData = [
        'amount' => $payment->amount,
        'currency' => $payment->currency,
        'reference' => $reference,
        'payer' => [
            'phone' => $payment->user->phone,
            'name' => $payment->user->name,
        ],
        'callback_url' => $callbackUrl,
        'description' => 'Commande BantuDelice #' . $payment->id,
    ];
    
    $response = $this->callMoMoAPI('/payments', $requestData, $momoApiKey, $momoApiSecret);
    
    return [
        'provider_reference' => $response['reference'],
        'meta' => [
            'momo_reference' => $response['reference'],
            'payment_url' => $response['payment_url'],
            'qr_code' => $response['qr_code'],
        ],
        'redirect_url' => $response['payment_url'],
    ];
}
```

### Méthode `callMoMoAPI()`

Cette méthode gère l'appel HTTP à l'API MoMo avec authentification par signature.

**Signature HMAC-SHA256** :
```php
$signatureString = $apiKey . $timestamp . $nonce . json_encode($data);
$signature = hash_hmac('sha256', $signatureString, $apiSecret);
```

**Headers requis** :
```
X-API-Key: {MOMO_API_KEY}
X-Timestamp: {timestamp}
X-Nonce: {nonce}
X-Signature: {signature}
Content-Type: application/json
```

### Méthode `verifyMoMoSignature()`

Vérifie la signature des callbacks pour garantir l'authenticité.

```php
protected function verifyMoMoSignature(array $payload): bool
{
    $apiSecret = env('MOMO_API_SECRET');
    $receivedSignature = $payload['signature'] ?? null;
    
    $signatureString = $payload['reference'] . $payload['amount'] . $payload['status'];
    $expectedSignature = hash_hmac('sha256', $signatureString, $apiSecret);
    
    return hash_equals($expectedSignature, $receivedSignature);
}
```

## Adaptation selon l'opérateur

### MTN Mobile Money

**Documentation** : https://momodeveloper.mtn.com/

**Endpoints typiques** :
- Sandbox : `https://sandbox.momodeveloper.mtn.com/`
- Production : `https://api.momodeveloper.mtn.com/`

**Différences** :
- Utilise OAuth2 pour l'authentification
- Nécessite un `subscription_key`
- Format de callback différent

### Airtel Money

**Documentation** : Contactez Airtel Business

**Caractéristiques** :
- API REST standard
- Authentification par API Key/Secret
- Callbacks via webhooks

### Orange Money

**Documentation** : Contactez Orange Business

**Caractéristiques** :
- API REST avec OAuth
- Support des paiements USSD
- QR codes pour paiement

## Gestion des erreurs

### Erreurs courantes

1. **Configuration manquante**
   - Message : "Configuration MoMo manquante"
   - Solution : Vérifier `.env`

2. **Signature invalide**
   - Message : "Signature de callback invalide"
   - Solution : Vérifier `MOMO_API_SECRET` et la méthode de signature

3. **Référence introuvable**
   - Message : "Paiement non trouvé pour la référence"
   - Solution : Vérifier que la référence est bien stockée

4. **Timeout API**
   - Message : "Erreur lors de l'appel API MoMo"
   - Solution : Augmenter le timeout ou vérifier la connectivité

### Logs

Tous les appels et erreurs sont loggés dans `storage/logs/laravel.log` :

```php
Log::info('Initiating MoMo payment', ['payment_id' => $payment->id]);
Log::error('MoMo API error', ['error' => $e->getMessage()]);
```

## Tests

### Mode démo

Sans configuration MoMo, le système fonctionne en mode démo :
- Génère des références factices
- Retourne des instructions de test
- Permet de tester le workflow sans appels réels

### Tests avec sandbox

1. Configurer les credentials sandbox dans `.env`
2. Tester l'initiation de paiement
3. Simuler un callback avec curl :

```bash
curl -X POST https://votre-domaine.com/api/payments/callback/momo \
  -H "Content-Type: application/json" \
  -d '{
    "reference": "MOMO-123-1701878400",
    "status": "SUCCESS",
    "amount": 15000,
    "signature": "abc123..."
  }'
```

### Tests end-to-end

1. **Créer un panier** avec des produits
2. **Aller sur /checkout**
3. **Sélectionner "Mobile Money"**
4. **Remplir les informations de livraison**
5. **Soumettre le checkout**
6. **Vérifier la redirection** vers le PSP
7. **Simuler le paiement** (sandbox)
8. **Vérifier le callback** et la création de commande

## Sécurité

### Bonnes pratiques

1. **Ne jamais exposer les secrets** dans le code
2. **Vérifier toujours les signatures** des callbacks
3. **Utiliser HTTPS** en production
4. **Whitelister les IPs** du PSP pour les callbacks (optionnel)
5. **Logger tous les callbacks** pour audit

### Protection des callbacks

Vous pouvez ajouter une vérification d'IP dans `PaymentCallbackController` :

```php
$allowedIPs = ['192.168.1.1', '10.0.0.1']; // IPs du PSP
if (!in_array($request->ip(), $allowedIPs)) {
    abort(403, 'IP non autorisée');
}
```

## Monitoring

### Métriques à surveiller

1. **Taux de succès** : Nombre de paiements réussis / Total
2. **Temps de réponse** : Temps d'appel à l'API MoMo
3. **Erreurs** : Nombre d'erreurs par type
4. **Callbacks manquants** : Paiements en PENDING trop longtemps

### Dashboard recommandé

- Nombre de paiements MoMo par jour
- Montant total traité
- Taux de succès/échec
- Temps moyen de traitement

## Support

### En cas de problème

1. Vérifier les logs : `storage/logs/laravel.log`
2. Vérifier la configuration : `.env`
3. Tester avec curl les endpoints
4. Contacter le support de l'opérateur MoMo

### Ressources

- Documentation MTN : https://momodeveloper.mtn.com/
- Support technique : support@thedrop247.com

## Checklist d'intégration

- [ ] Credentials MoMo obtenus (sandbox et production)
- [ ] Variables `.env` configurées
- [ ] Tests sandbox réussis
- [ ] Callbacks testés et vérifiés
- [ ] Signature des callbacks validée
- [ ] Gestion d'erreurs implémentée
- [ ] Logs configurés
- [ ] Tests end-to-end effectués
- [ ] Documentation mise à jour
- [ ] Déploiement en production validé

---

**Dernière mise à jour** : 2025-01-XX
**Version** : 1.0

