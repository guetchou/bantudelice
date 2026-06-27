# Mandat Claude — Auditer, corriger et finaliser GePay 0.1

## Contexte

Tu travailles dans le dépôt `guetchou/bantudelice`, sur la branche `feat/gepay-gateway-foundation`, associée à la PR #48.

GePay est une passerelle de paiement réutilisable, isolée du métier BantuDelice. Le lot 0.1 doit fournir un socle fiable pour :

- MTN MoMo Collection ;
- MTN MoMo Disbursement ;
- authentification HMAC des applications clientes ;
- idempotence ;
- journal transactionnel ;
- webhooks ;
- rapprochement automatique ;
- statuts normalisés ;
- préparation de Remittance sans activation prématurée.

## Règles absolues

1. Ne travaille jamais directement sur `main`.
2. Utilise exclusivement la branche `feat/gepay-gateway-foundation`.
3. Ne fusionne pas la PR #48.
4. Ne lance jamais `migrate:fresh`, `db:wipe` ou `git reset --hard`.
5. Ne supprime aucune donnée existante.
6. Ne mets aucune vraie clé MTN dans Git.
7. Ne journalise jamais API key, API user, subscription key, access token, secret HMAC ou header Authorization.
8. Les migrations doivent être additives, réversibles et compatibles MySQL.
9. Ne modifie pas les workflows BantuDelice existants sauf nécessité démontrée.
10. Ne déclare pas GePay prêt pour la production sans tests automatisés et validation sandbox.

## Diagnostic initial obligatoire

Exécute d'abord :

```bash
cd /opt/bantudelice

git status --short
git branch --show-current
git log --oneline -10
git diff main...HEAD --stat

php -v
php artisan about
php artisan migrate:status
php artisan route:list | grep -i gepay
php artisan schedule:list | grep -i gepay
```

Inspecte au minimum :

```text
config/gepay.php
routes/gepay.php
app/Domain/GePay/
app/Http/Middleware/AuthenticateGePayClient.php
app/Http/Controllers/Api/GePay/V1/
app/Console/Commands/GePayCreateClient.php
app/Console/Commands/GePayReconcile.php
database/migrations/2026_06_26_160000_create_gepay_gateway_tables.php
tests/Unit/GePaySignerTest.php
tests/Feature/GePayAuthenticationTest.php
tests/Feature/GePayMtnTokenHeaderTest.php
docs/gepay/README.md
app/Providers/AppServiceProvider.php
```

Avant toute réécriture, publie dans ton rapport une matrice :

| Élément | État actuel | Risque | Correction requise |
|---|---|---|---|

## Architecture cible

```text
Application cliente
        |
        v
API GePay
  - HMAC
  - idempotence
  - validation
  - journal transactionnel
  - statuts normalisés
  - webhooks
  - rapprochement
        |
        v
Provider Adapter
  - MTN Collection
  - MTN Disbursement
  - MTN Remittance futur
  - Airtel futur
```

Une application cliente ne doit jamais gérer directement les endpoints de token MTN, `X-Target-Environment`, les subscription keys, les statuts MTN ou les erreurs propres à MTN.

## API attendue

```http
GET  /api/gepay/v1/health
GET  /api/gepay/v1/client
POST /api/gepay/v1/collections
POST /api/gepay/v1/disbursements
GET  /api/gepay/v1/transactions/{reference}
POST /api/gepay/v1/transactions/{reference}/refresh
POST /api/gepay/v1/webhooks/mtn
```

Préparer l'architecture pour Remittance, mais ne pas activer la route tant que les endpoints et droits MTN Congo ne sont pas confirmés.

## Authentification HMAC

Les requêtes privées doivent contenir :

```text
X-GePay-Key
X-GePay-Timestamp
X-GePay-Signature
Idempotency-Key
```

Chaîne canonique :

```text
{timestamp}\n{METHOD}\n{request_uri}\n{sha256(raw_body)}
```

Signature : `HMAC-SHA256(canonical_string, client_secret)`.

Vérifie et corrige :

