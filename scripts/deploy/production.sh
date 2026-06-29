#!/usr/bin/env bash
set -euo pipefail

: "${DEPLOY_SHA:?DEPLOY_SHA est requis}"

PROJECT_PATH="/opt/bantudelice"
PROJECT_NAME="bantudelice"
DB_CONTAINER="bantudelice-db-new"
BACKUPS_ROOT="/opt/backups/bantudelice"

echo "Starting deployment of $PROJECT_NAME (target: $DEPLOY_SHA)..."
cd "$PROJECT_PATH"

# Ne jamais écraser des modifications serveur suivies par Git.
if [ -n "$(git status --porcelain | grep -v '^??')" ]; then
  echo "Fichiers suivis modifiés sur le serveur — déploiement annulé :"
  git status --porcelain | grep -v '^??'
  exit 1
fi

PREVIOUS_SHA=$(git rev-parse HEAD)
mkdir -p "$BACKUPS_ROOT"
echo "$PREVIOUS_SHA" > "$BACKUPS_ROOT/.previous_sha"
echo "Commit actuellement en production : $PREVIOUS_SHA"

BACKUP_DIR="$BACKUPS_ROOT/$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"
echo "Répertoire de sauvegarde : $BACKUP_DIR"
[ -f .env ] && cp .env "$BACKUP_DIR/.env.backup"

if docker ps --format '{{.Names}}' | grep -qx "$DB_CONTAINER"; then
  echo "Sauvegarde de la base de données..."
  DB_NAME=$(grep -E '^DB_DATABASE=' .env | cut -d= -f2-)
  DB_USER=$(grep -E '^DB_USERNAME=' .env | cut -d= -f2-)
  DB_PASS=$(grep -E '^DB_PASSWORD=' .env | cut -d= -f2-)
  docker exec -e MYSQL_PWD="$DB_PASS" "$DB_CONTAINER" \
    mysqldump -u "$DB_USER" "$DB_NAME" > "$BACKUP_DIR/database.sql" 2>/dev/null \
    || echo "Avertissement : sauvegarde DB impossible."
else
  echo "Avertissement : conteneur $DB_CONTAINER introuvable, sauvegarde DB ignorée."
fi

echo "Récupération du commit $DEPLOY_SHA..."
git fetch origin
git reset --hard "$DEPLOY_SHA"

composer install \
  --no-interaction \
  --prefer-dist \
  --optimize-autoloader \
  --no-dev

MIN_NODE_MAJOR=18 REQUIRE_EXACT_NODE_MAJOR=0 \
  bash scripts/ci/verify_frontend_build.sh

MIGRATIONS_RAN="false"
PENDING=$(php artisan migrate:status 2>/dev/null | grep -c 'Pending' || true)
if [ "$PENDING" != "0" ]; then
  echo "Migrations en attente : activation du mode maintenance court."
  php artisan down --retry=15 || true
  if php artisan migrate --force; then
    MIGRATIONS_RAN="true"
    touch "$BACKUP_DIR/.migrations_ran"
  else
    echo "Échec des migrations."
    php artisan up || true
    exit 1
  fi
  php artisan up
else
  echo "Aucune migration en attente."
fi
echo "migrations_ran=$MIGRATIONS_RAN"

# Règle projet : uniquement les commandes *:clear en production.
php artisan optimize:clear || true
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true
php artisan cache:clear || true

systemctl reload php8.3-fpm \
  || sudo systemctl reload php8.3-fpm \
  || echo "Avertissement : rechargement PHP-FPM impossible."

php artisan queue:restart || true
supervisorctl restart bantudelice-worker:* \
  || sudo supervisorctl restart bantudelice-worker:* \
  || echo "Avertissement : redémarrage des workers impossible."

# Dernière opération : restaurer les droits après les commandes artisan.
# Le wrapper sudo est nécessaire quand le déploiement tourne sous un compte non-root.
sudo /usr/local/bin/bantudelice-storage-chown.sh \
  || chown -R www-data:www-data "$PROJECT_PATH/storage/" \
  || echo "Avertissement : restauration des droits storage/ impossible."

echo "Déploiement terminé. HEAD=$(git rev-parse HEAD)"
