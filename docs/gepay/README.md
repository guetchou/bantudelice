# GePay Gateway

GePay est la passerelle de paiement réutilisable de l’écosystème. Le lot initial expose une API uniforme pour MTN MoMo Collection et Disbursement, avec authentification HMAC, idempotence, journal des transactions, webhooks et rapprochement.

## API v1

- `GET /api/gepay/v1/health`
- `GET /api/gepay/v1/client`
- `POST /api/gepay/v1/collections`
- `POST /api/gepay/v1/disbursements`
- `GET /api/gepay/v1/transactions/{reference}`
- `POST /api/gepay/v1/transactions/{reference}/refresh`
- `POST /api/gepay/v1/webhooks/mtn`

## Authentification cliente

Chaque requête privée contient :

```text
X-GePay-Key: gpk_...
X-GePay-Timestamp: 1710000000
X-GePay-Signature: HMAC_SHA256(...)
Idempotency-Key: référence-unique-du-client
```

Chaîne canonique :

```text
{timestamp}\n{METHOD}\n{request_uri}\n{sha256(raw_body)}
```

Créer un client :

```bash
php artisan gepay:client-create "BantuDelice" \
  --capability=collection \
  --capability=disbursement
```

## Configuration MTN

```env
GEPAY_ENABLED=true
GEPAY_MTN_ENABLED=true
GEPAY_MTN_ENVIRONMENT=production
GEPAY_MTN_TARGET_ENVIRONMENT=mtncongo
GEPAY_MTN_CALLBACK_URL=https://pay.example.cg/api/gepay/v1/webhooks/mtn

GEPAY_MTN_COLLECTIONS_SUBSCRIPTION_KEY=
GEPAY_MTN_COLLECTIONS_API_USER=
GEPAY_MTN_COLLECTIONS_API_KEY=

GEPAY_MTN_DISBURSEMENTS_SUBSCRIPTION_KEY=
GEPAY_MTN_DISBURSEMENTS_API_USER=
GEPAY_MTN_DISBURSEMENTS_API_KEY=
```

`X-Target-Environment` est envoyé aussi bien lors de la création des tokens que lors des opérations MTN.

## Exemple Collection

```json
{
  "amount": 5000,
  "currency": "XAF",
  "phone": "24206XXXXXXX",
  "provider": "mtn_momo",
  "external_reference": "ORDER-10045",
  "payer_message": "Commande 10045",
  "payee_note": "Paiement commande"
}
```

## Rapprochement

```bash
php artisan gepay:reconcile --limit=100
```

Le scheduler exécute ce rapprochement chaque minute. Les statuts inconnus restent bloqués jusqu’à confirmation du fournisseur : GePay ne transforme jamais un timeout en échec définitif.

## Limites du lot 0.1

- Remittance est préparé dans la configuration mais non activé tant que le contrat et les chemins MTN Congo ne sont pas confirmés.
- Le portefeuille/ledger multi-tenant et le reversement automatique vers les applications clientes arrivent au lot suivant.
- Le tableau de bord d’administration GePay n’est pas encore inclus.