- `hash_equals` ;
- tolérance temporelle configurable ;
- rejet des timestamps trop anciens ou trop futurs ;
- URI canonique avec query string ;
- body brut non transformé ;
- restriction IP facultative ;
- limitation de débit ;
- protection anti-rejeu avec `X-GePay-Nonce` et cache temporaire ;
- réponse uniforme sans fuite d'information.

## Idempotence et concurrence

Règles :

- unicité de `Idempotency-Key` par client ;
- même clé + même payload = transaction existante ;
- même clé + payload différent = `409 Conflict` ;
- `external_reference` unique par client et type ;
- double clic = un seul appel fournisseur ;
- requêtes concurrentes = une seule transaction ;
- verrou SQL et contraintes uniques ;
- gestion propre des collisions MySQL.

Ajoute des tests dédiés de double soumission et de concurrence.

## Statuts GePay

Statuts autorisés :

```text
created
submitted
pending
successful
failed
cancelled
expired
unknown
reversed
refunded
```

Un timeout après soumission doit produire `unknown`, jamais `failed`.

Centralise et verrouille les transitions afin d'éviter des régressions telles que `successful -> pending` ou `successful -> failed` sans correction auditable.

## MTN MoMo — tokens

Le header suivant est obligatoire pendant la création des tokens :

```http
X-Target-Environment: mtncongo
```

Il doit être présent pour Collection et Disbursement, et à terme Remittance.

Les headers de token doivent contenir :

```php
[
    'Ocp-Apim-Subscription-Key' => $subscriptionKey,
    'X-Target-Environment' => $targetEnvironment,
    'Content-Type' => 'application/json',
    'Content-Length' => '0',
]
```

Configuration production :

```env
GEPAY_MTN_ENVIRONMENT=production
GEPAY_MTN_TARGET_ENVIRONMENT=mtncongo
```

Configuration sandbox :

```env
GEPAY_MTN_ENVIRONMENT=sandbox
GEPAY_MTN_TARGET_ENVIRONMENT=sandbox
```

Ne code pas directement `mtncongo` dans les services.

Le cache des tokens doit distinguer produit, environnement cible et API user. Son TTL doit utiliser `expires_in` retourné par MTN, avec marge de sécurité.

## Collection

Endpoint :

```text
POST /collection/v1_0/requesttopay
```

Une réponse HTTP 202 signifie seulement `pending`. Le succès réel doit être confirmé par callback ou rapprochement.

Vérifie :

- format MSISDN Congo ;
- montant entier ;
- devise XAF ;
- `externalId` et `X-Reference-Id` uniques ;
- callback configurable ;
- réponses HTML ou JSON invalides ;
- erreurs et timeouts MTN.

## Disbursement

Endpoint :

```text
POST /disbursement/v1_0/transfer
```

Une réponse HTTP 202 signifie seulement que le transfert est accepté pour traitement. Le succès réel est confirmé par :

```text
GET /disbursement/v1_0/transfer/{reference}
```

Ne relance jamais aveuglément un transfert lorsque son résultat est incertain.

## Remittance

Remittance reste désactivé jusqu'à confirmation de :

- contrat ;
- credentials ;
- chemin exact du token ;
- endpoint d'envoi ;
- statuts ;
- exigences réglementaires ;
- pays et devises autorisés.

Prépare uniquement la configuration, l'interface, les capability flags et la documentation. N'invente pas les endpoints.

## Webhooks entrants MTN

Flux recommandé :

```text
callback reçu
-> hash du payload
-> déduplication
-> identification transaction
-> contrôle du statut auprès de MTN
-> mise à jour GePay
-> notification de l'application cliente
```

Le callback ne doit jamais être considéré comme preuve suffisante sans recontrôle MTN.

Conserve les callbacks inconnus pour investigation. Le traitement doit être rejouable et idempotent.

## Webhooks sortants clients

Implémente ou prépare :

```text
transaction.pending
transaction.successful
transaction.failed
transaction.unknown
transaction.reversed
```

Headers recommandés :

