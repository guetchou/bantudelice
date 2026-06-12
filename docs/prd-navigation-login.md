# BantuDelice — Product Requirements Document

**Version** : 1.0 — 2026-05-19
**Projet** : BantuDelice — Plateforme food delivery, Congo/Brazzaville
**Stack** : Laravel 10, PHP 8.1, Blade templates, MySQL
**Base démo** : `bantudelice_repro` (10 users, 8 restaurants, 10 livreurs)
**Déploiement** : rsync WSL → VPS via `scripts/deploy.sh`

---

## Executive Summary

BantuDelice est une plateforme food delivery opérant en République du Congo (Brazzaville). L'application gère quatre profils d'utilisateurs (admin, restaurant, driver, client) et coexiste dans un écosystème multi-marques incluant Kende (transport VTC) et Mema (livraison colis).

Ce PRD couvre trois axes d'amélioration validés en session de conception :

1. **Connexion multi-canal** (Session C — priorité haute) : permettre aux livreurs et utilisateurs sans email de se connecter via téléphone ou nom d'utilisateur.
2. **Drawer profil contextuel** (Session A — priorité haute) : accès rapide au profil, sécurité et déconnexion depuis la topbar, sans quitter le dashboard.
3. **Workspace switcher admin** (Session B — priorité normale) : rendre fonctionnel le sélecteur BantuDelice/Kende/Mema pour filtrer réellement la sidebar et les KPIs.

Aucune de ces fonctionnalités ne casse l'existant. Le seuil de non-régression est 470/470 tests PHPUnit verts.

---

## Problèmes

### P1 — Connexion bloquante pour les livreurs sans email

**Constat** : La page `auth/login.blade.php` expose un unique champ `type="email"` avec `name="email"`. Le `LoginController` cherche uniquement par `email`. La table `users` a une colonne `phone` mais pas de `username`.

**Impact** : Au Congo, le numéro de téléphone est le canal d'identification dominant (mobile money, MTN, Airtel). Les livreurs recrutés sur le terrain n'ont souvent pas d'adresse email active. Ils ne peuvent pas se connecter.

**Périmètre** : `LoginController`, `auth/login.blade.php`, table `users`, migration `username`.

---

### P2 — Accès au profil inexistant depuis le dashboard

**Constat** : Après login, l'avatar topbar est décoratif. L'accès au profil impose une navigation hors du dashboard courant.

**Impact** : Friction utilisateur pour tout changement de mot de passe, déconnexion propre, accès aux commandes/revenus.

**Périmètre** : Layout `admin-modern.blade.php`, layout `restaurant_app.blade.php`, topbar, routes déconnexion.

---

### P3 — Workspace switcher décoratif

**Constat** : Les tabs BantuDelice/Kende/Mema transmettent `?workspace=` mais la sidebar et les KPIs restent identiques quel que soit le workspace.

**Impact** : Un admin sur "Mema" voit des KPIs food. Confusion sans valeur.

**Périmètre** : `admin-modern.blade.php`, `admin/home.blade.php`, contrôleur dashboard admin.

---

## Solutions

### S1 — Connexion multi-canal

#### Détection automatique du canal

| Saisie | Règle | Canal |
|---|---|---|
| Contient `@` | Regex email | `email` |
| Commence par `+242`, `06`, `05`, `04` ou chiffres uniquement | Regex téléphone | `phone` |
| Sinon | Fallback | `username` |

#### Composants modifiés

- `LoginController` : override `credentials()` avec détection automatique
- `auth/login.blade.php` : champ `identifier` unique (pas de `type="email"`)
- Migration : `add_username_to_users_table` (nullable, unique, index)
- `User` model : `username` dans `$fillable`

#### Règles UX

- Un seul champ, pas de tabs de mode
- Message d'erreur générique : "Identifiant ou mot de passe incorrect"
- Placeholder : "Email, téléphone ou nom d'utilisateur"

---

### S2 — Drawer profil contextuel

#### Structure

```
[Overlay semi-transparent]
  └── [Drawer latéral droit — 380px desktop, 100% mobile]
        ├── Header : avatar + nom + rôle
        ├── Lien → page profil complète
        ├── Accordéon "Changer mot de passe" (mini-formulaire POST)
        ├── Liens contextuels selon rôle :
        │     Admin      : Paramètres plateforme
        │     Restaurant : Paramètres resto, Revenus
        │     Driver     : Mes livraisons, Mes gains
        │     Client     : Mes commandes, Mes paiements
        └── Bouton "Se déconnecter" → form POST /logout + @csrf
```

#### Règles

- HTML statique Blade, zéro Ajax
- `auth()->user()` avec null-safe guards
- Fermeture : bouton X + clic overlay + touche Escape
- Classes préfixées `bd-drawer-*` (pas de collision)
- Avatar bas de sidebar reste décoratif

---

### S3 — Workspace switcher fonctionnel

#### Mapping

