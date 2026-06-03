#!/bin/bash
# Backup automatique BantuDelice — quotidien via cron
set -euo pipefail

ENV_FILE="/opt/bantudelice/.env"
BACKUP_DIR="/opt/bantudelice/storage/backups"
RETENTION_DAYS=14
DATE=$(date +%Y%m%d_%H%M%S)

# Lire credentials depuis .env (jamais hardcodés)
db_password() { grep "^DB_PASSWORD=" "$ENV_FILE" | cut -d= -f2- | tr -d '"' | tr -d "'"; }
db_user()     { grep "^DB_USERNAME=" "$ENV_FILE" | cut -d= -f2- | tr -d '"' | tr -d "'"; }
db_port()     { grep "^DB_PORT="     "$ENV_FILE" | cut -d= -f2- | tr -d '"' | tr -d "'"; }
db_host()     { grep "^DB_HOST="     "$ENV_FILE" | cut -d= -f2- | tr -d '"' | tr -d "'"; }
db_name()     { grep "^DB_DATABASE=" "$ENV_FILE" | cut -d= -f2- | tr -d '"' | tr -d "'"; }

DB_HOST=$(db_host)
DB_PORT=$(db_port)
DB_USER=$(db_user)
DB_PASS=$(db_password)
DB_NAME=$(db_name)
FILE="${BACKUP_DIR}/${DB_NAME}_${DATE}.sql.gz"

mkdir -p "$BACKUP_DIR"

mysqldump \
  -h "$DB_HOST" -P "$DB_PORT" \
  -u "$DB_USER" -p"$DB_PASS" \
  --single-transaction \
  --routines \
  --triggers \
  "$DB_NAME" 2>/dev/null \
  | gzip > "$FILE"

SIZE=$(du -sh "$FILE" | cut -f1)
echo "[$(date)] Backup créé : $FILE ($SIZE)"

# Nettoyage : garder seulement les $RETENTION_DAYS derniers jours
find "$BACKUP_DIR" -name '*.sql.gz' -mtime "+${RETENTION_DAYS}" -delete
echo "[$(date)] Nettoyage : fichiers de plus de ${RETENTION_DAYS} jours supprimés"
