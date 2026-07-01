# ADR-001 — Architecture du portail marchand GePay

**Statut :** Accepted  
**Date :** 2026-07-01  
**Décideur :** guetchou  
**Domaine :** GePay — passerelle de paiement multi-tenant  
**Révision :** 3 (finale)

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

### D2 — Authentification

| Élément | Valeur |
|---|---|
| Guard | `gepay` |
| Provider | `gepay_users` |
| Modèle | `App\Domain\GePay\Models\GePayMerchantUser` |
| Table | `gepay_merchant_users` |
| Session cookie | distinct du cookie BantuDelice |
| Inscription | fermée — comptes créés manuellement ou via seeder |

`GePayMerchantUser` est **séparé** de `App\User`. Aucune relation entre les deux.

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
**Autorisé** : `GePayGateway`, modèles GePay, enums, providers MTN/Airtel

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
- `gepay_ledger_entries`
- `gepay_payout_requests`
- `gepay_payout_destinations`

Désactivation par champ `status` ou `deleted_at` (soft delete) uniquement.  
Toutes les FK sur ces tables utilisent `ON DELETE RESTRICT`.

### D8 — Chiffrement des données de paiement

Toute donnée de destination de règlement (numéro de téléphone, IBAN) est **chiffrée au repos** via Laravel `encrypted` cast.  
Cela inclut :
- `gepay_payout_destinations.destination`
- `gepay_payout_requests.destination_snapshot` (colonne JSON entière)

Seules des valeurs **masquées** (ex: `****5678`, `FR76****1234`) peuvent apparaître dans :
- les vues Blade
- les logs applicatifs
- les réponses HTTP (JSON inclus)

Aucune valeur complète ne transite vers le navigateur, même pour un admin GePay.

---

## Schéma de base de données

### Nouvelles tables

#### `gepay_merchants`
```
id               BIGINT UNSIGNED PK AUTO_INCREMENT
ulid             CHAR(26) UNIQUE NOT NULL       -- identifiant stable et opaque
name             VARCHAR(191) NOT NULL
slug             VARCHAR(191) UNIQUE NOT NULL   -- ex: bantudelice-internal
country          CHAR(2) DEFAULT 'CG'
email            VARCHAR(191) UNIQUE NOT NULL
status           ENUM('active','suspended','closed') DEFAULT 'active'
created_at, updated_at
deleted_at       TIMESTAMP NULL                 -- soft delete uniquement
```

#### `gepay_merchant_users`
```
id               BIGINT UNSIGNED PK AUTO_INCREMENT
merchant_id      BIGINT UNSIGNED NOT NULL FK→gepay_merchants (RESTRICT)
name             VARCHAR(191) NOT NULL
email            VARCHAR(191) NOT NULL
password         VARCHAR(255) NOT NULL
role             ENUM('admin','viewer') DEFAULT 'admin'
remember_token   VARCHAR(100) NULL
last_login_at    TIMESTAMP NULL
created_at, updated_at

UNIQUE (email)
INDEX (merchant_id)
```

#### `gepay_payout_destinations`

Destinations de règlement vérifiées. Séparée de `gepay_merchants` pour permettre
plusieurs destinations par marchand et un audit de vérification indépendant.

```
id               BIGINT UNSIGNED PK AUTO_INCREMENT
merchant_id      BIGINT UNSIGNED NOT NULL FK→gepay_merchants (RESTRICT)
label            VARCHAR(100) NOT NULL          -- ex: "MTN principal", "Compte BGFI"
destination_type ENUM('mobile_mtn','mobile_airtel','bank_iban') NOT NULL
destination      TEXT NOT NULL                  -- chiffré (encrypted cast) — jamais exposé brut
verified         BOOLEAN NOT NULL DEFAULT FALSE
verified_by      VARCHAR(191) NULL              -- email admin GePay ayant vérifié
verified_at      TIMESTAMP NULL
is_default       BOOLEAN NOT NULL DEFAULT FALSE
created_at, updated_at
deleted_at       TIMESTAMP NULL                 -- soft delete uniquement

INDEX (merchant_id, verified)
-- Une seule destination par défaut par marchand (enforced applicativement + index partiel si DB supporte)
```

**Règle** : seul un admin GePay peut passer `verified = true`. Le marchand ne peut pas s'auto-vérifier.  
**Exposition** : seules les valeurs masquées (`****5678`) apparaissent dans les vues et réponses.