| `?workspace=` | Sidebar | KPIs | Titre |
|---|---|---|---|
| `bantudelice` (défaut) | Restaurants, Commandes, Produits, Livreurs | Commandes jour, CA, Restaurants actifs | Pilotage BantuDelice |
| `kende` | Réservations, Véhicules, Chauffeurs, Zones | Courses jour, Revenus transport, Chauffeurs actifs | Pilotage Kende |
| `mema` | Colis, Expéditions, Points relais, Tarifs | Colis jour, Livraisons en cours | Pilotage Mema |

#### Règles d'accès

| Rôle | Comportement |
|---|---|
| Super admin | Voit le switcher, peut basculer librement |
| Restaurant | Jamais exposé au switcher ni au layout admin |
| Driver | Idem |
| Client | Idem |

#### Contraintes

- Pas de nouveau layout, pas de duplication
- Pas de nouvelles routes
- Les `href` de sidebar propagent `?workspace=`

---

## Critères d'acceptation

### S1

| # | Critère |
|---|---|
| AC-1.1 | Login avec numéro de téléphone (`+242 06 700 00 01`) fonctionne |
| AC-1.2 | Login avec email existant non cassé |
| AC-1.3 | Login avec username fonctionne si renseigné |
| AC-1.4 | Saisie invalide → message générique, pas d'exception |
| AC-1.5 | Migration idempotente |
| AC-1.6 | 470/470 tests verts |

### S2

| # | Critère |
|---|---|
| AC-2.1 | Clic avatar topbar → drawer s'ouvre |
| AC-2.2 | Clic overlay → drawer se ferme |
| AC-2.3 | Escape → drawer se ferme |
| AC-2.4 | Contenu varie selon le rôle |
| AC-2.5 | Changement MDP → POST valide + confirmation |
| AC-2.6 | Déconnexion → POST + CSRF |
| AC-2.7 | Avatar sidebar sans comportement |
| AC-2.8 | Zéro requête Ajax à l'ouverture |
| AC-2.9 | 470/470 tests verts |

### S3

| # | Critère |
|---|---|
| AC-3.1 | `?workspace=kende` → sidebar transport uniquement |
| AC-3.2 | `?workspace=mema` → KPIs colis |
| AC-3.3 | `?workspace=bantudelice` → comportement actuel inchangé |
| AC-3.4 | Liens sidebar préservent `?workspace=` |
| AC-3.5 | Rôle non-admin ne peut pas accéder au workspace switcher |
| AC-3.6 | Aucune nouvelle route créée |
| AC-3.7 | 470/470 tests verts |

---

## Contraintes techniques non négociables

| Contrainte | Valeur |
|---|---|
| Framework | Laravel 10 |
| PHP | 8.1 |
| Templates | Blade uniquement |
| Tests | 470/470 PHPUnit verts avant tout déploiement |
| Déploiement | rsync WSL → VPS, `bootstrap/cache` exclu |
| `artisan optimize` | Jamais après déploiement |
| Migrations destructives | Jamais sans validation explicite |

---

## Plan de livraison

### Session A — Drawer profil (aujourd'hui)

| Livrable | Fichier |
|---|---|
| Partial `_profile_drawer.blade.php` | `resources/views/admin/partials/` |
| Intégration layout admin | `resources/views/layouts/admin-modern.blade.php` |
| Intégration layout restaurant | `resources/views/layouts/restaurant_app.blade.php` |
| JS vanilla fermeture | Inline dans le partial |
| Vérification 470 tests | `php artisan test` |

**Rollback** : supprimer le partial + retirer `@include` dans les layouts. Zéro migration.

---

### Session C — Connexion multi-canal

| Livrable | Fichier |
|---|---|
| Migration `add_username_to_users_table` | `database/migrations/` |
| Override `LoginController::credentials()` | `app/Http/Controllers/LoginController.php` |
| Mise à jour `auth/login.blade.php` | `resources/views/auth/login.blade.php` |
| `username` dans `User::$fillable` | `app/User.php` |
| Tests `LoginMultiCanalTest` (3 modes) | `tests/Feature/Auth/` |

**Rollback** : `migrate:rollback` + revert LoginController + revert blade.

---

### Session B — Workspace switcher (après A et C)

| Livrable | Fichier |
|---|---|
| `$workspace` propagé depuis contrôleur | `app/Http/Controllers/admin/` |
| Sidebar conditionnelle | `resources/views/layouts/admin-modern.blade.php` |
| KPIs conditionnels | `resources/views/admin/home.blade.php` |
| Propagation `?workspace=` dans les `href` | `resources/views/admin/partials/` |
| Tests `WorkspaceSwitcherTest` | `tests/Feature/Admin/` |

**Rollback** : revert Blade + contrôleur. Zéro migration.

---

## Fichiers clés

- `app/Http/Controllers/LoginController.php` — S1
- `resources/views/auth/login.blade.php` — S1
- `resources/views/layouts/admin-modern.blade.php` — S2, S3
- `resources/views/layouts/restaurant_app.blade.php` — S2
- `resources/views/admin/partials/control_hub_nav.blade.php` — S3
- `resources/views/admin/home.blade.php` — S3
