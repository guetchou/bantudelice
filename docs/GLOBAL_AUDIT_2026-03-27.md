# Audit global Bantudelice

## Constat

- Le socle routes/views passe les audits statiques existants: routes nommees resolues, formulaires/fetch alignes, guards modules coherents, pas de fuite evidente de routes rolees dans les vues publiques.
- La dette majeure n'est pas un bug unique, mais un empilement de duplication legacy et de couverture metier inegale.
- Le backoffice utilise massivement [`resources/views/layouts/app.blade.php`](/root/bantudelice-prod-audit/resources/views/layouts/app.blade.php), tandis que [`resources/views/app.blade.php`](/root/bantudelice-prod-audit/resources/views/app.blade.php) reste un duplicat divergent encore maintenu.
- Le CMS couvre creation, edition, transitions editoriales et suppression. La dette residuelle porte surtout sur les scenarios E2E editoriaux complets.
- Les E2E Playwright ont ete etendus aux parcours publics, aux comptes multi-roles et aux viewports mobiles, mais l'execution navigateur authentifiee reste dependante du runtime Chromium du runner.
- Les payouts et incidents existent dans le code applicatif, mais n'ont presque pas de tests de regression dedies.

## Ecarts structurels

- Les backups frontend `*.bak*` identifies pendant l'audit ont ete purges du depot de travail.
- OVH et WSL sont maintenant alignes sur le code source utile (`app/routes/resources/tests/scripts/database`), avec une validation applicative faite apres sync.

## Trous de couverture

- CMS profond:
  creation/edition/suppression presentes, mais pas encore de scenario E2E editorial complet.
- Parcours editoriaux CMS:
  la dette residuelle porte surtout sur un scenario navigateur editorial complet; le CRUD et la suppression sont deja verrouilles par tests feature.
- Execution navigateur authentifiee:
  les specs Playwright multi-roles existent, mais leur execution depend d'un runner Chromium compatible. En attendant, les parcours deployes ont ete valides via fallback HTTP reel multi-role et multi-viewport.

## Plan global de correction

1. Unifier le layout admin et supprimer la maintenance parallele de [`resources/views/app.blade.php`](/root/bantudelice-prod-audit/resources/views/app.blade.php).
2. Ajouter un scenario navigateur editorial CMS complet quand un runner Playwright Chromium stable est disponible.
3. Maintenir les tests feature sur payouts, signalements, redelivery et resolution.
4. Rejouer les specs Playwright authentifiees sur un runner navigateur compatible pour la validation visuelle finale.
5. Continuer a purger les reliquats legacy uniquement apres verification d'usage.

## Routine ciblee

- Audit unifie: `npm run audit:global`
- Audit de sync OVH: `BD_RSYNC_TARGET=vps-ovh:/opt/bantudelice/ npm run audit:global`
- E2E navigateur: `npm run e2e`
- Validation HTTP multi-role de secours: `php scripts/e2e_http_validate.php`
