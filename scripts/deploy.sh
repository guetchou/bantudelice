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

info() {
    printf '[INFO] %s\n' "$1"
}

success() {
    printf '[OK] %s\n' "$1"
}

warn() {
    printf '[WARN] %s\n' "$1" >&2
}

fail() {
    printf '[ERROR] %s\n' "$1" >&2
    exit 1
}

command -v rsync >/dev/null 2>&1 || fail "rsync est requis"
command -v ssh   >/dev/null 2>&1 || fail "ssh est requis"

[[ -f "$ROOT_DIR/composer.json" ]] || fail "Racine Laravel introuvable: $ROOT_DIR"
[[ -f "$RUNTIME_REPAIR_SCRIPT" ]] || fail "Script de réparation runtime introuvable: $RUNTIME_REPAIR_SCRIPT"

if [[ "$RUN_AUTH_SMOKE" == "1" ]]; then
    [[ -f "$SMOKE_SCRIPT" ]] || fail "Script de smoke test introuvable: $SMOKE_SCRIPT"
fi

if [[ "$DRY_RUN" == "1" ]]; then
    info "Mode DRY-RUN activé — aucune écriture sur le serveur"
fi

# ── Pre-flight : vérifier que le VPS répond avant tout rsync ────────────────
info "Pre-flight : connexion SSH vers ${TARGET_HOST}"
if ! ssh -o BatchMode=yes -o ConnectTimeout=10 "$TARGET_HOST" "echo ok" >/dev/null 2>&1; then
    fail "Impossible de joindre ${TARGET_HOST} — annulation avant rsync"
fi
success "Pre-flight SSH OK"

# ── Pre-flight : espace disque disponible (seuil 500 Mo) ────────────────────
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
    # Dossiers d'uploads dynamiques — jamais supprimés par rsync
    --exclude="public/images/cms/"
    --exclude="public/images/restaurant_images/"
    --exclude="public/images/product_images/"
    --exclude="public/images/driver_images/"
    --exclude="public/images/profile_images/"
    --exclude="public/images/banner_images/"
)

if [[ "$DRY_RUN" == "1" ]]; then
    info "DRY-RUN rsync (--dry-run) :"
    rsync "${RSYNC_ARGS[@]}" --dry-run --itemize-changes "$ROOT_DIR/" "${TARGET_HOST}:${TARGET_PATH}/"
    success "DRY-RUN terminé — aucun fichier modifié"
    exit 0
fi

info "Synchronisation des sources"
rsync "${RSYNC_ARGS[@]}" "$ROOT_DIR/" "${TARGET_HOST}:${TARGET_PATH}/"

# ── Finalisation sur le serveur ──────────────────────────────────────────────
REMOTE_CMD=$(cat <<EOF
set -euo pipefail
cd "$TARGET_PATH"
mkdir -p storage/logs bootstrap/cache
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
PHP_BIN="$PHP_BIN" WEB_USER="$WEB_USER" bash "$TARGET_PATH/scripts/repair_auth_runtime.sh" "$TARGET_PATH"
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
