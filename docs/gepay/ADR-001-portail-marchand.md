# ADR-001 — Architecture du portail marchand GePay

**Statut :** Accepted  
**Date :** 2026-07-01  
**Décideur :** guetchou  
**Domaine :** GePay — passerelle de paiement multi-tenant  
**Révision :** 4 (patch bloquants PRD v1.2)

---

## Contexte

GePay est une passerelle de paiement autonome hébergée sur `gepay.bantudelice.cg`.
BantuDelice est le premier marchand, identifié par le slug stable `bantudelice-internal`.
Le portail admin BantuDelice (`admin-modern`) est **distinct** du portail marchand GePay.
Toute confusion entre les deux périmètres est un risque de sécurité et de données.

---

## Décisions imposées

### D1 — Isolation du portail

Le portail marchand GePay est indépendant de `admin-modern`.  
Layout : `resources/views/layouts/gepay.blade.php`  
Routes : groupe subdomain `gepay.bantudelice.cg`, fichier `routes/gepay-web.php`

### D2 — Authentification et session

| Élément | Valeur |
|---|---|
| Guard | `gepay` |
| Provider | `gepay_users` |
| Modèle | `App\Domain\GePay\Models\GePayMerchantUser` |
| Table | `gepay_merchant_users` |
| Cookie nom | distinct du cookie BantuDelice |
| Cookie flags | `Secure`, `HttpOnly`, `SameSite=Strict` |
| Cookie domaine | `gepay.bantudelice.cg` (host-only — pas de point préfixe) |
| Inscription | fermée — comptes créés via `gepay:provision-user` uniquement |

`GePayMerchantUser` est **séparé** de `App\User`. Aucune relation entre les deux.

Cookie host-only : en fixant `Domain=gepay.bantudelice.cg` sans point préfixe, le cookie
est restreint exactement à ce sous-domaine et n'est pas envoyé à `bantudelice.cg` ni aux
autres sous-domaines.

### D3 — Scope marchand

- Source unique : `auth('gepay')->user()->merchant_id`
- Interdit : `request()->input('merchant_id')` ou tout paramètre navigateur comme source de périmètre
- Toutes les requêtes (liste, KPI, écriture) filtrent sur le `merchant_id` authentifié

### D4 — Contrôleurs