#### `gepay_wallets`

Les colonnes `available`, `pending`, `reserved` sont des **agrégats transactionnels**.
Elles ne sont jamais modifiées directement. Toute mutation passe par `gepay_ledger_entries`
avec `lockForUpdate()` dans la même transaction SQL.

```
id               BIGINT UNSIGNED PK AUTO_INCREMENT
merchant_id      BIGINT UNSIGNED NOT NULL FK→gepay_merchants (RESTRICT)
currency         CHAR(3) NOT NULL DEFAULT 'XAF'
available        BIGINT NOT NULL DEFAULT 0     -- agrégat, entiers XAF
pending          BIGINT NOT NULL DEFAULT 0     -- agrégat, entiers XAF
reserved         BIGINT NOT NULL DEFAULT 0     -- agrégat, entiers XAF
updated_at       TIMESTAMP NOT NULL

UNIQUE (merchant_id, currency)
CHECK  (available >= 0)
CHECK  (pending   >= 0)
CHECK  (reserved  >= 0)
```

**Règles wallet :**
- Un seul wallet par `(merchant_id, currency)` — enforced par la contrainte `UNIQUE` et vérification applicative avant création.
- `available`, `pending`, `reserved` ne peuvent pas être négatifs — enforced par `CHECK` DB et vérification applicative avant toute écriture.
- `UPDATE gepay_wallets SET available = X` est **interdit** hors de la procédure de ledger verrouillée.

**Invariant** : `available + pending + reserved` = somme nette des `gepay_ledger_entries` agrégées par bucket pour ce wallet. Toute divergence est un incident.

#### `gepay_ledger_entries`

**Source de vérité financière.** En cas de divergence avec `gepay_wallets`, le ledger fait foi.
Aucun solde wallet n'est fiable s'il diverge du ledger recalculé.

Chaque entrée est définie par les champs suivants — **tous obligatoires sauf `source_bucket` et `destination_bucket`** :

```
id               BIGINT UNSIGNED PK AUTO_INCREMENT
merchant_id      BIGINT UNSIGNED NOT NULL FK→gepay_merchants (RESTRICT)
wallet_id        BIGINT UNSIGNED NOT NULL FK→gepay_wallets (RESTRICT)
type             ENUM(
                   'collection_pending',    -- bucket: NULL → pending
                   'collection_confirm',    -- bucket: pending → available
                   'collection_fail',       -- bucket: pending → NULL
                   'disbursement_debit',    -- bucket: available → NULL
                   'disbursement_refund',   -- bucket: NULL → available
                   'payout_reserve',        -- bucket: available → reserved
                   'payout_release',        -- bucket: reserved → available
                   'payout_debit',          -- bucket: reserved → NULL
                   'fee_debit',             -- bucket: available → NULL
                   'adjustment_credit',     -- bucket: NULL → available  (note obligatoire)
                   'adjustment_debit'       -- bucket: available → NULL  (note obligatoire)
                 ) NOT NULL
amount           BIGINT NOT NULL             -- toujours positif, jamais zéro, entier XAF
source_bucket    ENUM('available','pending','reserved') NULL   -- bucket débité
destination_bucket ENUM('available','pending','reserved') NULL -- bucket crédité
reference_type   VARCHAR(100) NOT NULL       -- ex: GePayTransaction, GePayPayoutRequest
reference_id     BIGINT UNSIGNED NOT NULL
idempotency_key  VARCHAR(191) NOT NULL
metadata         JSON NULL                   -- données contextuelles libres (ex: webhook_id, provider_ref)
note             TEXT NULL                   -- obligatoire pour adjustment_credit / adjustment_debit
created_at       TIMESTAMP NOT NULL

-- Idempotence scoped : un merchant partage l'espace d'idempotence par type entre tous ses clients API
-- Règle intentionnelle : empêche deux clients API du même marchand de soumettre la même opération en doublon
UNIQUE (merchant_id, type, idempotency_key)
INDEX (merchant_id, created_at)
INDEX (wallet_id, created_at)
INDEX (reference_type, reference_id)
```

**Immuabilité absolue** :
- Aucun `UPDATE` ni `DELETE` jamais autorisé sur cette table.
- Toute correction → **écriture compensatrice** (`adjustment_credit` ou `adjustment_debit`) avec `note` obligatoire.
- L'entrée erronée reste visible pour audit.

