# Rapport d'audit — Remédiation pipeline CI/CD et dérive VPS

**Date :** 2026-06-22 / 2026-06-23
**Branche Git :** main
**Commit final :** `eca457a7e910506ce9620a2738845f0dece7fdb0` (identique local ↔ VPS)
**Environnement :** Production — vps-ovh (bantudelice.cg)

---

## (1) Executive summary

Le pipeline GitHub Actions (`quality_gate → deploy → smoke_test → rollback`) ne s'était jamais exécuté de bout en bout avec succès. Cinq causes racines distinctes ont été identifiées et corrigées séquentiellement : dérive git du VPS (historique diverge de GitHub), absence de remote git sur le VPS (déploiement automatique impossible), ordre incorrect du `chown` post-déploiement (500 sur la première requête après chaque déploiement), test unitaire fragile sur une fenêtre minuit, et durcissement supply-chain (SHA pinning) + sécurité (signature de callback paiement, garde-fou frontend npm).

**Décision clé :** résolution complète et propre (option A — pas de contournement, pas de `git reset --hard` brutal, pas de déploiement forcé). Le pipeline est aujourd'hui **opérationnel de bout en bout** et le site est en **200** sur le commit `eca457a`.

**Risques résiduels :** issue #10 (chaîne de build npm/Laravel Mix cassée) reste ouverte et bloque tout changement de source frontend dans le pipeline — c'est un garde-fou actif, pas un oubli. `docs/test-accounts.md` contient des identifiants démo en clair sur disque VPS (décision de traitement différée à l'utilisateur, déjà documentée en mémoire de session antérieure).

---

## (2) Inventory — État de l'environnement

| Élément | État |
|---|---|
| Branche Git locale | `main` @ `eca457a` |
| Branche Git VPS | `main` @ `eca457a` (identique, `git diff` vide) |
| PHP | 8.3-FPM (`active`, reload sans coupure) |
| Nginx | `active` |
| MySQL | conteneur Docker `bantudelice-db-new` (Up 7 semaines) — pas de service systemd `mysql` (attendu) |
| Queue workers | 2/2 `RUNNING` (supervisor) |
| Espace disque VPS (`/opt`) | 39G disponibles / 193G (81% utilisé) |
| Site live | `https://bantudelice.cg/` → **HTTP 200** |
| Issue GitHub #10 | **OPEN** — `chore(frontend): réparer la chaîne build npm Laravel Mix/Webpack` (garde-fou actif, conforme) |
| Deploy Key GitHub | `VPS-bantudelice-deploy-readonly` (id 155210250, read_only=true, créée 2026-06-22T22:26:45Z) |
| Permissions | Accès SSH root sur VPS, accès `gh` admin sur le repo — aucune permission manquante |

---

## (3) Actions effectuées — Priorité, type, risque

