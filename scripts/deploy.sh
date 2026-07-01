#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
TARGET_HOST="${1:-vps-ovh}"
TARGET_PATH="${2:-/opt/bantudelice}"
PHP_BIN="${PHP_BIN:-/usr/bin/php}"
WEB_USER="${WEB_USER:-www-data}"
RUN_AUTH_SMOKE="${RUN_AUTH_SMOKE:-0}"
DRY_RUN="${DRY_RUN:-0}"
SMOKE_SCRIPT="$ROOT_DIR/scripts/prod_auth_smoke.py"
RUNTIME_REPAIR_SCRIPT="$ROOT_DIR/scripts/repair_auth_runtime.sh"

info() { printf '[INFO] %s\n' "$1"; }
success() { printf '[OK] %s\n' "$1"; }
warn() { printf '[WARN] %s\n' "$1" >&2; }
fail() { printf '[ERROR] %s\n' "$1" >&2; exit 1; }

command -v rsync >/dev/null 2>&1 || fail "rsync est requis"
command -v ssh >/dev/null 2>&1 || fail "ssh est requis"

[[ -f "$ROOT_DIR/composer.json" ]] || fail "Racine Laravel introuvable: $ROOT_DIR"
[[ -f "$ROOT_DIR/composer.lock" ]] || fail "composer.lock est requis"
[[ -f "$RUNTIME_REPAIR_SCRIPT" ]] || fail "Script de réparation runtime introuvable: $RUNTIME_REPAIR_SCRIPT"

if [[ "$RUN_AUTH_SMOKE" == "1" ]]; then
    [[ -f "$SMOKE_SCRIPT" ]] || fail "Script de smoke test introuvable: $SMOKE_SCRIPT"
fi

if [[ "$DRY_RUN" == "1" ]]; then
    info "Mode DRY-RUN activé — aucune écriture sur le serveur"
fi

info "Pre-flight : connexion SSH vers ${TARGET_HOST}"
if ! ssh -o BatchMode=yes -o ConnectTimeout=10 "$TARGET_HOST" "echo ok" >/dev/null 2>&1; then
    fail "Impossible de joindre ${TARGET_HOST} — annulation avant rsync"
fi
success "Pre-flight SSH OK"

REMOTE_FREE_MB=$(ssh "$TARGET_HOST" "df -m '$TARGET_PATH' 2>/dev/null | awk 'NR==2{print \$4}'" 2>/dev/null || echo "0")
if [[ "$REMOTE_FREE_MB" -lt 500 ]]; then
    warn "Espace disque faible sur le VPS : ${REMOTE_FREE_MB} Mo disponibles (seuil 500 Mo)"
fi

info "Déploiement vers ${TARGET_HOST}:${TARGET_PATH}"

RSYNC_ARGS=(
    -az
    --delete
    --exclude=.git/
    --exclude=.codex-backups/
    --exclude=.env
    --exclude=.env.backup*
    --exclude=vendor/
    --exclude=node_modules/
    --exclude=storage/
    --exclude=bootstrap/cache/
    --exclude=test-results/
    --exclude="*.bak-psr4"
    --exclude="*.bak.*"
    --exclude=".phpunit.cache/"
    --exclude="public/images/cms/"
    --exclude="public/images/restaurant_images/"
    --exclude="public/images/product_images/"
    --exclude="public/images/driver_images/"
    --exclude="public/images/profile_images/"
    --exclude="public/images/banner_images/"
    --exclude="soketi.json"
    --exclude="firebase-credentials.json"
)

if [[ "$DRY_RUN" == "1" ]]; then
    info "DRY-RUN rsync (--dry-run) :"
    rsync "${RSYNC_ARGS[@]}" --dry-run --itemize-changes "$ROOT_DIR/" "${TARGET_HOST}:${TARGET_PATH}/"
    success "DRY-RUN terminé — aucun fichier modifié"
    exit 0
fi

info "Synchronisation des sources"
rsync "${RSYNC_ARGS[@]}" "$ROOT_DIR/" "${TARGET_HOST}:${TARGET_PATH}/"

ssh "$TARGET_HOST" "php ${TARGET_PATH}/artisan config:clear --quiet 2>/dev/null; php ${TARGET_PATH}/artisan route:clear --quiet 2>/dev/null; php ${TARGET_PATH}/artisan view:clear --quiet 2>/dev/null" || true

REMOTE_CMD=$(cat <<EOF
set -euo pipefail
cd "$TARGET_PATH"
mkdir -p storage/logs bootstrap/cache
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress
PHP_BIN="$PHP_BIN" WEB_USER="$WEB_USER" bash "$TARGET_PATH/scripts/repair_auth_runtime.sh" "$TARGET_PATH"

if [ -f "$TARGET_PATH/soketi.json.template" ] && [ -f "$TARGET_PATH/.env" ]; then
    PUSHER_APP_ID=\$(grep '^PUSHER_APP_ID=' "$TARGET_PATH/.env" | cut -d= -f2 | tr -d '"')
    PUSHER_APP_KEY=\$(grep '^PUSHER_APP_KEY=' "$TARGET_PATH/.env" | cut -d= -f2 | tr -d '"')
    PUSHER_APP_SECRET=\$(grep '^PUSHER_APP_SECRET=' "$TARGET_PATH/.env" | cut -d= -f2 | tr -d '"')
    if [ -n "\$PUSHER_APP_ID" ] && [ -n "\$PUSHER_APP_KEY" ] && [ -n "\$PUSHER_APP_SECRET" ]; then
        sed \
            -e "s/\\\${PUSHER_APP_ID}/\$PUSHER_APP_ID/g" \
            -e "s/\\\${PUSHER_APP_KEY}/\$PUSHER_APP_KEY/g" \
            -e "s/\\\${PUSHER_APP_SECRET}/\$PUSHER_APP_SECRET/g" \
            "$TARGET_PATH/soketi.json.template" > "$TARGET_PATH/soketi.json"
        chmod 640 "$TARGET_PATH/soketi.json"
    fi
fi

if command -v node >/dev/null 2>&1 && [ -f "$TARGET_PATH/soketi.json" ]; then
    if ! command -v soketi >/dev/null 2>&1; then
        npm install -g @soketi/soketi 2>/dev/null || true
    fi
    if [ ! -f /etc/systemd/system/soketi-bantudelice.service ]; then
        cp "$TARGET_PATH/scripts/soketi.service" /etc/systemd/system/soketi-bantudelice.service
        systemctl daemon-reload
        systemctl enable soketi-bantudelice
    fi
    systemctl restart soketi-bantudelice 2>/dev/null || true
fi

php "$TARGET_PATH/artisan" view:clear --quiet 2>/dev/null || true
php "$TARGET_PATH/artisan" config:cache --quiet 2>/dev/null || true
php "$TARGET_PATH/artisan" route:cache --quiet 2>/dev/null || true

if php "$TARGET_PATH/artisan" list --raw 2>/dev/null | grep -qx 'horizon:terminate'; then
    php "$TARGET_PATH/artisan" horizon:terminate --quiet || true
fi
EOF
)

info "Finalisation Laravel sur le serveur"
if ! ssh "$TARGET_HOST" "$REMOTE_CMD"; then
    fail "Finalisation échouée — relancer: ./scripts/bd_ops.py rollback-auth-runtime --backup-dir <dir> --yes"
fi

if [[ "$RUN_AUTH_SMOKE" == "1" ]]; then
    info "Smoke test auth/runtime"
    python3 "$SMOKE_SCRIPT"
fi

success "Déploiement terminé"