**Idempotence partagée par marchand :** `UNIQUE(merchant_id, type, idempotency_key)` est **intentionnel**.
Tous les clients API d'un même marchand partagent l'espace d'idempotence par type d'opération.
Cela empêche deux clients différents du même marchand de soumettre deux fois la même opération.
Si une séparation par client API est nécessaire, inclure le `client_id` dans `idempotency_key` à la construction.

#### `gepay_payout_requests`

```
id                       BIGINT UNSIGNED PK AUTO_INCREMENT
merchant_id              BIGINT UNSIGNED NOT NULL FK→gepay_merchants (RESTRICT)
wallet_id                BIGINT UNSIGNED NOT NULL FK→gepay_wallets (RESTRICT)
payout_destination_id    BIGINT UNSIGNED NOT NULL FK→gepay_payout_destinations (RESTRICT)
amount                   BIGINT UNSIGNED NOT NULL
currency                 CHAR(3) NOT NULL DEFAULT 'XAF'
-- Snapshot immuable chiffré de la destination au moment de la soumission
-- Contient : {type, masked_destination, verified, verified_by, verified_at}
-- Jamais de valeur complète dans ce JSON
destination_snapshot     TEXT NOT NULL    -- chiffré (encrypted cast)
status                   ENUM('draft','submitted','processing','successful','failed','cancelled','expired')
                         NOT NULL DEFAULT 'draft'
idempotency_key          VARCHAR(191) NOT NULL
rejection_reason         TEXT NULL
processed_at             TIMESTAMP NULL
expires_at               TIMESTAMP NULL   -- nullable, fixé à la soumission si applicable
created_at, updated_at

UNIQUE (merchant_id, idempotency_key)
INDEX (merchant_id, status)
```

