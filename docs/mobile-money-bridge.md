# Passerelle Mobile Money BantuDelice

- Objectif: permettre à une autre application d'initier et suivre un paiement Mobile Money sans réimplémenter la logique MTN/Airtel, les statuts et la réconciliation.
- Base URL: `/api/bridge/mobile-money`
- Sécurité: signature HMAC par application cliente

## Endpoints

### 1. Créer un paiement

- `POST /api/bridge/mobile-money/payments`

Payload JSON:

```json
{
  "external_reference": "ORDER-2026-0001",
  "amount": 1500,
  "currency": "XAF",
  "phone": "068006730",
  "operator": "auto",
  "customer_name": "Jean Client",
  "callback_url": "https://mon-app.example.com/webhooks/mobile-money",
  "metadata": {
    "app": "food-app",
    "order_id": "ORDER-2026-0001"
  }
}
```

### 2. Lire un statut

- `GET /api/bridge/mobile-money/payments/{gateway_reference}`
- exemple: `GET /api/bridge/mobile-money/payments/MMB-00000042`
- option: `?reconcile=1`

### 3. Forcer une réconciliation

- `POST /api/bridge/mobile-money/payments/{gateway_reference}/reconcile`

## Signature requise

Headers:

- `X-Bridge-Key`
- `X-Bridge-Timestamp`
- `X-Bridge-Signature`

Chaîne à signer:

```text
{timestamp}\n{METHOD}\n{PATH}\n{raw_body}
```

Algorithme:

- `HMAC-SHA256`

## Exemple Node.js

```js
import crypto from "crypto";

const key = process.env.MM_BRIDGE_KEY;
const secret = process.env.MM_BRIDGE_SECRET;
const method = "POST";
const path = "/api/bridge/mobile-money/payments";
const body = JSON.stringify({
  external_reference: "ORDER-2026-0001",
  amount: 1500,
  currency: "XAF",
  phone: "068006730",
  operator: "auto"
});

const timestamp = String(Math.floor(Date.now() / 1000));
const signature = crypto
  .createHmac("sha256", secret)
  .update([timestamp, method, path, body].join("\n"))
  .digest("hex");
```

## Variables de configuration

- `MOBILE_MONEY_BRIDGE_ENABLED=true`
- `MOBILE_MONEY_BRIDGE_CLIENT_KEY=bridge-default`
- `MOBILE_MONEY_BRIDGE_CLIENT_SECRET=change-me`
- `MOBILE_MONEY_BRIDGE_CLIENT_NAME=Default Bridge Client`
- `MOBILE_MONEY_BRIDGE_SERVICE_EMAIL=payments-bridge@bantudelice.cg`
- `MOBILE_MONEY_BRIDGE_SERVICE_NAME=BantuDelice Payments Bridge`
- `MOBILE_MONEY_BRIDGE_SERVICE_PHONE=060000000`