Namespace dédié : `App\Http\Controllers\GePay\`

```
Auth\LoginController
DashboardController
TransactionController
DisbursementController   (Envoyer)
CollectionController     (Encaisser)
PayoutController
```

**Interdit** dans ces contrôleurs : `GePayInternalClientResolver`  
**Autorisé** : `GePayGateway`, `GePayMerchantClientResolver`, modèles GePay, enums, providers

### D5 — PR #101 (feat/gepay-collect-panel)

Conservée telle quelle — fonctionnalité **admin BantuDelice uniquement**.  
`GePayDisbursementController` reste dans `App\Http\Controllers\admin\`.  
Vue `admin/gepay/disbursement.blade.php` reste dans layout `admin-modern`.  
Aucun déplacement vers le portail marchand.

### D6 — Erreurs

Toute `\Throwable` dans un contrôleur marchand :
1. `Log::error()` avec contexte (`merchant_id`, `reference`, type d'opération)
2. Réponse client : message générique opaque
3. **Jamais** `$exception->getMessage()` exposé au navigateur ou à l'API

### D7 — Suppression physique interdite

Les entités suivantes ne peuvent jamais être supprimées physiquement (`DELETE`) :

- `gepay_merchants`
- `gepay_clients`
- `gepay_wallets`
- `gepay_transactions`
- `gepay_ledger_entries`  ← **aucun soft delete non plus** (immuabilité absolue)
- `gepay_payout_requests`
- `gepay_payout_destinations`

Pour les entités autres que le ledger : désactivation par `status`/`is_active` ou soft delete (`deleted_at`).  
Toutes les FK sur ces tables utilisent `ON DELETE RESTRICT`.

### D8 — Chiffrement des données de paiement

Toute donnée de destination de règlement (numéro de téléphone, IBAN) est **chiffrée au repos**.  
Cela inclut :
- `gepay_payout_destinations.destination` (encrypted cast)
- `gepay_payout_requests.destination_snapshot` — colonne TEXT chiffrée (encrypted cast JSON)

Structure de `destination_snapshot` (chiffrée côté serveur, jamais déchiffrée côté navigateur) :
```json
{
  "destination_type": "mobile_mtn",
  "destination_full": "<valeur complète chiffrée — serveur uniquement>",
  "masked": "****5678",
  "verified": true,
  "verified_by": "admin@gepay.cg",
  "verified_at": "2026-07-01T10:00:00Z"
}
```

Seul le champ `masked` peut apparaître dans les vues Blade, logs et réponses HTTP.  
`destination_full` ne transite **jamais** vers le navigateur, même pour un admin GePay.

### D9 — Client technique du portail (`GePayMerchantClientResolver`)

Chaque marchand doit disposer d'un `GePayClient` dédié au portail, référencé par
`gepay_merchants.portal_client_id`.

`GePayMerchantClientResolver` résout le client actif du marchand authentifié :
1. Lit `auth('gepay')->user()->merchant->portal_client_id`
2. Charge le `GePayClient` correspondant
3. Vérifie : `client.merchant_id == merchant.id` (invariant d'appartenance)
4. Vérifie : `client.status == active`
5. Vérifie : `client.capabilities` contient la capacité demandée (`collection`, `disbursement`, etc.)
6. Échec sur n'importe quelle condition → exception métier, log, réponse générique 503

Aucun contrôleur du portail n'appelle `GePayInternalClientResolver`.

### D10 — Idempotence des formulaires (`operation_token`)

Pour chaque opération financière (Envoyer, Encaisser, Payout) :

- À l'ouverture du formulaire (GET), le serveur génère un UUID `operation_token` et le stocke
  en session lié à `(merchant_id, user_id, operation_type)`.
- Le formulaire soumet ce token avec le payload.
- **Même token + même payload** → résultat identique retourné, aucune double écriture.
- **Même token + payload différent** → rejet 409 (conflit détecté).
- **Token absent ou expiré** → rejet 422.
- Token consommé après soumission réussie (invalidé en session).
- `operation_token` ≠ token CSRF. Les deux sont requis simultanément.

### D11 — Compensation technique vs remboursement métier

**Compensations techniques automatiques** (idempotentes, déclenchées par le système) :
- `disbursement_refund` : disbursement échoué / annulé / expiré → crédit `available`
- `payout_release` : payout non abouti → restitution `reserved` → `available`
- `collection_fail` : collection échouée / expirée / annulée → débit `pending`

Ces compensations s'exécutent **exactement une fois** via idempotency_key.  
Elles n'impliquent aucun remboursement vers le payeur.

**Remboursements métier** (`refunded`) : hors MVP. Requièrent une action admin explicite
et une écriture compensatrice `adjustment_credit` avec note. Non automatisés.

**État `unknown`** : aucune compensation automatique. Le wallet reste figé jusqu'à
résolution manuelle par réconciliation ou confirmation du provider.

**Règle de pré-soumission** : aucune écriture ledger si l'erreur survient avant
l'appel au provider (validation payload, résolution client, vérification solde).
`collection_pending` n'est créé que si et seulement si la soumission MTN a retourné
un succès HTTP (202 accepté par le provider).

### D12 — Provisionnement des utilisateurs GePay

Aucun compte `gepay_merchant_users` ne doit être créé manuellement via SQL.

Commande dédiée, auditée :
```
php artisan gepay:provision-user
  --merchant=<slug>
  --name=<nom>
  --email=<email>
  --role=<admin|viewer>
  [--send-invite]
```

La commande :
1. Vérifie que le marchand existe et est `active`
2. Vérifie l'unicité de l'email
3. Génère un mot de passe temporaire aléatoire (min 16 caractères)
4. Log l'action avec l'identité de l'opérateur (`whoami` ou env `PROVISIONER`)
5. N'affiche le mot de passe qu'une seule fois en sortie console

---

## Schéma de base de données

### Nouvelles tables

#### `gepay_merchants`
```
id               BIGINT UNSIGNED PK AUTO_INCREMENT
ulid             CHAR(26) UNIQUE NOT NULL
name             VARCHAR(191) NOT NULL
slug             VARCHAR(191) UNIQUE NOT NULL   -- ex: bantudelice-internal
country          CHAR(2) DEFAULT 'CG'
email            VARCHAR(191) UNIQUE NOT NULL
status           ENUM('active','suspended','closed') DEFAULT 'active'
portal_client_id BIGINT UNSIGNED NULL FK→gepay_clients (RESTRICT)
                 -- client GePay dédié au portail marchand, résolu par GePayMerchantClientResolver
