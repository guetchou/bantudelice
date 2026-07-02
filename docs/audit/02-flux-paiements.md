# 02 — Flux de paiements

## Flux réel

`PaymentService::startManagedPayment()` crée un paiement `PENDING`, puis `PaymentGatewayFactory` appelle MTN, Airtel, PayPal, cash/démo ou GePay. Le callback commun passe par `PaymentCallbackController`. Food est traité directement ; Colis et Transport passent par une file. `markPaymentAsPaid()` émet ensuite `PaymentConfirmed`, consommé par les trois domaines et par le miroir financier.

## Anomalies

### PAY-001 — Critique — Confirmation rejouable

- **Fichiers :** `app/Services/PaymentService.php:187-361`.
- **Fait :** aucun verrouillage du paiement ni transition conditionnelle atomique avant l’émission de `PaymentConfirmed`.
- **Reproduction :** deux callbacks valides simultanés pour la même référence.
- **Conséquence :** listeners, notifications et écritures secondaires potentiellement dupliqués.
- **Correction :** `DB::transaction`, `lockForUpdate`, transition unique vers `PAID`, événement après commit.
- **Tests :** callbacks concurrents et replay séquentiel.
- **Régression :** élevée.

### PAY-002 — Haute — Référence fournisseur non unique

- **Fichiers :** migration `create_payments_table`; `PaymentCallbackController::resolvePayment`.
- **Fait :** `provider_reference` est seulement indexée ; le callback prend la ligne la plus récente.
- **Conséquence :** rattachement au mauvais paiement.
- **Correction :** unicité `(provider, provider_reference)` après audit des doublons.
- **Tests :** doublon DB et callback ambigu.
- **Régression :** élevée pendant le backfill.

### PAY-003 — Haute — Idempotence facultative

- **Fichiers :** `PaymentService::startManagedPayment`, `ShipmentPaymentService`, `TransportService`.
- **Fait :** la colonne unique `idempotency_key` existe mais n’est pas obligatoire dans les appels centraux.
- **Conséquence :** double demande fournisseur sur retry ou double clic.
- **Correction :** clé métier obligatoire + hash du payload + retour de l’existant.
- **Tests :** même clé/même contenu, même clé/contenu différent, concurrence.
- **Régression :** moyenne.

### PAY-004 — Haute — Données callback trop largement conservées

- **Fichiers :** `PaymentService::handleCallback`; `PaymentCallbackController`.
- **Fait :** payload complet dans logs et `payments.meta`.
- **Conséquence :** exposition de données PSP ou personnelles.
- **Correction :** allowlist, masquage et conservation minimale chiffrée.
- **Tests :** absence de secrets et numéros complets dans logs.
- **Régression :** faible.

### PAY-005 — Haute — Comparaison monétaire en float

- **Fichier :** `PaymentService.php:236-251`.
- **Fait :** montant converti en `float` avec tolérance implicite de 1 FCFA.
- **Correction :** entiers XAF et devise strictement identique.
- **Tests :** montant exact, écart de 1, devise différente.
- **Régression :** moyenne.

### PAY-006 — Critique — Référence Bridge non atomique

- **Fichier :** `MobileMoneyBridgeService.php:24-77,142-159`.
- **Fait :** recherche dans JSON puis création sans contrainte unique atomique.
- **Reproduction :** deux requêtes parallèles même client/référence.
- **Conséquence :** double paiement.
- **Correction :** référence normalisée unique par client.
- **Tests :** concurrence MySQL.
- **Régression :** moyenne.

### PAY-007 — Haute — Callback sortant Bridge non durable

- **Fichier :** `MobileMoneyBridgeService.php:194-224`.
- **Fait :** URL externe fournie par client, POST synchrone non signé, erreur ignorée, aucune outbox.
- **Conséquence :** callback perdu et destination réseau non maîtrisée.
- **Correction :** validation stricte, signature, outbox, retries et journal.
- **Tests :** destination interdite, signature, indisponibilité temporaire.
- **Régression :** moyenne.

### PAY-008 — Haute — Retry transformant une panne en échec métier

- **Fichier :** `RetryPaymentCallbackJob.php:49-100`.
- **Fait :** à la dernière exception, le paiement devient `FAILED` sans résultat terminal fournisseur.
- **Conséquence :** argent encaissé mais paiement localement échoué.
- **Correction :** statut incertain, polling et revue ; jamais `FAILED` sur exception technique seule.
- **Tests :** timeout après succès fournisseur et provider indisponible.
- **Régression :** élevée.

## Invariants

- Une référence fournisseur n’identifie qu’un paiement.
- `PaymentConfirmed` est émis une seule fois.
- Aucun paiement n’est comptabilisé sans confirmation fournisseur.
- Une panne réseau ne produit pas un statut financier terminal.
- Callback, polling et réconciliation convergent sans double effet.