`destination_snapshot` est écrit à la création et jamais modifié (valeur chiffrée, masquée à l'affichage).

---

### Machine d'état `gepay_payout_requests`

```
         ┌─────────────────────────────────────────────────────┐
         │                                                     │
  [draft] ──submit──→ [submitted] ──processing start──→ [processing]
                          │                                    │
                          │ annulation avant traitement        ├──→ [successful]
                          ↓                                    │
                      [cancelled]                              ├──→ [failed]
                                                               │
                                                               ├──→ [cancelled]  (arrêt admin)
                                                               │
                                                               └──→ [expired]    (timeout SLA)
```

| Transition | Condition | Effet ledger |
|---|---|---|
| `draft → submitted` | destination vérifiée + solde suffisant | `payout_reserve` : available-- / reserved++ |
| `submitted → processing` | traitement opérateur démarré | aucun effet ledger |
| `submitted → cancelled` | annulation avant traitement | `payout_release` : reserved-- / available++ |
| `processing → successful` | webhook / confirmation opérateur | `payout_debit` : reserved-- |
| `processing → failed` | rejet opérateur | `payout_release` : reserved-- / available++ |
| `processing → cancelled` | arrêt admin GePay | `payout_release` : reserved-- / available++ |
| `processing → expired` | timeout SLA dépassé | `payout_release` : reserved-- / available++ |

**États terminaux** : `successful`, `failed`, `cancelled`, `expired` — aucune transition sortante.

**Garantie d'unicité des écritures réservation / libération :**

La contrainte `UNIQUE(merchant_id, type, idempotency_key)` sur `gepay_ledger_entries`, combinée au `lockForUpdate()` sur le wallet dans une `DB::transaction()`, garantit que `payout_reserve` et `payout_release` ne peuvent être exécutés **qu'une seule fois** par demande, même en cas de :
- webhook dupliqué
- retry de job (Queue)
- concurrence entre réconciliation et webhook simultanés

L'idempotency_key du ledger est construite à partir de `payout_request.id` + type d'écriture.
Une seconde tentative avec la même clé retourne l'entrée existante sans nouvelle écriture ni modification du wallet.

---

### Tables existantes modifiées

#### `gepay_clients` → ajouter `merchant_id` (nullable)

```sql
ALTER TABLE gepay_clients
  ADD COLUMN merchant_id BIGINT UNSIGNED NULL
    REFERENCES gepay_merchants(id) ON DELETE RESTRICT;
```

- `NULL` = client interne BantuDelice géré par `GePayInternalClientResolver` — **non touché**.
- Non-null = client rattaché à un marchand GePay.

**Colonne reste nullable** jusqu'à exécution et validation du backfill contrôlé, puis migration séparée pour NOT NULL.

#### `gepay_transactions` → ajouter `merchant_id` + remplacer cascade

**Migration A** — ajout colonne (non-destructive) :
```sql
ALTER TABLE gepay_transactions
  ADD COLUMN merchant_id BIGINT UNSIGNED NULL
    REFERENCES gepay_merchants(id) ON DELETE RESTRICT;
```

**Colonne reste nullable** jusqu'à validation du backfill.

`merchant_id` est toujours **déduit côté serveur** du `GePayClient` associé :
```
gepay_transactions.merchant_id = gepay_clients.merchant_id
  WHERE gepay_clients.id = gepay_transactions.client_id
```

Aucun code n'accepte `merchant_id` comme paramètre entrant pour cette colonne.

**Invariant d'intégrité** :
```
gepay_transactions.merchant_id MUST EQUAL gepay_clients.merchant_id
  FOR ALL rows WHERE both are NOT NULL
```
Divergence = incident de corruption.

**Migration B** — cascade → restrict (DDL sensible) :

> ⚠️ **Warning — Migration B :** Modifie la contrainte FK `gepay_transactions.client_id`.  
> Ne supprime aucune donnée. Empêche tout `DELETE` sur `gepay_clients` si des transactions existent.  
> Test rollback staging obligatoire avant production. `down()` : recréer `ON DELETE CASCADE`.

---

## Backfill contrôlé — Commande `gepay:link-internal-merchant`

Le backfill générique est **interdit**. Commande Artisan dédiée, exécution manuelle avec validation.

```
php artisan gepay:link-internal-merchant [--dry-run] [--force]
```

Étapes dans l'ordre :

1. Retrouver ou créer le marchand interne via slug stable `bantudelice-internal`.  
   Ambiguïté (plusieurs résultats) → **échec immédiat**.

2. Retrouver le client interne via `config('gepay.bantudelice.client_uuid')` (`GEPAY_INTERNAL_CLIENT_UUID`).  
   Absent / null / introuvable / plusieurs résultats → **échec immédiat**.

3. Afficher **volumes avant** : nombre de lignes `gepay_clients` sans `merchant_id`, nombre de `gepay_transactions` sans `merchant_id`, somme `amount` concernée.

4. Rattacher uniquement ce client et ses transactions (`merchant_id` déduit de `client_id`). Aucune autre ligne touchée.

5. Afficher **volumes après**. Confirmation interactive si `--force` absent.

**Conditions d'échec** : UUID absent, correspondance absente ou ambiguë, slug ambigu → stop explicite.

---

## Flux financiers

### `gepay_ledger_entries` = source de vérité

`available`, `pending`, `reserved` dans `gepay_wallets` sont des **projections**.  
En cas de divergence, le ledger fait foi. Un job de réconciliation peut recalculer les colonnes wallet.

### Formule de réconciliation

```
wallet.available = SUM(amount WHERE destination_bucket = 'available')
                 - SUM(amount WHERE source_bucket      = 'available')

wallet.pending   = SUM(amount WHERE destination_bucket = 'pending')
                 - SUM(amount WHERE source_bucket      = 'pending')

wallet.reserved  = SUM(amount WHERE destination_bucket = 'reserved')
                 - SUM(amount WHERE source_bucket      = 'reserved')
```

Calculé sur `gepay_ledger_entries WHERE wallet_id = ?`.

**Contrôle de réconciliation :**
- Exécuté périodiquement (job planifié) et à la demande.
- Toute divergence → alerte immédiate (log `critical` + notification monitoring).
- **Jamais** de correction silencieuse. Toute correction → écriture compensatrice avec `note`.
- Un rapport de réconciliation est conservé avec horodatage, résultat et identité du déclencheur.

### Règles d'écriture financière (toutes opérations)

Chaque écriture wallet + ledger doit :

1. S'exécuter dans `DB::transaction()`
2. Verrouiller la ligne wallet avec `->lockForUpdate()`
3. Vérifier `available >= montant` avant tout débit (solde négatif interdit — enforced DB + code)
4. Calculer `balance_after` **après** verrouillage, pas avant
5. Fournir `idempotency_key` — double soumission retourne l'entrée existante, aucune nouvelle écriture
6. Entiers XAF exclusivement — aucun `float`, aucun `round()`
7. `UPDATE gepay_wallets SET available = X` direct = **interdit** — toujours via ledger
8. Correction → écriture compensatrice obligatoire avec `note` — aucun `UPDATE`/`DELETE` sur entrées existantes

### Transitions d'état complètes

```
── Encaisser (Collection) ──────────────────────────────────────────────────
Collection soumise (USSD envoyé)
  → type: collection_pending   | src: NULL       | dst: pending
Collection confirmée (webhook SUCCESSFUL)
  → type: collection_confirm   | src: pending    | dst: available
Collection échouée (webhook FAILED)
  → type: collection_fail      | src: pending    | dst: NULL
Collection expirée (webhook EXPIRED / timeout)
  → type: collection_fail      | src: pending    | dst: NULL
Collection annulée (webhook CANCELLED)
  → type: collection_fail      | src: pending    | dst: NULL

── Envoyer (Disbursement) ──────────────────────────────────────────────────
Disbursement soumis
  → type: disbursement_debit   | src: available  | dst: NULL
Disbursement échoué / rejeté / expiré
  → type: disbursement_refund  | src: NULL       | dst: available   ← compensateur

── Payout (Reversement) ────────────────────────────────────────────────────
Payout draft → submitted (destination vérifiée, solde OK)
  → type: payout_reserve       | src: available  | dst: reserved
Payout processing → successful
  → type: payout_debit         | src: reserved   | dst: NULL
Payout processing/submitted → failed / cancelled / expired
  → type: payout_release       | src: reserved   | dst: available   ← compensateur

── Frais ───────────────────────────────────────────────────────────────────
Frais prélevés
  → type: fee_debit            | src: available  | dst: NULL

── Corrections (admin GePay uniquement) ────────────────────────────────────
Crédit correctif
  → type: adjustment_credit    | src: NULL       | dst: available   | note obligatoire
Débit correctif
  → type: adjustment_debit     | src: available  | dst: NULL        | note obligatoire
```

---

## Relations

```
gepay_merchants (1)
  ├── (N) gepay_merchant_users
  ├── (N) gepay_clients               merchant_id nullable → NOT NULL après backfill validé
  ├── (N) gepay_transactions          merchant_id nullable → NOT NULL après backfill validé
  │         invariant: tx.merchant_id = client.merchant_id (WHERE both NOT NULL)
  ├── (N) gepay_payout_destinations
  ├── (1) gepay_wallets [par currency, UNIQUE(merchant_id, currency)]
  │     ├── CHECK available >= 0
  │     ├── CHECK pending   >= 0
  │     ├── CHECK reserved  >= 0
  │     └── (N) gepay_ledger_entries  ← source de vérité, immuable
  └── (N) gepay_payout_requests
              ├── FK→gepay_payout_destinations (snapshot immuable chiffré)
              └── machine d'état: draft→submitted→processing→{successful|failed|cancelled|expired}
```

---

## Risques de migration

| Risque | Impact | Mitigation |
|---|---|---|
| `gepay_clients` + col `merchant_id` nullable | Faible — non-destructif | Reste nullable jusqu'à backfill validé |
| `gepay_transactions` + col `merchant_id` nullable | Faible — non-destructif | Reste nullable jusqu'à backfill validé |
| FK cascade → restrict sur `gepay_transactions.client_id` | Moyen — DDL lock bref | Fenêtre maintenance, rollback staging obligatoire |
| Backfill via `gepay:link-internal-merchant` | Moyen — écriture contrôlée | `--dry-run` obligatoire avant `--force` |
| Passage `merchant_id` nullable → NOT NULL post-backfill | Faible — migration séparée | Après validation backfill uniquement |
| CHECK constraints `>= 0` sur wallet (si DB ancienne) | Faible | Vérifier version MySQL/MariaDB (8.0.16+ ou MariaDB 10.2+) |
| Divergence wallet / ledger découverte post-migration | Élevé si présent | Job réconciliation avant mise en service, alerte obligatoire |

---

## Navigation layout `gepay.blade.php`

Toutes les routes sont relatives au sous-domaine `gepay.bantudelice.cg` — pas de préfixe `/gepay`.

```
Auth (hors middleware) :
  GET  /login    → formulaire de connexion
  POST /logout   → déconnexion (CSRF protégé)

Sidebar (middleware auth:gepay) :
  ● Tableau de bord    GET /
  ● Transactions       GET /transactions
  ● Envoyer            GET /envoyer   POST /envoyer
  ● Encaisser          GET /encaisser POST /encaisser
  ● Payout             GET /payout    POST /payout

Header :
  Nom marchand  |  Solde : {wallet->available} XAF  |  [Déconnexion POST /logout]
```

Solde affiché = `gepay_wallets.available` en entiers XAF (pas de division). Aucun `/100`.

---

## Tests minimums obligatoires

```
AuthTest
  ✓ login valide → session gepay créée
  ✓ login invalide → 422
  ✓ logout POST → session détruite, redirect /gepay/login
  ✓ marchand status=suspended → login refusé

IsolationTest
  ✓ merchant_A ne voit pas transactions merchant_B
  ✓ merchant_id URL param ignoré — scope vient du guard uniquement

TransactionTest
  ✓ liste filtrée par merchant authentifié
  ✓ filtres date / statut / opérateur / type fonctionnels
  ✓ merchant_id toujours déduit du GePayClient côté serveur

WalletTest
  ✓ available / pending / reserved cohérents avec ledger (réconciliation)
  ✓ CHECK available >= 0 enforced — débit sur solde nul refusé
  ✓ création double wallet (même merchant+currency) → erreur UNIQUE
  ✓ double soumission idempotente → même résultat, pas de double écriture
  ✓ fonds insuffisants → erreur, wallet + ledger inchangés
  ✓ réservation payout → available-- / reserved++ / entrée payout_reserve
  ✓ payout rejeté → reserved-- / available++ / entrée payout_release
  ✓ webhook dupliqué payout_release → idempotence, pas de double libération
  ✓ retry job payout_reserve → idempotence, pas de double réservation
  ✓ collection échouée → pending-- / entrée collection_fail
  ✓ collection expirée → même comportement que échouée
  ✓ disbursement échoué → available++ / entrée disbursement_refund

PayoutTest
  ✓ destination verified=false → refusé avant toute écriture ledger
  ✓ destination verified=true → statut draft puis submitted / ledger payout_reserve
  ✓ destination_snapshot immuable après création
  ✓ destination_snapshot ne contient aucune valeur complète (masquée uniquement)
  ✓ payout vers destination soft-deleted → refusé
  ✓ payout successful → ledger payout_debit / reserved--
  ✓ payout expired → ledger payout_release / reserved-- / available++

ReconciliationTest
  ✓ réconciliation OK quand wallet = agrégat ledger
  ✓ divergence artificielle → alerte levée, pas de correction silencieuse

BackfillTest
  ✓ gepay:link-internal-merchant --dry-run → 0 écriture, volumes affichés
  ✓ GEPAY_INTERNAL_CLIENT_UUID absent → échec explicite
  ✓ ambiguïté client ou marchand → échec explicite

InvariantTest
  ✓ tx.merchant_id == client.merchant_id pour toutes transactions rattachées

MigrationTest
  ✓ rollback complet de toutes les migrations GePay dans l'ordre inverse

LayoutTest
  ✓ layout desktop (1280px) sans overflow
  ✓ layout mobile (375px) sans overflow
```

---

## Conséquences

**Acceptées :**
- Portail totalement isolé de l'admin BantuDelice
- Isolation multi-tenant par guard, jamais par paramètre URL
- Journal financier immuable — audit et réconciliation possibles à tout moment
- Agrégats wallet = projections du ledger, jamais source primaire
- Payout vers destination non vérifiée = impossible par design
- Suppression physique des entités financières = interdite
- Données de destination chiffrées au repos, jamais exposées en clair
- Idempotence partagée par `(merchant_id, type)` = intentionnelle

**Différées (Phase 2) :**
- Webhooks marchands avancés
- Multi-utilisateurs par marchand avec rôles fins
- Statistiques graphiques temps réel
- Widget JS intégrable (iframe / SDK)
- Remboursements et gestion des litiges
- Séparation idempotence par client API si besoin (inclure `client_id` dans la clé)

---

**Statut : Accepted — 2026-07-01**
