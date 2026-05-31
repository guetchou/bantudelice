#!/bin/bash
# Backup automatique BantuDelice — quotidien via cron
set -euo pipefail

BACKUP_DIR="/opt/bantudelice/storage/backups"
RETENTION_DAYS=14
DATE=$(date +%Y%m%d_%H%M%S)
FILE="${BACKUP_DIR}/bantudelice_repro_${DATE}.sql.gz"

mkdir -p "$BACKUP_DIR"

mysqldump \
  -h 127.0.0.1 -P 3336 \
  -u bantudelice -pBantuDb2026! \
  --single-transaction \
  --routines \
  --triggers \
  bantudelice_repro 2>/dev/null \
  | gzip > "$FILE"

SIZE=$(du -sh "$FILE" | cut -f1)
echo "[$(date)] Backup créé : $FILE ($SIZE)"

# Nettoyage : garder seulement les $RETENTION_DAYS derniers jours
find "$BACKUP_DIR" -name '*.sql.gz' -mtime "+${RETENTION_DAYS}" -delete
echo "[$(date)] Nettoyage : fichiers de plus de ${RETENTION_DAYS} jours supprimés"
