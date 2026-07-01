#!/usr/bin/env bash
set -euo pipefail

PROJECT_PATH="/opt/bantudelice"
BACKUPS_ROOT="/opt/backups/bantudelice"
DEPLOY_SHA="${DEPLOY_SHA:-unknown}"
PREVIOUS_SHA=$(cat "$BACKUPS_ROOT/.previous_sha" 2>/dev/null || true)
LATEST_BACKUP=$(ls -td "$BACKUPS_ROOT"/*/ 2>/dev/null | head -1 || true)

echo "Déploiement ou smoke test en échec. Rollback du code uniquement."

if [ -z "$PREVIOUS_SHA" ]; then
  echo "Aucun commit précédent connu : intervention manuelle requise."
  exit 1
fi

cd "$PROJECT_PATH"
git reset --hard "$PREVIOUS_SHA"

composer install \
  --no-interaction \
  --prefer-dist \
  --optimize-autoloader \
  --no-dev || true

php artisan optimize:clear || true
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true
php artisan cache:clear || true
php artisan horizon:terminate || true

systemctl reload php8.3-fpm || sudo systemctl reload php8.3-fpm || true
php artisan queue:restart || true
supervisorctl restart bantudelice-worker:* \
  || sudo supervisorctl restart bantudelice-worker:* \
  || true
php artisan horizon:status || true
sudo /usr/local/bin/bantudelice-fix-permissions.sh \
  || echo "Avertissement : restauration des droits impossible."

echo "============================================================"
echo "ROLLBACK CODE TERMINÉ — DÉCISION HUMAINE REQUISE POUR LA DB"
echo "Commit déployé en échec : $DEPLOY_SHA"
echo "Commit restauré         : $PREVIOUS_SHA"

if [ -n "$LATEST_BACKUP" ]; then
  echo "Dernier dump DB : ${LATEST_BACKUP}database.sql"
  echo "Horodatage       : $(basename "$LATEST_BACKUP")"
  if [ -f "${LATEST_BACKUP}.migrations_ran" ]; then
    echo "Des migrations ont été exécutées avant l'échec."
    echo "Une restauration DB effacerait toute donnée écrite depuis."
    echo "Restauration manuelle uniquement :"
    echo "docker exec -i bantudelice-db-new mysql -u <DB_USERNAME> <DB_DATABASE> < ${LATEST_BACKUP}database.sql"
  else
    echo "Aucune migration n'a tourné pendant ce déploiement."
  fi
else
  echo "Aucun dump DB trouvé pour ce déploiement."
fi

echo "============================================================"
