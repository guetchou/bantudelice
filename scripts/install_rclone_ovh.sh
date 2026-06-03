#!/bin/bash
# Installation et configuration rclone pour OVH Object Storage (S3-compatible)
# À exécuter UNE SEULE FOIS sur le VPS en tant que root
set -euo pipefail

# ─────────────────────────────────────────────
# RENSEIGNER CES VARIABLES AVANT D'EXÉCUTER
# (récupérer dans OVH Control Panel > Public Cloud > Object Storage > S3 API Credentials)
# ─────────────────────────────────────────────
OVH_ACCESS_KEY=""        # ex: ABCDEF1234567890
OVH_SECRET_KEY=""        # ex: abc123def456...
OVH_ENDPOINT="s3.gra.io.cloud.ovh.net"   # GRA=Gravelines, SBG=Strasbourg, DE=Frankfurt
OVH_REGION="gra"                          # gra | sbg | de | bhs
OVH_BUCKET="bantudelice-backups"          # nom du container à créer dans OVH
RCLONE_REMOTE="ovh-bantudelice"           # nom du remote rclone (cohérent avec backup_offsite.sh)
# ─────────────────────────────────────────────

if [[ -z "$OVH_ACCESS_KEY" || -z "$OVH_SECRET_KEY" ]]; then
    echo "ERREUR: Renseigner OVH_ACCESS_KEY et OVH_SECRET_KEY avant d'exécuter."
    echo ""
    echo "Où trouver les credentials OVH :"
    echo "  1. https://www.ovh.com/manager → Public Cloud → votre projet"
    echo "  2. Stockage objet → S3 API Credentials → Créer des identifiants"
    echo "  3. Copier Access Key + Secret Key dans ce script"
    exit 1
fi

echo "=== 1/4 Installation rclone ==="
if command -v rclone &>/dev/null; then
    echo "rclone déjà installé : $(rclone version | head -1)"
else
    curl -fsSL https://rclone.org/install.sh | bash
    echo "rclone installé : $(rclone version | head -1)"
fi

echo ""
echo "=== 2/4 Configuration remote OVH S3 ==="
mkdir -p ~/.config/rclone

# Supprimer section existante si elle existe
if grep -q "^\[${RCLONE_REMOTE}\]" ~/.config/rclone/rclone.conf 2>/dev/null; then
    echo "Remote '${RCLONE_REMOTE}' déjà présent — mise à jour."
    # Supprimer l'ancien bloc
    python3 - <<PYEOF
import re, pathlib
conf = pathlib.Path('/root/.config/rclone/rclone.conf')
content = conf.read_text()
# Supprimer la section [remote] jusqu'à la prochaine section [
pattern = r'\[${RCLONE_REMOTE}\][^\[]*'
content = re.sub(pattern.replace('\${RCLONE_REMOTE}', '${RCLONE_REMOTE}'), '', content)
conf.write_text(content)
PYEOF
fi

cat >> ~/.config/rclone/rclone.conf <<EOF

[${RCLONE_REMOTE}]
type = s3
provider = Other
access_key_id = ${OVH_ACCESS_KEY}
secret_access_key = ${OVH_SECRET_KEY}
endpoint = ${OVH_ENDPOINT}
region = ${OVH_REGION}
acl = private
EOF

echo "Remote '${RCLONE_REMOTE}' configuré."

echo ""
echo "=== 3/4 Création du bucket OVH ==="
rclone mkdir "${RCLONE_REMOTE}:${OVH_BUCKET}" && echo "Bucket '${OVH_BUCKET}' créé ou déjà existant."

echo ""
echo "=== 4/4 Test de connexion ==="
rclone ls "${RCLONE_REMOTE}:${OVH_BUCKET}/" && echo "Connexion OVH OK."

echo ""
echo "=== Ajout cron (02:30 quotidien) ==="
CRON_LINE="30 2 * * * /opt/bantudelice/scripts/backup_offsite.sh >> /var/log/bantudelice-backup-offsite.log 2>&1"
if crontab -l 2>/dev/null | grep -q "backup_offsite"; then
    echo "Cron déjà présent."
else
    (crontab -l 2>/dev/null; echo "$CRON_LINE") | crontab -
    echo "Cron ajouté : $CRON_LINE"
fi

echo ""
echo "=== RÉSUMÉ ==="
echo "  rclone remote : ${RCLONE_REMOTE}"
echo "  OVH bucket    : ${OVH_BUCKET}"
echo "  Endpoint      : ${OVH_ENDPOINT}"
echo "  Cron          : 02:30 quotidien → backup_offsite.sh"
echo ""
echo "Test manuel : /opt/bantudelice/scripts/backup_offsite.sh"
echo "Log         : tail -f /var/log/bantudelice-backup-offsite.log"
