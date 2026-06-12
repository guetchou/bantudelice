#!/bin/bash
# Upload off-site vers OVH Object Storage (S3-compatible) via rclone
# Exécuté après backup_db.sh (cron 02:30 quotidien)
set -euo pipefail

BACKUP_DIR="/opt/bantudelice/storage/backups"
RCLONE_REMOTE="ovh-bantudelice"       # nom du remote dans ~/.config/rclone/rclone.conf
RCLONE_BUCKET="bantudelice-backups"   # nom du container OVH Object Storage
RETENTION_DAYS=30                      # garder 30j en off-site (vs 14j local)
LOG_FILE="/var/log/bantudelice-backup-offsite.log"

log() { echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" | tee -a "$LOG_FILE"; }

# Vérifier que rclone est installé
if ! command -v rclone &>/dev/null; then
    log "ERREUR: rclone non installé. Exécuter: /opt/bantudelice/scripts/install_rclone_ovh.sh"
    exit 1
fi

# Vérifier que le remote est configuré
if ! rclone listremotes | grep -q "^${RCLONE_REMOTE}:"; then
    log "ERREUR: remote rclone '${RCLONE_REMOTE}' non configuré."
    log "Exécuter: /opt/bantudelice/scripts/install_rclone_ovh.sh"
    exit 1
fi

# Vérifier qu'il y a des fichiers à uploader
LATEST=$(find "$BACKUP_DIR" -name '*.sql.gz' -mmin -60 | sort -r | head -1)
if [[ -z "$LATEST" ]]; then
    log "Aucun backup récent trouvé dans $BACKUP_DIR (< 60 min). Rien à uploader."
    exit 0
fi

# Upload le backup le plus récent
FILENAME=$(basename "$LATEST")
log "Upload: $FILENAME → ${RCLONE_REMOTE}:${RCLONE_BUCKET}/"

rclone copy "$LATEST" "${RCLONE_REMOTE}:${RCLONE_BUCKET}/" \
    --s3-no-check-bucket \
    --checksum \
    2>>"$LOG_FILE"

log "Upload OK : ${RCLONE_REMOTE}:${RCLONE_BUCKET}/${FILENAME}"

# Nettoyage off-site : supprimer fichiers > RETENTION_DAYS jours
log "Nettoyage off-site : suppression fichiers > ${RETENTION_DAYS}j"
rclone delete "${RCLONE_REMOTE}:${RCLONE_BUCKET}/" \
    --min-age "${RETENTION_DAYS}d" \
    2>>"$LOG_FILE" || true

# Lister les backups présents en remote (vérification)
log "Backups off-site actuels :"
rclone ls "${RCLONE_REMOTE}:${RCLONE_BUCKET}/" 2>>"$LOG_FILE" | tee -a "$LOG_FILE"