created_at, updated_at
deleted_at       TIMESTAMP NULL
```

#### `gepay_merchant_users`
```
id               BIGINT UNSIGNED PK AUTO_INCREMENT
merchant_id      BIGINT UNSIGNED NOT NULL FK→gepay_merchants (RESTRICT)
name             VARCHAR(191) NOT NULL
email            VARCHAR(191) NOT NULL
password         VARCHAR(255) NOT NULL
role             ENUM('admin','viewer') DEFAULT 'admin'
is_active        BOOLEAN NOT NULL DEFAULT TRUE
                 -- FALSE → login refusé (403), sans détail
remember_token   VARCHAR(100) NULL
last_login_at    TIMESTAMP NULL
created_at, updated_at

UNIQUE (email)
INDEX (merchant_id, is_active)
```

#### `gepay_payout_destinations`
```
id               BIGINT UNSIGNED PK AUTO_INCREMENT
merchant_id      BIGINT UNSIGNED NOT NULL FK→gepay_merchants (RESTRICT)
label            VARCHAR(100) NOT NULL
destination_type ENUM('mobile_mtn','mobile_airtel','bank_iban') NOT NULL
destination      TEXT NOT NULL              -- chiffré (encrypted cast)
verified         BOOLEAN NOT NULL DEFAULT FALSE
verified_by      VARCHAR(191) NULL
verified_at      TIMESTAMP NULL
is_default       BOOLEAN NOT NULL DEFAULT FALSE
created_at, updated_at
deleted_at       TIMESTAMP NULL

INDEX (merchant_id, verified)
```

Seule la valeur masquée est exposée. `destination` déchiffrée uniquement côté serveur
lors de l'exécution d'un payout via `GePayGateway`.

#### `gepay_wallets`
```
id               BIGINT UNSIGNED PK AUTO_INCREMENT
merchant_id      BIGINT UNSIGNED NOT NULL FK→gepay_merchants (RESTRICT)
currency         CHAR(3) NOT NULL DEFAULT 'XAF'
available        BIGINT NOT NULL DEFAULT 0
pending          BIGINT NOT NULL DEFAULT 0
reserved         BIGINT NOT NULL DEFAULT 0
updated_at       TIMESTAMP NOT NULL

UNIQUE (merchant_id, currency)
CHECK  (available >= 0)
CHECK  (pending   >= 0)
CHECK  (reserved  >= 0)
```

Colonnes = agrégats transactionnels. `UPDATE … SET available = X` interdit hors ledger.  
Invariant : `available + pending + reserved` = somme nette du ledger pour ce wallet.

#### `gepay_ledger_entries`

Source de vérité financière. **Immuable absolument** : aucun `UPDATE`, `DELETE` ni soft delete.

```
id                 BIGINT UNSIGNED PK AUTO_INCREMENT
merchant_id        BIGINT UNSIGNED NOT NULL FK→gepay_merchants (RESTRICT)
wallet_id          BIGINT UNSIGNED NOT NULL FK→gepay_wallets (RESTRICT)
type               ENUM(
                     'collection_pending',
                     'collection_confirm',
                     'collection_fail',
                     'disbursement_debit',
                     'disbursement_refund',
                     'payout_reserve',
                     'payout_release',
                     'payout_debit',
                     'fee_debit',
                     'adjustment_credit',
                     'adjustment_debit'
                   ) NOT NULL
amount             BIGINT NOT NULL             -- positif, non nul, entier XAF
source_bucket      ENUM('available','pending','reserved') NULL
destination_bucket ENUM('available','pending','reserved') NULL
reference_type     VARCHAR(100) NOT NULL
reference_id       BIGINT UNSIGNED NOT NULL
idempotency_key    VARCHAR(191) NOT NULL
metadata           JSON NULL
note               TEXT NULL                   -- obligatoire pour adjustment_*
created_at         TIMESTAMP NOT NULL

