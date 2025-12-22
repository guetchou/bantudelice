#!/bin/bash
#
# Script pour mettre à jour le fichier .env pour la production
# Usage: bash update-env-production.sh
#

set -e

ENV_FILE="/opt/thedrop247/.env"
BACKUP_FILE="/opt/thedrop247/.env.backup.$(date +%Y%m%d_%H%M%S)"

# Couleurs
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo "========================================="
echo "Mise à jour du fichier .env pour production"
echo "========================================="
echo ""

# Vérifier que le fichier .env existe
if [ ! -f "$ENV_FILE" ]; then
    echo "Erreur: Le fichier .env n'existe pas à $ENV_FILE"
    exit 1
fi

# Créer une sauvegarde
echo -e "${YELLOW}Création d'une sauvegarde: $BACKUP_FILE${NC}"
cp "$ENV_FILE" "$BACKUP_FILE"
echo "✅ Sauvegarde créée"

# Fonction pour mettre à jour une variable dans .env
update_env_var() {
    local key=$1
    local value=$2
    local file=$3
    
    if grep -q "^${key}=" "$file"; then
        # Variable existe, la mettre à jour
        if [[ "$OSTYPE" == "darwin"* ]]; then
            sed -i '' "s|^${key}=.*|${key}=${value}|" "$file"
        else
            sed -i "s|^${key}=.*|${key}=${value}|" "$file"
        fi
        echo "✅ ${key} mis à jour"
    else
        # Variable n'existe pas, l'ajouter
        echo "${key}=${value}" >> "$file"
        echo "✅ ${key} ajouté"
    fi
}

echo ""
echo "Mise à jour des variables de production..."
echo ""

# Mettre à jour APP_NAME
update_env_var "APP_NAME" "TheDrop247" "$ENV_FILE"

# Mettre à jour APP_ENV
update_env_var "APP_ENV" "production" "$ENV_FILE"

# Mettre à jour APP_DEBUG
update_env_var "APP_DEBUG" "false" "$ENV_FILE"

# Demander l'URL de production
echo ""
read -p "URL de production (ex: https://votre-domaine.com) [laisser vide pour garder la valeur actuelle]: " PROD_URL
if [ ! -z "$PROD_URL" ]; then
    update_env_var "APP_URL" "$PROD_URL" "$ENV_FILE"
fi

# Mettre à jour SESSION_SECURE_COOKIE
update_env_var "SESSION_SECURE_COOKIE" "true" "$ENV_FILE"

echo ""
echo "========================================="
echo "Mise à jour terminée!"
echo "========================================="
echo ""
echo "Fichier de sauvegarde: $BACKUP_FILE"
echo ""
echo "Variables mises à jour:"
echo "  - APP_NAME=TheDrop247"
echo "  - APP_ENV=production"
echo "  - APP_DEBUG=false"
echo "  - SESSION_SECURE_COOKIE=true"
if [ ! -z "$PROD_URL" ]; then
    echo "  - APP_URL=$PROD_URL"
fi
echo ""
echo "⚠️  N'oubliez pas de:"
echo "  1. Vérifier toutes les variables dans .env"
echo "  2. Vider les caches Laravel: php artisan config:clear"
echo "  3. Vérifier la configuration de la base de données"
echo ""