```text
X-GePay-Event
X-GePay-Delivery
X-GePay-Timestamp
X-GePay-Signature
```

Ajoute retries, backoff, historique des tentatives et replay manuel. L'échec du webhook client ne doit pas bloquer la mise à jour de la transaction.

## Base de données

Audite :

```text
gepay_clients
gepay_transactions
gepay_webhook_events
```

Vérifie les index, contraintes, tailles, montants entiers, chiffrement, historique et suppression.

`cascadeOnDelete()` sur les transactions financières doit être réévalué. Préférer désactivation, soft delete ou restriction afin de conserver l'historique.

Ajoute si nécessaire :

```text
gepay_transaction_attempts
gepay_outgoing_webhooks
gepay_audit_logs
```

## Commandes Artisan

Valide et améliore :

```bash
php artisan gepay:client-create
php artisan gepay:reconcile
```

Ajoute si utile :

```bash
php artisan gepay:client-list
php artisan gepay:client-disable
php artisan gepay:client-rotate-secret
php artisan gepay:transaction-show
php artisan gepay:webhook-retry
php artisan gepay:health
```

Les secrets ne doivent être affichés qu'à leur création ou rotation.

## Tests obligatoires

Ajoute ou complète :

```text
tests/Feature/GePayIdempotencyTest.php
tests/Feature/GePayConcurrencyTest.php
tests/Feature/GePayCollectionTest.php
tests/Feature/GePayDisbursementTest.php
tests/Feature/GePayWebhookTest.php
tests/Feature/GePayReconciliationTest.php
tests/Feature/GePayAuthorizationTest.php
```

Couvre au minimum :

- signature correcte et incorrecte ;
- timestamp expiré et futur ;
- client désactivé ;
- IP refusée ;
- nonce rejoué ;
- body modifié après signature ;
- même clé d'idempotence avec même payload ;
- même clé avec payload différent ;
- double clic ;
- concurrence ;
- token Collection avec `mtncongo` ;
- token Disbursement avec `mtncongo` ;
- sandbox avec `sandbox` ;
- caches séparés ;
- réponse token sans access token ;
- Collection 202, succès, échec, timeout, HTML ou JSON invalide ;
- Disbursement 202, succès confirmé, échec confirmé et timeout `unknown` ;
- webhook valide, dupliqué, inconnu et recontrôle MTN ;
- cloisonnement strict entre clients ;
- masquage téléphone ;
- absence de secrets dans les réponses.

Utilise `Http::fake()` et `Http::assertSent()`.

## Documentation

Mets à jour `docs/gepay/README.md` et ajoute si nécessaire :

```text
docs/gepay/architecture.md
docs/gepay/api.md
docs/gepay/security.md
docs/gepay/deployment.md
docs/gepay/client-integration.md
docs/gepay/openapi.yaml
```

Documente authentification, signature, idempotence, statuts, erreurs HTTP, webhooks, sandbox, production et rotation des secrets.

## Validation finale

Exécute :

```bash
composer install
php artisan optimize:clear
php artisan migrate:status
php artisan route:list | grep -i gepay
php artisan schedule:list | grep -i gepay
php artisan test --filter=GePay
php artisan test
./vendor/bin/pint --test
```

Vérifie la syntaxe PHP :

```bash
find app/Domain/GePay app/Http/Controllers/Api/GePay app/Console/Commands \
  -name '*.php' -print0 | xargs -0 -n1 php -l
```

Ne masque aucun échec.

## Livrables

Pousse les commits sur `feat/gepay-gateway-foundation` et mets à jour la PR #48 avec :

1. diagnostic initial ;
2. anomalies et causes racines ;
3. fichiers et migrations modifiés ;
4. tests exécutés et résultats ;
5. risques résiduels ;
6. variables d'environnement nécessaires ;
7. commandes de déploiement et rollback ;
8. recommandation claire : fusionnable ou non fusionnable.

Ne fusionne pas la PR.

Priorités absolues : intégrité financière, idempotence, sécurité, traçabilité, rapprochement et tests.