UNIQUE (merchant_id, type, idempotency_key)
INDEX (merchant_id, created_at)
INDEX (wallet_id, created_at)
INDEX (reference_type, reference_id)
```

Toute correction → écriture compensatrice `adjustment_credit` / `adjustment_debit` avec `note`.  
Idempotence partagée par `(merchant_id, type)` : intentionnelle (voir D2 ADR v3).

#### `gepay_payout_requests`
```
id                       BIGINT UNSIGNED PK AUTO_INCREMENT
merchant_id              BIGINT UNSIGNED NOT NULL FK→gepay_merchants (RESTRICT)
wallet_id                BIGINT UNSIGNED NOT NULL FK→gepay_wallets (RESTRICT)
payout_destination_id    BIGINT UNSIGNED NOT NULL FK→gepay_payout_destinations (RESTRICT)
amount                   BIGINT UNSIGNED NOT NULL
currency                 CHAR(3) NOT NULL DEFAULT 'XAF'
destination_snapshot     TEXT NOT NULL   -- chiffré (encrypted cast) — voir structure D8
                                         -- masked uniquement exposé en vue
status                   ENUM('draft','submitted','processing',
                               'successful','failed','cancelled','expired')
                         NOT NULL DEFAULT 'draft'
idempotency_key          VARCHAR(191) NOT NULL
-- Champs remplis lors du traitement admin GePay
processed_by             VARCHAR(191) NULL    -- email admin GePay
operator_reference       VARCHAR(191) NULL    -- référence opérateur MTN/banque
rejection_reason         TEXT NULL
processed_at             TIMESTAMP NULL
expires_at               TIMESTAMP NULL
created_at, updated_at

UNIQUE (merchant_id, idempotency_key)
INDEX (merchant_id, status)
```

---

### Machine d'état `gepay_payout_requests`

```
  [draft] ──atomique──→ [submitted] ──admin start──→ [processing]
                             │                              │
                    ─────────┼──────────────────────────────┼─────────
                    submitted│cancelled   submitted│expired  │
                             ↓                    ↓         ├──→ [successful]
                         [cancelled]          [expired]     ├──→ [failed]
                                                            ├──→ [cancelled]
                                                            └──→ [expired]
```

| Transition | Acteur | Champs obligatoires | Effet ledger |
|---|---|---|---|
| `draft → submitted` | Marchand admin (atomique) | — | `payout_reserve` |
| `submitted → processing` | Admin GePay | `processed_by`, `processed_at` | aucun |
| `submitted → cancelled` | Admin GePay | `processed_by`, `processed_at`, `rejection_reason` | `payout_release` |
| `submitted → expired` | Système (SLA) | `processed_by=system`, `processed_at`, `rejection_reason` | `payout_release` |
| `processing → successful` | Admin GePay (après exécution MTN via backend) | `processed_by`, `processed_at`, `operator_reference` | `payout_debit` |
| `processing → failed` | Admin GePay | `processed_by`, `processed_at`, `rejection_reason` | `payout_release` |
| `processing → cancelled` | Admin GePay | `processed_by`, `processed_at`, `rejection_reason` | `payout_release` |
| `processing → expired` | Système (SLA) | `processed_by=system`, `processed_at`, `rejection_reason` | `payout_release` |

**États terminaux** : `successful`, `failed`, `cancelled`, `expired`.  

**Exécution MTN** : la transition `processing → successful` déclenche un appel à `GePayGateway`
via `GePayMerchantClientResolver`. Le numéro de destination est déchiffré **uniquement côté serveur**
et transmis au provider. `operator_reference` reçu en retour est stocké chiffré.
Aucun numéro complet ne transite vers le navigateur à aucune étape.

**Idempotence** : `payout_reserve` et `payout_release` protégés par
`UNIQUE(merchant_id, type, idempotency_key)` + `lockForUpdate()`.
Webhook ou job dupliqué → entrée existante retournée, 0 double écriture.

---

### Tables existantes modifiées

#### `gepay_clients` → ajouter `merchant_id` (nullable)
```sql
ALTER TABLE gepay_clients
  ADD COLUMN merchant_id BIGINT UNSIGNED NULL
    REFERENCES gepay_merchants(id) ON DELETE RESTRICT;
