# Escalade MTN Disbursement Temps Reel

- Date: 2026-03-30
- Périmètre: décaissements admin payout restaurants et livreurs, fallback bulk CSV, activation MTN Disbursement API production.

## Contexte

- Le décaissement temps réel MTN est déjà branché dans `app/Http/Controllers/admin/PayoutController.php` via `App\Services\MobileMoneyService::initiateDisbursement()`.
- En production Congo, les vérifications préparatoires répondent mais l'appel de transfert reste refusé.
- Pour ne pas bloquer l'exploitation, un export CSV bulk MTN a été ajouté dans l'admin payout comme solution de continuité.

## Etat constaté

- Token disbursement: réponse OK.
- Solde / disponibilité de compte: réponse OK.
- Validation account holder: réponse OK.
- `POST /disbursement/v1_0/transfer`: refus provider, avec cas observé `403`.

## Lecture opérationnelle

- Le tenant MTN Congo semble provisionné pour consulter certains prérequis, mais pas encore autorisé à exécuter le `transfer` API en temps réel.
- Le bulk payment via portail MTN + CSV reste le fallback exploitable tant que l'autorisation `transfer` n'est pas explicitement activée.

## Demandes à adresser à MTN

1. Confirmer par écrit que la ligne MoMo Congo associée au marchand est bien autorisée au produit `Disbursement API` en production, pas seulement au bulk payment web.
2. Confirmer que la `subscription key` utilisée est bien une clé de production dédiée `disbursements`, distincte de `collections`.
3. Confirmer que `MOMO_DISBURSEMENTS_API_USER` et `MOMO_DISBURSEMENTS_API_KEY` sont rattachés au même tenant de production que la clé d'abonnement.
4. Confirmer que l'appel `POST /disbursement/v1_0/transfer` est autorisé pour ce tenant sur l'environnement Congo réellement visé.
5. Confirmer si une whitelist IP est requise pour les appels production et, si oui, enregistrer les IP sortantes du serveur BantuDelice.
6. Confirmer si le header callback doit être whiteliste séparément, ou si le décaissement doit être lancé sans callback tant que l'URL n'est pas validée.
7. Fournir l'interprétation exacte du `403` renvoyé sur `transfer` pour ce tenant.
8. Fournir un exemple de requête live validée par MTN Congo pour un décaissement B2C local.

## Informations techniques à transmettre à MTN

- Application: BantuDelice
- Cas d'usage: payouts restaurants et livreurs depuis l'admin
- Endpoint testé: `POST /disbursement/v1_0/transfer`
- Endpoints déjà validés:
  - token disbursement
  - balance
  - accountholder active
- Résultat bloquant:
  - `transfer` refusé alors que le compte et le bénéficiaire répondent

## Variables de configuration concernées

- `MOMO_ENVIRONMENT`
- `MOMO_TARGET_ENVIRONMENT`
- `MOMO_DISBURSEMENTS_SUBSCRIPTION_KEY`
- `MOMO_DISBURSEMENTS_PRIMARY_KEY`
- `MOMO_DISBURSEMENTS_SECONDARY_KEY`
- `MOMO_DISBURSEMENTS_API_USER`
- `MOMO_DISBURSEMENTS_API_KEY`
- `MOMO_CALLBACK_URL`
- `MOMO_USE_CALLBACK_HEADER`

## Preuves code à citer

- `app/Services/MobileMoneyService.php`
- `app/Http/Controllers/admin/PayoutController.php`
- `resources/views/admin/payouts/restaurant_payout.blade.php`
- `resources/views/admin/payouts/driver_payout.blade.php`

## Contournement opérationnel mis en place

- Export CSV bulk MTN ajouté sur les écrans:
  - `restaurant_payout`
  - `driver_payout`
- Format exporté:
  - `Payee Name`
  - `MSISDN`
  - `Amount (FCFA)`
- Usage:
  - exporter les demandes `pending`
  - uploader le fichier dans le portail MTN bulk payment
  - rapprocher ensuite les paiements dans BantuDelice via référence manuelle si nécessaire

## Brouillon de message à envoyer à MTN

Objet: Activation requise du `transfer` API sur notre tenant MoMo Disbursement Congo

Bonjour,

Nous intégrons le produit MTN MoMo Disbursement pour des décaissements B2C locaux au Congo depuis notre plateforme BantuDelice.

Nos tests de production montrent:

- token disbursement: OK
- balance / disponibilité du compte: OK
- validation beneficiary account holder: OK
- `POST /disbursement/v1_0/transfer`: refusé (`403`)

Pouvez-vous confirmer:

1. que notre tenant de production est bien activé pour le `Disbursement API transfer` et pas uniquement pour le bulk payment web,
2. que notre clé `disbursements` de production est correcte et active,
3. si une whitelist IP est requise pour autoriser `transfer`,
4. si notre callback URL doit être validée séparément,
5. et la cause exacte du `403` renvoyé sur `transfer` pour notre compte.

Nous pouvons transmettre les identifiants de requête et nos IP sortantes via canal sécurisé si nécessaire.

Merci.

