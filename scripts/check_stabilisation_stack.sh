#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
COMPOSE_FILE="${COMPOSE_FILE:-$ROOT_DIR/deploy/compose.stabilisation.yml}"
ENV_FILE="${ENV_FILE:-$ROOT_DIR/deploy/.env.stabilisation}"

fail() {
  printf '[ERROR] %s\n' "$1" >&2
  exit 1
}

ok() {
  printf '[OK] %s\n' "$1"
}

command -v docker >/dev/null 2>&1 || fail "Docker est requis"
docker compose version >/dev/null 2>&1 || fail "Docker Compose v2 est requis"
[[ -f "$COMPOSE_FILE" ]] || fail "Fichier Compose introuvable: $COMPOSE_FILE"
[[ -f "$ENV_FILE" ]] || fail "Fichier d'environnement introuvable: $ENV_FILE"

docker compose --env-file "$ENV_FILE" -f "$COMPOSE_FILE" config --quiet
ok "Configuration Docker Compose valide"

docker compose --env-file "$ENV_FILE" -f "$COMPOSE_FILE" ps

REDIS_STATUS=$(docker inspect --format='{{if .State.Health}}{{.State.Health.Status}}{{else}}{{.State.Status}}{{end}}' bantudelice-redis 2>/dev/null || true)
[[ "$REDIS_STATUS" == "healthy" ]] || fail "Redis n'est pas sain (statut: ${REDIS_STATUS:-absent})"
ok "Redis répond et son healthcheck est sain"

KUMA_STATUS=$(docker inspect --format='{{.State.Status}}' bantudelice-uptime-kuma 2>/dev/null || true)
[[ "$KUMA_STATUS" == "running" ]] || fail "Uptime Kuma n'est pas démarré (statut: ${KUMA_STATUS:-absent})"
ok "Uptime Kuma est démarré"

printf '\nPile de stabilisation opérationnelle.\n'