```
`NULL` = client interne BantuDelice (`GePayInternalClientResolver`) — non touché.  
Colonne reste nullable jusqu'à backfill validé, puis migration séparée NOT NULL.

#### `gepay_transactions` → ajouter `merchant_id` + remplacer cascade

**Migration A** (non-destructive) :
```sql
ALTER TABLE gepay_transactions
  ADD COLUMN merchant_id BIGINT UNSIGNED NULL
    REFERENCES gepay_merchants(id) ON DELETE RESTRICT;
```
`merchant_id` toujours déduit serveur : `gepay_clients.merchant_id WHERE id = client_id`.  
Invariant : `tx.merchant_id MUST EQUAL client.merchant_id` (WHERE both NOT NULL).

**Migration B** (DDL sensible) : cascade → restrict sur `gepay_transactions.client_id`.

> ⚠️ **Warning — Migration B :** Empêche tout `DELETE` sur `gepay_clients` si transactions existent.  
> Rollback staging obligatoire. `down()` : recréer `ON DELETE CASCADE`.

---

## Backfill contrôlé — `gepay:link-internal-merchant`

```
php artisan gepay:link-internal-merchant [--dry-run] [--force]
```

1. Retrouver ou créer marchand via slug `bantudelice-internal` — ambiguïté → stop.
2. Retrouver client via `GEPAY_INTERNAL_CLIENT_UUID` — absent / ambigu → stop.
3. Afficher volumes avant (lignes sans `merchant_id`, somme `amount`).
4. Rattacher uniquement ce client et ses transactions. Aucune autre ligne touchée.
5. Afficher volumes après. Confirmation interactive sans `--force`.

---

## Flux financiers

### Source de vérité et réconciliation

`available`, `pending`, `reserved` = projections du ledger. Ledger fait foi en cas de divergence.

```
wallet.available = SUM(amount WHERE destination_bucket='available')
                 - SUM(amount WHERE source_bucket='available')

wallet.pending   = SUM(amount WHERE destination_bucket='pending')
                 - SUM(amount WHERE source_bucket='pending')

wallet.reserved  = SUM(amount WHERE destination_bucket='reserved')
                 - SUM(amount WHERE source_bucket='reserved')
```

Divergence → alerte `critical` + monitoring. **Jamais** corrigée silencieusement.

### Règles d'écriture financière

1. `DB::transaction()`
2. `lockForUpdate()` sur wallet
3. Vérifier `available >= montant` avant débit
4. Calculer `balance_after` après verrouillage
5. `idempotency_key` obligatoire — double soumission retourne l'existant
6. Entiers XAF, aucun `float`, aucun `round()`
7. `UPDATE gepay_wallets SET … = X` direct = interdit
8. Correction → écriture compensatrice avec `note` — aucun UPDATE/DELETE ledger

### Transitions complètes

```
── Encaisser ───────────────────────────────────────────────────────────────
Collection soumise ET acceptée par MTN (HTTP 202)
  → collection_pending  | src: NULL      | dst: pending
  ⚠ Si MTN retourne erreur avant soumission : AUCUNE écriture ledger
Collection confirmée (webhook SUCCESSFUL)
  → collection_confirm  | src: pending   | dst: available
Collection échouée / expirée / annulée (webhook FAILED / EXPIRED / CANCELLED)
  → collection_fail     | src: pending   | dst: NULL

── Envoyer ─────────────────────────────────────────────────────────────────
Disbursement soumis (1× exactement)
  → disbursement_debit  | src: available | dst: NULL
Disbursement failed / cancelled / expired (1× exactement, compensation technique)
  → disbursement_refund | src: NULL      | dst: available
Disbursement successful : AUCUNE écriture compensatrice

── Payout ──────────────────────────────────────────────────────────────────
draft → submitted (atomique, 1× exactement)
  → payout_reserve      | src: available | dst: reserved
