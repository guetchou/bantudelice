# BantuDelice — Contexte de session

> **LIRE EN PREMIER. METTRE À JOUR EN DERNIER.**
> Ce fichier est le contrat entre sessions. Chaque agent qui ouvre ce projet
> doit le lire avant toute modification et le mettre à jour avant de terminer.

---

## Bases de données — LIRE EN PREMIER

Le VPS a **deux bases MySQL** dans le container `bantudelice-db-new` (port 3336) :

| Base | État | Usage |
|---|---|---|
| `bantudelice` | **VIDE** — tables créées le 17/05, jamais peuplées | Future base prod réelle |
| `bantudelice_repro` | 10 users, 8 restaurants, 10 livreurs | **Base démo active** |

**Le `.env` doit pointer vers `DB_DATABASE=bantudelice_repro`** tant qu'il n'y a pas de clients réels.

Le site est en pré-lancement — aucun client réel. Voir [docs/demo-accounts.md](docs/demo-accounts.md) pour tous les comptes et mots de passe.

---

## État stable connu

- **470/470 tests PHPUnit verts** (suite complète)
- **Déploiement** : rsync WSL → vps-ovh:/opt/bantudelice via `scripts/deploy.sh`
- **Post-deploy obligatoire** : `ssh vps-ovh "php /opt/bantudelice/artisan view:clear && php /opt/bantudelice/artisan cache:clear"`

---

## Ce qui est stable — NE PAS TOUCHER sans raison documentée

| Fichier / Zone | Pourquoi stable |
|---|---|
| `app/Charge.php` | `$fillable` corrigé (delivery_fee/service_fee) — bug prod réparé |
| `config/bantudelice_state_machine.php` | Transitions corrigées (pending_restaurant_acceptance → driver_assigned) |
| `app/Domain/Food/Enums/OrderPaymentStatus.php` | Enum centralisé — 9 fichiers l'utilisent |
| `app/Domain/Food/Services/OrderPricingService.php` | Calcul service_fee corrigé (float, formule validée) |
| `app/Order.php` | SoftDeletes actif — utiliser assertSoftDeleted dans les tests |
| `public/images/home/service-*.jpg` | 5 images existantes utilisées par la homepage |
| `public/frontend/css/modern.css` | CSS homepage — **voir section Régressions CSS ci-dessous** |

---

## Régressions CSS connues — À CORRIGER (ne pas aggraver)

Le fichier `public/frontend/css/modern.css` actuel (9721 lignes) est **incomplet** par rapport au backup du 16 mai (12632 lignes). Manquent notamment :

- `.journey-section` / `.jstep*` / `.jmock*` — section parcours de commande animé
- `.hub-section` / `.hub-card*` — section écosystème
- `.hero2*` — ancien hero (peut-être intentionnellement retiré)
- Styles pages driver, offers, legal, faq (lignes 10811–12577 dans le backup)

**Backup de référence** : `public/frontend/css/modern.css.bak.20260516_174454` (12632 lignes, dernier état complet connu)

**Règle** : si tu touches le CSS, ne réécris pas le fichier entier. Ajoute uniquement ce qui manque en diff ciblé.

---

## Images homepage

Les images dans `public/images/home/` disponibles :
- `service-restaurant.jpg`
- `service-driver.jpg`
- `service-cuisine.jpg`
- `service-colis.jpg`
- `service-transport.jpg`

**Toute nouvelle référence à une image dans le blade doit avoir le fichier physique correspondant.**
Ne jamais référencer `food-orb*.jpg`, `food-hero.jpg`, `food-mosaic*.jpg`, `food-team.jpg` — ces fichiers n'existent pas.

---

## Règles de session obligatoires

### Avant de commencer
1. `git status` — vérifier qu'on part du bon état
2. `git checkout -b session/YYYYMMDD-description` — travailler sur une branche
3. Écrire ici en une phrase le périmètre de la session

### Périmètre strict
- Une session = un périmètre déclaré
- Si tu touches le blade, **ne touche pas** le CSS dans la même session
- Si tu touches le CSS, **ne touche pas** le blade
- Si tu touches le backend, **ne touche pas** le frontend

### Avant de déployer
1. `php artisan test --no-coverage` → doit rester 470/470
2. `git diff` → relire chaque changement
3. `git commit` avec message descriptif
4. Deploy + cache clear

### Après la session
Mettre à jour ce fichier : état stable, ce qui a changé, ce qui reste à faire.

---

## Prochaines tâches connues

- [ ] Réparer le CSS homepage : restaurer les 3111 lignes manquantes depuis `modern.css.bak.20260516_174454`
- [ ] Valider visuellement la homepage après correction CSS
- [ ] Initialiser git sur le VPS également (`ssh vps-ovh "cd /opt/bantudelice && git init && git add . && git commit -m 'baseline prod'"`)

---

## Session en cours

**Date** : —
**Périmètre** : —
**Branche** : —
