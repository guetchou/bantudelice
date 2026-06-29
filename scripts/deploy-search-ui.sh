#!/usr/bin/env bash
set -Eeuo pipefail

APP_DIR="${APP_DIR:-/opt/bantudelice}"
WEB_USER="${WEB_USER:-www-data}"
REQUIRED_COMMIT="d15bc009f61269c5db1ef741321338d018907f67"
EXPECTED_MARKER="searchFiltersOpen"

log()  { printf '\n\033[1;34m[search-ui]\033[0m %s\n' "$*"; }
ok()   { printf '\033[1;32m[OK]\033[0m %s\n' "$*"; }
fail() { printf '\033[1;31m[ERREUR]\033[0m %s\n' "$*" >&2; exit 1; }

[[ -d "$APP_DIR/.git" ]] || fail "Dépôt Git absent : $APP_DIR"
cd "$APP_DIR"

log "Contrôle du dépôt"
CURRENT_BRANCH="$(git branch --show-current)"
[[ "$CURRENT_BRANCH" == "main" ]] || fail "Branche active : $CURRENT_BRANCH. Passez sur main avant le déploiement."

if [[ -n "$(git status --porcelain)" ]]; then
    git status --short
    fail "Le dépôt contient des changements locaux. Aucun fichier n'a été modifié. Sauvegardez/stashez ces changements avant de relancer."
fi

BEFORE_HEAD="$(git rev-parse HEAD)"
printf 'Commit avant déploiement : %s\n' "$BEFORE_HEAD"

git fetch --prune origin main
git merge --ff-only origin/main

AFTER_HEAD="$(git rev-parse HEAD)"
printf 'Commit après déploiement : %s\n' "$AFTER_HEAD"

git merge-base --is-ancestor "$REQUIRED_COMMIT" HEAD \
    || fail "La refonte de recherche $REQUIRED_COMMIT n'est pas présente dans HEAD."
ok "Le commit de refonte est inclus dans la version déployée"

log "Contrôle des fichiers"
required_files=(
    "resources/views/frontend/search-v2.blade.php"
    "public/frontend/css/pages/search.css"
    "public/frontend/js/pages/search.js"
    "tests/Feature/CatalogSearchWorkflowTest.php"
)
for file in "${required_files[@]}"; do
    [[ -s "$file" ]] || fail "Fichier absent ou vide : $file"
    ok "$file"
done

grep -q 'frontend/css/pages/search.css' resources/views/frontend/search-v2.blade.php \
    || fail "La vue ne charge pas le CSS de recherche."
grep -q 'frontend/js/pages/search.js' resources/views/frontend/search-v2.blade.php \
    || fail "La vue ne charge pas le JavaScript de recherche."
grep -q "$EXPECTED_MARKER" resources/views/frontend/search-v2.blade.php \
    || fail "Le marqueur du nouveau design est absent de la vue."

log "Préparation des répertoires Laravel"
mkdir -p \
    storage/framework/views \
    storage/framework/sessions \
    storage/framework/cache/data \
    storage/logs \
    bootstrap/cache

if [[ "$(id -u)" -eq 0 ]] && id "$WEB_USER" >/dev/null 2>&1; then
    chown -R "$WEB_USER:$WEB_USER" storage bootstrap/cache
fi
find storage bootstrap/cache -type d -exec chmod 775 {} +
find storage bootstrap/cache -type f -exec chmod 664 {} +

log "Purge des caches applicatifs"
rm -f bootstrap/cache/config.php bootstrap/cache/events.php bootstrap/cache/routes-*.php
find storage/framework/views -mindepth 1 -maxdepth 1 -type f -delete
php artisan optimize:clear

log "Contrôle des routes"
ROUTES="$(php artisan route:list --path=search --columns=method,uri,name,action 2>&1 || php artisan route:list --path=search 2>&1)"
printf '%s\n' "$ROUTES"
grep -q 'CatalogSearchController' <<<"$ROUTES" \
    || fail "La route /search n'utilise pas CatalogSearchController."

log "Tests fonctionnels de la recherche"
php artisan test --filter=CatalogSearchWorkflowTest

log "Compilation des vues"
php artisan view:cache

log "Rechargement de PHP-FPM si disponible"
if command -v systemctl >/dev/null 2>&1 && [[ "$(id -u)" -eq 0 ]]; then
    mapfile -t fpm_services < <(systemctl list-units --type=service --state=running --no-legend 2>/dev/null \
        | awk '{print $1}' | grep -E '^php[0-9.]+-fpm\.service$' || true)
    if (( ${#fpm_services[@]} > 0 )); then
        for service in "${fpm_services[@]}"; do
            systemctl reload "$service"
            ok "Service rechargé : $service"
        done
    else
        printf 'Aucun service PHP-FPM actif détecté.\n'
    fi
fi

APP_URL="$(awk -F= '/^APP_URL=/{sub(/^APP_URL=/, ""); gsub(/^"|"$/, ""); print; exit}' .env 2>/dev/null || true)"
APP_URL="${APP_URL%/}"

if [[ -n "$APP_URL" ]] && command -v curl >/dev/null 2>&1; then
    log "Vérification HTTP : $APP_URL/search"
    HTML_FILE="$(mktemp)"
    trap 'rm -f "$HTML_FILE"' EXIT

    PAGE_CODE="$(curl -k -L -sS --max-time 30 -o "$HTML_FILE" -w '%{http_code}' "$APP_URL/search" || true)"
    [[ "$PAGE_CODE" == "200" ]] || fail "La page /search répond HTTP $PAGE_CODE."
    grep -q "$EXPECTED_MARKER" "$HTML_FILE" \
        || fail "Le HTML public ne contient pas $EXPECTED_MARKER : ancienne vue, cache intermédiaire ou mauvais document root."
    grep -q 'frontend/css/pages/search.css' "$HTML_FILE" \
        || fail "Le HTML public ne référence pas le nouveau CSS."
    ok "Le HTML public contient le nouveau design"

    for asset in frontend/css/pages/search.css frontend/js/pages/search.js; do
        CODE="$(curl -k -L -sS --max-time 30 -o /dev/null -w '%{http_code}' "$APP_URL/$asset" || true)"
        [[ "$CODE" == "200" ]] || fail "Asset inaccessible ($CODE) : $APP_URL/$asset"
        ok "HTTP 200 : $asset"
    done
else
    printf 'APP_URL absent ou curl indisponible : contrôle HTTP ignoré.\n'
fi

log "Déploiement terminé"
printf 'HEAD : %s\n' "$AFTER_HEAD"
printf 'Recherche : %s/search\n' "${APP_URL:-URL_NON_DETECTEE}"
printf 'Marqueur attendu : %s\n' "$EXPECTED_MARKER"
