#!/usr/bin/env bash

set -euo pipefail

APP_ROOT="${1:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
PHP_BIN="${PHP_BIN:-/usr/bin/php}"
WEB_USER="${WEB_USER:-www-data}"
PASSPORT_PERSONAL_CLIENT_NAME="${PASSPORT_PERSONAL_CLIENT_NAME:-BantuDelice Personal Access Client}"

info() {
    printf '[INFO] %s\n' "$1"
}

success() {
    printf '[OK] %s\n' "$1"
}

fail() {
    printf '[ERROR] %s\n' "$1" >&2
    exit 1
}

require_cmd() {
    command -v "$1" >/dev/null 2>&1 || fail "Commande requise manquante: $1"
}

require_cmd "$PHP_BIN"
require_cmd chown
require_cmd chmod
require_cmd find
require_cmd runuser

[[ -f "$APP_ROOT/artisan" ]]             || fail "Racine Laravel introuvable: $APP_ROOT"
[[ -f "$APP_ROOT/vendor/autoload.php" ]] || fail "vendor/autoload.php absent — lancer composer install d'abord"

cd "$APP_ROOT"

oauth_summary_json() {
    "$PHP_BIN" <<'PHP'
<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
echo json_encode([
    'oauth_clients'                 => (int) Illuminate\Support\Facades\DB::table('oauth_clients')->count(),
    'oauth_personal_access_clients' => (int) Illuminate\Support\Facades\DB::table('oauth_personal_access_clients')->count(),
    'oauth_access_tokens'           => (int) Illuminate\Support\Facades\DB::table('oauth_access_tokens')->count(),
], JSON_UNESCAPED_SLASHES), PHP_EOL;
PHP
}

oauth_personal_access_count() {
    "$PHP_BIN" <<'PHP'
<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
echo (int) Illuminate\Support\Facades\DB::table('oauth_personal_access_clients')->count(), PHP_EOL;
PHP
}

ensure_passport_keys() {
    if [[ -f "storage/oauth-private.key" && -f "storage/oauth-public.key" ]]; then
        info "Clés Passport présentes"
        return
    fi

    info "Clés Passport absentes, génération minimale"
    "$PHP_BIN" artisan passport:keys --force --no-interaction
}

ensure_personal_access_client() {
    local count
    count="$(oauth_personal_access_count)"

    if [[ "$count" != "0" ]]; then
        info "Client Passport personnel déjà présent (count=$count)"
        return
    fi

    info "Création du client Passport personnel"
    "$PHP_BIN" artisan passport:client \
        --personal \
        --name="$PASSPORT_PERSONAL_CLIENT_NAME" \
        --no-interaction
}

fix_runtime_permissions() {
    info "Correction des permissions runtime Laravel"
    mkdir -p storage/logs bootstrap/cache
    chown -R "$WEB_USER:$WEB_USER" storage bootstrap/cache
    find storage bootstrap/cache -type d -exec chmod 775 {} +
    find storage bootstrap/cache -type f -exec chmod 664 {} +
}

refresh_laravel_runtime() {
    info "Purge et reconstruction légère des caches Laravel via $WEB_USER"
    # NOTE: artisan optimize est intentionnellement absent (risque bootstrap/cache en prod)
    runuser -u "$WEB_USER" -- "$PHP_BIN" artisan optimize:clear
    runuser -u "$WEB_USER" -- "$PHP_BIN" artisan view:cache
}

info "État OAuth avant correction: $(oauth_summary_json)"
ensure_passport_keys
ensure_personal_access_client
fix_runtime_permissions
refresh_laravel_runtime
info "État OAuth après correction: $(oauth_summary_json)"
success "Réparation auth/runtime terminée"
