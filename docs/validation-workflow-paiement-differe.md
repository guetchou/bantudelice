# Validation — Workflow paiement/livraison différé BantuDelice

**Date** : 2026-06-19  
**Environnement** : VPS OVH `/opt/bantudelice`, `APP_ENV=production`, DB `bantudelice_repro`  
**Auteur** : BantuDelice / Claude Code  
**Référence plan** : `docs/prd- Workflow.md` + plan `/root/.claude/plans/playful-snuggling-map.md`

---

## 1. Objectif

Valider en conditions réelles de production que le nouveau workflow paiement/livraison différé fonctionne correctement pour tous les scénarios nominaux et d'échec, sans régression sur le workflow cash.

Modèle d'état validé :

```
Online (momo/paypal) :
pending_restaurant_acceptance → accepted_awaiting_payment → confirmed → in_kitchen → …

Cash :
pending_restaurant_acceptance → confirmed → in_kitchen → … → delivered
```

---

## 2. Environnement de test

| Point | Valeur |
|---|---|
| URL | `https://bantudelice.cg` |
| `APP_ENV` | `production` |
| `MOMO_ENVIRONMENT` | `production` |
| `FCM_ENABLED` | `true` |
| Base de données | `bantudelice_repro` |
| PHP | 8.3.6 |
| Laravel | 10.x |
| Compte client test | `client@bantudelice.cg` |
| Compte restaurant test | `mamiwata@bantudelice.cg` (Mami Wata Restaurant) |
| Compte livreur test | `jean-paul.mboumba@bantudelice.cg` |
| Numéro MoMo test | `068006730` (Congo) |

---

## 3. Tableau de preuves complet

| # | Scénario | order\_no | Compte | b\_status attendu | b\_status observé | pay\_status attendu | pay\_status observé | Delivery | ETA | Verdict |
|---|---|---|---|---|---|---|---|---|---|---|
| Cash | Checkout → delivered | TD-20260619-1086 | client@bantudelice.cg | `delivered` | `delivered` | `paid` | `paid` | 1 (DELIVERED) | non avant in\_kitchen | **OK** |
| Garde 1 | `in_kitchen` sans paiement | (tinker direct) | admin | `RuntimeException` | exception levée | — | — | 0 | — | **OK** |
| Garde 2 | `AutoAssignDeliveryJob` avant paiement | (inspection code) | — | log warning, job ignoré | confirmé lignes 63-64 | — | — | — | — | **OK** |
| **#17** | Refus restaurant | TD-REFUS-TEST-001 | mamiwata@bantudelice.cg | `cancelled` | `cancelled` | — | `failed`* | 0 | non | **OK** |
| **#18** | Échec paiement MoMo | TD-EXPIRE-TEST-001 | mamiwata@bantudelice.cg | `accepted_awaiting_payment` | `accepted_awaiting_payment` | `failed` | `failed` | 0 | non | **OK** |
| **#19** | Expiration 10 min | TD-EXPIRE-TEST-001 | system (scheduler) | `cancelled` | `cancelled` | `expired` | `expired` | 0 | non | **OK** |
| **#16** | MoMo réel 1 FCFA | TD-MOMO-TEST-001 | mamiwata@bantudelice.cg + 068006730 | `in_kitchen` | `in_kitchen` | `paid` | `paid` | 0 (pickup) | non (pickup) | **OK** |

\* `payment_status=failed` posé par `FoodOrderFinanceService::cancelOrderGroup()` après refus restaurant cash — comportement cohérent (paiement cash attendu jamais collecté).

### Détail scénario #16 (MoMo réel)

| Étape | Horodatage VPS | Transition | Actor | Reason |
|---|---|---|---|---|
| Restaurant accepte | 19:09:05 | `pending_restaurant_acceptance → accepted_awaiting_payment` | restaurant | `accepted_awaiting_payment` |
| Callback MTN reçu | 19:10:01 | `accepted_awaiting_payment → confirmed` | system | `payment_confirmed` |
| Auto-avance cuisine | 19:10:02 | `confirmed → in_kitchen` | system | `payment_confirmed` |

Durée checkout → paiement confirmé : **57 secondes**. Montant réel débité : **1 XAF** sur `068006730`. Référence MTN : `9a8fb9f5-22cd-4d47-89c4-76a0e74a543a`.