processing → successful (après exécution MTN backend)
  → payout_debit        | src: reserved  | dst: NULL
{submitted|processing} → {failed|cancelled|expired} (compensation technique, 1× exactement)
  → payout_release      | src: reserved  | dst: available

── États incertains ────────────────────────────────────────────────────────
unknown : AUCUNE compensation automatique — wallet figé jusqu'à réconciliation

── Frais ───────────────────────────────────────────────────────────────────
  → fee_debit           | src: available | dst: NULL    (MVP : 0 XAF, non utilisé)

── Corrections admin ───────────────────────────────────────────────────────
  → adjustment_credit   | src: NULL      | dst: available  | note obligatoire
  → adjustment_debit    | src: available | dst: NULL        | note obligatoire

── Remboursements métier (hors MVP) ────────────────────────────────────────
status 'refunded' : action admin explicite → adjustment_credit avec note
Distinct des compensations techniques automatiques
```

---

## Relations

```
gepay_merchants (1)
  ├── portal_client_id → gepay_clients (GePayMerchantClientResolver)
  ├── (N) gepay_merchant_users     is_active contrôle l'accès
  ├── (N) gepay_clients            merchant_id nullable → NOT NULL post-backfill
  ├── (N) gepay_transactions       merchant_id nullable → NOT NULL post-backfill
  │         invariant: tx.merchant_id = client.merchant_id
  ├── (N) gepay_payout_destinations
  ├── (1) gepay_wallets            UNIQUE(merchant_id, currency), CHECK >= 0
  │     └── (N) gepay_ledger_entries  immuable, source de vérité
  └── (N) gepay_payout_requests
              ├── FK→gepay_payout_destinations
              ├── destination_snapshot chiffré (masked uniquement en vue)
              └── machine d'état 7 états, champs obligatoires par transition
```

---

## Risques de migration

| Risque | Impact | Mitigation |
|---|---|---|
| `gepay_merchants.portal_client_id` nullable | Faible | Peuplé via seeder avant mise en service portail |
| `gepay_merchant_users.is_active` DEFAULT TRUE | Faible — non-destructif | Migration séparée |
| `gepay_clients` + `merchant_id` nullable | Faible | Reste nullable jusqu'à backfill validé |
| `gepay_transactions` + `merchant_id` nullable | Faible | Idem |
| FK cascade → restrict `gepay_transactions.client_id` | Moyen | Fenêtre maintenance, rollback staging |
| Backfill `gepay:link-internal-merchant` | Moyen | `--dry-run` avant `--force` |
| CHECK `>= 0` wallet (MySQL < 8.0.16 / MariaDB < 10.2) | Faible | Vérifier version DB |
| Divergence wallet / ledger post-migration | Élevé si présent | Réconciliation avant mise en service |
| `gepay_payout_requests` + `processed_by`, `operator_reference` nullable | Faible | Non-destructif |

---

## Navigation layout `gepay.blade.php`

Routes relatives à `gepay.bantudelice.cg`, sans préfixe `/gepay` :

```
Auth (hors middleware) :
  GET  /login    → formulaire
  POST /logout   → déconnexion (CSRF + Secure cookie host-only)

Sidebar (middleware auth:gepay) :
  ● Tableau de bord    GET /
  ● Transactions       GET /transactions
  ● Envoyer            GET /envoyer   POST /envoyer
  ● Encaisser          GET /encaisser POST /encaisser
  ● Payout             GET /payout    POST /payout

Header :
  Nom marchand  |  {wallet.available} XAF  |  [POST /logout]
```

Aucun `/100`. Montants en entiers XAF natifs.

---

## Tests minimums obligatoires

```
AuthTest
  ✓ login valide → session gepay créée, session régénérée
  ✓ login invalide → 422
  ✓ logout POST → session détruite, redirect /login
  ✓ marchand status=suspended → 403 générique
  ✓ user is_active=false → 403 générique
  ✓ rate limiting → 6e tentative bloquée
  ✓ cookie : Secure, HttpOnly, SameSite=Strict, domain=gepay.bantudelice.cg
  ✓ POST sans CSRF token → 419

IsolationTest
  ✓ merchant_A ne voit pas données merchant_B
  ✓ merchant_id URL param ignoré
  ✓ viewer POST /envoyer → 403
  ✓ viewer POST /encaisser → 403
  ✓ viewer POST /payout → 403