| # | Action | Priorité | Type | Risque | Validation requise | Exécuté |
|---|---|---|---|---|---|---|
| 1 | Audit dérive git VPS (39 fichiers trackés modifiés vs HEAD VPS) | 1 | audit | aucun | non | ✅ |
| 2 | Comparaison fichier par fichier VPS vs GitHub main (tous changements déjà présents côté GitHub, sauf 1 régression sécurité rejetée) | 1 | audit | aucun | non | ✅ |
| 3 | `git checkout -- .` sur VPS (révert fichiers trackés vers HEAD VPS, untracked non touchés) | 2 | non-destructive | faible (changements déjà dans GitHub main, vérifié au préalable) | non | ✅ |
| 4 | Rejet de la régression sécurité VPS (`routes/web.php` : middleware `auth` retiré de `track.order`) | 1 | audit + décision | élevé si appliqué | implicite (sécurité) | ✅ (rejeté, GitHub main conservé) |
| 5 | Génération clé SSH dédiée `github_deploy` sur VPS + Deploy Key GitHub read-only | 2 | non-destructive | faible (clé read-only, scope = 1 repo) | non | ✅ |
| 6 | Configuration remote git `origin` sur VPS via alias SSH `github-bantudelice` | 2 | non-destructive | faible | non | ✅ |
| 7 | Fix garde-fou deploy : exclure fichiers non-trackés (`grep -v '^??'`) | 2 | non-destructive | aucun (étend la portée de blocage, ne la réduit pas) | non | ✅ |
| 8 | Ajout SSH/`environment: production` dans le job `smoke_test` | 2 | non-destructive | aucun | non | ✅ |
| 9 | Pin SHA de toutes les actions GitHub (`checkout`, `setup-php`, `ssh-agent`) | 3 | non-destructive (durcissement supply-chain) | aucun | non | ✅ |
| 10 | Retrait `npm ci`/`npm run production` du pipeline + garde-fou source frontend bloquant | 2 | non-destructive | aucun (assets déjà commités et à jour) | non | ✅ |
| 11 | Création issue #10 (chantier différé chaîne build npm) | 3 | documentation | aucun | non | ✅ |
| 12 | Fix ordre `chown -R www-data:www-data storage/` (déplacé en toute dernière étape, après `queue:restart`) | 1 | non-destructive | aucun (chown read/write seulement, pas de suppression) | non | ✅ |
| 13 | Fix `chown` immédiat sur VPS en attendant le déploiement du correctif | 1 | non-destructive (correctif manuel ponctuel) | aucun | non | ✅ |
| 14 | Fix test flaky `PaymentDashboardServiceTest` (`Carbon::setTestNow()`) | 3 | non-destructive | aucun (test uniquement, logique prod inchangée) | non | ✅ |
| 15 | Correctif sécurité `PaymentCallbackController` : vérification de signature déplacée avant le traitement transport/colis (callback forgé pouvait confirmer un paiement sans vérification PSP) | 1 | sécurité (OWASP A8) | élevé si non corrigé | non (déjà mergé, PR #11) | ✅ |
| 16 | Hotfix `app/Order.php` : `displayStatus()`/`resolveTrackingStatus()` — `accepted` → `prepairing` au lieu de `pending` | 3 | fonctionnel | faible | non | ✅ |

---

## (4) Backup paths — Sauvegardes et justification

| Élément | Sauvegarde | Justification |
|---|---|---|
| Historique git VPS (avant réécriture, session antérieure 2026-06-18) | `/opt/backups/bantudelice/bantudelice-git-backup-20260618_125054.tar.gz` (53,8 Mo) | Tarball complet du `.git` avant `git-filter-repo`. Intégrité vérifiée (`tar tzf`). Restauration : `tar xzf <archive> -C /opt/bantudelice` après avoir vidé le `.git` actuel. |
| Dérive git VPS (cette session) | `/opt/backups/bantudelice/vps-drift-20260622_224343/audit.txt` (26 428 octets) + `full.patch` (277 479 octets) | Audit texte + patch complet du diff VPS-vs-GitHub avant `git checkout -- .`. Permet de réappliquer manuellement n'importe quel changement si un oubli était découvert. |
| Fichiers trackés modifiés sur le VPS | **Aucune sauvegarde séparée** — rollback Git suffisant | Tous les changements vérifiés identiques ou strictement inférieurs à GitHub main avant `checkout`. Le commit `eca457a` (et tout commit antérieur sur `main`) constitue le point de restauration. |
| Base de données | `/opt/backups/bantudelice/db_20260622_021501.sql.gz` (73 504 octets, cron quotidien 02:15, rétention 14j) | Aucune migration de schéma n'a été exécutée durant cette session (vérifié via `migrate:status` dans le script de déploiement — `PENDING=0` à chaque run observé). |
| Déploiements (par run pipeline) | `/opt/backups/bantudelice/<timestamp>/` créé automatiquement par le script `deploy` (`.env.backup`, `database.sql` si conteneur DB up) | Mécanisme intégré au pipeline, un dossier par déploiement. |

**Aucune sauvegarde de fichier séparée pour `.github/workflows/deploy.yml`, `app/Order.php`, `tests/*` :** fichiers suivis par Git — rollback Git suffisant (`git revert <hash>` ou `git checkout <hash> -- <fichier>`).

---

## (5) Tests et vérifications reproduites

| Vérification | Commande | Résultat |
|---|---|---|
| Suite de tests complète | `php artisan test` | **576/576 passed** (1671 assertions), 139,57s |
| Test flaky isolé (stabilité) | `php artisan test --filter=PaymentDashboardServiceTest` ×3 | 3/3 passed après fix |
| Test régression display status | `php artisan test --filter=OrderStatusResolutionTest` | passed (mis à jour pour `accepted → prepairing`) |
| Lint PHP | `php -l` sur tous les fichiers modifiés (étape `quality_gate`) | 0 erreur de syntaxe |
| Intégrité routes | `php artisan route:list` (étape `quality_gate`) | OK, pas de route cassée |
| Pipeline complet (run final) | `gh run watch` sur commit `eca457a` | **Quality Gate ✓ (2m10s) → Deploy ✓ (18s) → Smoke Tests ✓ (10s)** — Rollback non déclenché |
| Smoke tests post-déploiement | `curl` homepage/login/restaurants/checkout/track-order/API health + scan logs 500 | 7/7 passed |
| Site live (vérification manuelle finale) | `curl -s -o /dev/null -w '%{http_code}' https://bantudelice.cg/` | **200** |
| Connexion VPS → GitHub | `ssh vps-ovh "cd /opt/bantudelice && git fetch origin main"` | OK, `FETCH_HEAD` résolu |
| Drift git résiduel | `git diff <HEAD VPS> <HEAD local>` | **vide** (synchronisation parfaite confirmée à la rédaction de ce rapport) |

**Aucun test impossible à exécuter** — environnement CI (GitHub Actions) et VPS tous deux accessibles avec permissions suffisantes.

---

## (6) Rollback steps

| Risque couvert | Commande de rollback | Note |
|---|---|---|
| Déploiement applicatif en échec | Automatique — job `rollback` du pipeline (`needs.deploy.result == 'failure' \|\| needs.smoke_test.result == 'failure'`), revient à `PREVIOUS_SHA` mémorisé avant chaque déploiement | Code uniquement — DB toujours manuelle (règle projet) |
| Régression sur `.github/workflows/deploy.yml` | `git revert bab52d3 21fa00c eca457a` (ou `git checkout <commit antérieur> -- .github/workflows/deploy.yml`) | Aucun impact DB |
| Régression `app/Order.php` (hotfix display status) | `git revert 3f57ff0` | ⚠️ Réintroduit le bug display status corrigé — à valider avant tout revert |
| Deploy Key GitHub compromise (hypothèse) | `gh api repos/guetchou/bantudelice/keys/155210250 --method DELETE` puis régénérer une nouvelle paire sur le VPS | La clé est read-only — impact limité même en cas de compromission (pas de push possible) |
| Retour à l'état VPS pré-session (hypothèse extrême) | `tar xzf /opt/backups/bantudelice/bantudelice-git-backup-20260618_125054.tar.gz -C /opt/bantudelice` (historique git) + restauration DB depuis `db_20260622_021501.sql.gz` | **Non recommandé** — perdrait tous les correctifs sécurité (callback paiement) de cette session. À n'utiliser qu'en cas d'incident majeur non résolu autrement. |

**Point de restauration DB recommandé :** `db_20260622_021501.sql.gz` (avant cette session, aucune migration exécutée pendant la session donc ce backup reste valide).

---

## (7) Diff/patchs appliqués — Récapitulatif des commits

```
eca457a test(payment): figer l'horloge dans PaymentDashboardServiceTest (flake minuit)
bab52d3 fix(deploy): déplacer chown storage/ après queue:restart (toujours dernier)
21fa00c fix(deploy): chown storage/ après cache:clear pour éviter 500 post-deploy
8f5746e ci: tester le pipeline après fix remote GitHub VPS
b78f8ea test(order): mettre à jour OrderStatusResolutionTest pour accepted → prepairing
fa664a3 fix(ci): garde-fou deploy — exclure fichiers non-trackés du check
a2b4f30 hotfix(vps): résolution dérive git VPS + display status commandes
2b2be69 docs: ajouter roadmap des lacunes techniques L1-L9
3f57ff0 fix(order): corriger la logique de display status pour accepted/in_kitchen
c9ad17c ci: déclencher la validation du pipeline complet post-correctifs
d42e4f5 security(ci): épingler toutes les actions GitHub au SHA de commit
264167a fix(ci): ajouter SSH + environment au job smoke_test
3c3c183 chore(ci): retirer npm du pipeline, ajouter garde-fou sources frontend
```

PR mergée : **#11** (hotfix VPS + roadmap + correctif sécurité `PaymentCallbackController`/`PaymentService`/`checkout.js`).

Pour le détail ligne-à-ligne de chaque modification : `git show <hash>` ou `git diff <hash>~1 <hash>`.

---

## Score final

**Pipeline CI/CD : opérationnel de bout en bout (quality_gate → deploy → smoke_test).**
**Site production : 200, commit `eca457a`, VPS synchronisé à l'octet avec GitHub main.**
**Dette ouverte documentée :** issue #10 (build npm), `docs/test-accounts.md` (identifiants démo en clair, décision utilisateur en attente).