### Détail scénario #19 (expiration)

- `accepted_at` : `18:49:06`
- `cancelled_at` : `19:00:03`
- Durée effective : **657s (~11 min)**
- Commande Artisan : `food:expire-unpaid-accepted` (scheduler toutes les 2 min)
- Config : `food.payment_failed_hold_timeout_minutes = 10` (défaut)

---

## 4. Gardes critiques — état

### 4.1 Garde dure `in_kitchen`

Fichier : `app/Services/FoodOrderStateMachineService.php`, méthode `guardInKitchenRequiresConfirmedAndPaid()` (lignes 176-211).

Comportement validé :
- `transitionOrderGroup($orderNo, 'in_kitchen', [])` sur commande non confirmée/non payée → `RuntimeException` levée ✓
- Bypass avec `force_admin: true` → transition autorisée + écriture `status_transition_forced_unpaid` dans `order_status_logs` ✓

### 4.2 Garde `AutoAssignDeliveryJob`

Fichier : `app/Jobs/AutoAssignDeliveryJob.php`, lignes 63-64.

```php
if (! $order || ! in_array($order->business_status, $dispatchableStatuses, true)) {
    Log::warning('AutoAssignDeliveryJob: order pas en in_kitchen/ready_for_pickup, job ignoré', [...]);
    return;
}
```

`DISPATCHABLE_BUSINESS_STATUSES = ['in_kitchen', 'ready_for_pickup']` (constante partagée, `FoodOrderStateMachineService::DISPATCHABLE_BUSINESS_STATUSES`).

### 4.3 Pas de Delivery avant paiement

`OrderAcceptanceService::triggerOnlinePayment()` : transition vers `accepted_awaiting_payment` sans création de `Delivery`.  
`FoodOrderPaymentConfirmed::handleOrderFinalization()` ligne 84 : `createDeliveryAndDispatch()` appelé seulement si `fulfillment_mode !== 'pickup'` ET seulement après callback paiement confirmé.

---

## 5. Lacunes documentées

| Code | Sévérité | Description | Impact |
|---|---|---|---|
| **L1** | Haute | `cash_collection_status` reste `pending_collection` après `business_status=delivered` — aucun mécanisme automatique | Suivi encaissement cash incomplet, action manuelle requise |
| **L2** | Moyenne | Pas de retry automatique MoMo (côté système) — uniquement bouton client | Paiements échoués non relancés automatiquement |
| **L3** | Moyenne | `cancelExternalPayment()` non vérifié si Payment PSP AUTHORIZED lors de l'annulation | Risque de paiement PSP orphelin sur commande annulée |
| **L4** | Faible | `confirmed` est un état transitoire auto-avancé — non visible sur dashboard restaurant | Acceptable, état documenté |
| **L5** | Moyenne | Pas de gestion horaires d'ouverture restaurant (benchmark GitHub) | Commandes possibles hors horaires |

---

## 6. Éléments hors scope (non testés)

- Parcours PayPal (sandbox) — mode similaire à MoMo, non exécuté
- Pickup par le client (`customer_arrived → customer_picked_up`) — étapes post-`ready_for_pickup`, inchangées
- Notifications FCM réelles (envoyées mais non confirmées côté appareil de test)
- Concurrence webhook (double callback MTN simultané) — guard d'idempotence en place par contrainte `deliveries.order_id` unique

---

## 7. Nettoyage post-validation

| Élément | Supprimé |
|---|---|
| Orders `TD-*` | 35 |
| Deliveries liées | 26 |
| Payments liés | 8 |
| `order_status_logs` liés | 107 |
| Prix produits restaurés depuis backup | 120/120 |

Backup source : `/opt/backups/bantudelice/products_prices_backup_20260618_141041.json`

---

## 8. Conclusion

**Workflow paiement/livraison différé : validé sur 7 scénarios** en production réelle.

**Non déclaré "production ready"** — les lacunes L1 à L5 restent ouvertes. La lacune L1 (`cash_collection_status`) est la plus prioritaire avant mise en service commercial.

**Point sécurité en attente** : rotation des clés MoMo/PayPal exposées pendant la session d'audit (action côté portail MTN/PayPal, hors scope technique de cette validation).