ClientResolverTest
  ✓ portal_client_id null → 503 générique
  ✓ client inactif → 503 générique
  ✓ client sans capacité → 503 générique
  ✓ client merchant_id ≠ authenticated merchant → exception

OperationTokenTest
  ✓ GET formulaire → operation_token généré en session
  ✓ même token + même payload → résultat identique, 0 double écriture
  ✓ même token + payload différent → 409
  ✓ token absent → 422
  ✓ token ≠ CSRF token

WalletTest
  ✓ available / pending / reserved cohérents avec ledger
  ✓ CHECK available >= 0 enforced
  ✓ UNIQUE(merchant_id, currency) enforced
  ✓ double soumission idempotente → 0 double écriture
  ✓ fonds insuffisants → erreur, wallet + ledger inchangés
  ✓ erreur pré-soumission MTN → 0 écriture ledger

CollectionTest
  ✓ collection_pending créé UNIQUEMENT si MTN retourne 202
  ✓ MTN erreur pré-soumission → 0 collection_pending
  ✓ webhook SUCCESSFUL → collection_confirm
  ✓ webhook FAILED / EXPIRED / CANCELLED → collection_fail
  ✓ statut expired affiché "Expiré"

DisbursementTest
  ✓ disbursement_debit créé exactement 1× à la soumission
  ✓ disbursement_refund créé exactement 1× sur failed/cancelled/expired
  ✓ 0 disbursement_refund sur successful
  ✓ fee_debit : 0 entrée en MVP (frais = 0 XAF)

PayoutTest
  ✓ destination verified=false → refusé, 0 écriture
  ✓ destination verified=true → payout_reserve atomique avec draft→submitted
  ✓ destination_snapshot : masked uniquement en vue, full chiffré serveur
  ✓ submitted→cancelled → payout_release + champs admin obligatoires
  ✓ submitted→expired → payout_release + processed_by=system
  ✓ processing→successful : appel GePayGateway, operator_reference stocké
  ✓ processing→failed/cancelled/expired → payout_release
  ✓ payout_reserve idempotent (retry job, webhook dupliqué)
  ✓ payout_release idempotent (idem)
  ✓ unknown : 0 compensation automatique

ReconciliationTest
  ✓ wallet = agrégat ledger → OK
  ✓ divergence artificielle → alerte critical, 0 correction silencieuse

BackfillTest
  ✓ gepay:link-internal-merchant --dry-run → 0 écriture
  ✓ GEPAY_INTERNAL_CLIENT_UUID absent → stop explicite
  ✓ ambiguïté → stop explicite

ProvisioningTest
  ✓ gepay:provision-user crée utilisateur avec rôle correct
  ✓ email dupliqué → stop explicite
  ✓ marchand inexistant ou inactif → stop explicite

InvariantTest
  ✓ tx.merchant_id == client.merchant_id (WHERE both NOT NULL)

MigrationTest
  ✓ rollback complet dans l'ordre inverse

LayoutTest
  ✓ desktop 1280px sans overflow
  ✓ mobile 375px sans overflow
```

---

## Conséquences

**Acceptées :**
- Portail isolé de l'admin BantuDelice
- Isolation multi-tenant par guard + `GePayMerchantClientResolver`
- Ledger immuable — audit et réconciliation à tout moment
- Agrégats wallet = projections, jamais source primaire
- Destinations chiffrées, jamais exposées en clair
- Idempotence opération_token + ledger UNIQUE
- Payout : exécution MTN par backend, aucun numéro au navigateur
- Compensation technique ≠ remboursement métier
- `collection_pending` conditionnel à l'acceptation MTN
- `unknown` sans compensation automatique
- Cookie host-only `gepay.bantudelice.cg`
- Provisionnement utilisateurs via Artisan audité

**Différées (Phase 2) :**
- Airtel Money (provider à créer)
- Webhooks marchands configurables
- Multi-utilisateurs rôles fins
- Graphiques statistiques
- Payout IBAN automatisé
- Remboursements métier automatisés
- Widget JS / SDK
- Changement mot de passe self-service

---

**Statut : Accepted — révision 4 — 2026-07-01**
