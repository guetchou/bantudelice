#!/bin/bash
#
# Script pour configurer Nginx pour TheDrop247
# Remplace la configuration de BantuDelice
#
set -e

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo "========================================="
echo "Configuration Nginx pour TheDrop247"
echo "========================================="
echo ""

# 1. Sauvegarder l'ancienne configuration
BACKUP_FILE="/etc/nginx/conf.d/dev.bantudelice.cg.conf.backup.$(date +%Y%m%d_%H%M%S)"
if [ -f "/etc/nginx/conf.d/dev.bantudelice.cg.conf" ]; then
    echo -e "${YELLOW}Sauvegarde de l'ancienne configuration...${NC}"
    cp /etc/nginx/conf.d/dev.bantudelice.cg.conf "$BACKUP_FILE"
    echo -e "${GREEN}✅ Sauvegardée dans: $BACKUP_FILE${NC}"
fi

# 2. La nouvelle configuration est déjà créée
NEW_CONFIG="/etc/nginx/conf.d/dev.bantudelice.cg-thedrop247.conf"
if [ ! -f "$NEW_CONFIG" ]; then
    echo -e "${RED}❌ Erreur: La nouvelle configuration n'existe pas: $NEW_CONFIG${NC}"
    exit 1
fi

# 3. Remplacer l'ancienne configuration
echo -e "${YELLOW}Remplacement de la configuration...${NC}"
cp "$NEW_CONFIG" /etc/nginx/conf.d/dev.bantudelice.cg.conf
echo -e "${GREEN}✅ Configuration remplacée${NC}"

# 4. Tester la configuration Nginx
echo -e "${YELLOW}Test de la configuration Nginx...${NC}"
if nginx -t; then
    echo -e "${GREEN}✅ Configuration Nginx valide${NC}"
else
    echo -e "${RED}❌ Erreur dans la configuration Nginx${NC}"
    exit 1
fi

# 5. Redémarrer Nginx
echo -e "${YELLOW}Redémarrage de Nginx...${NC}"
systemctl reload nginx
echo -e "${GREEN}✅ Nginx redémarré${NC}"

# 6. Vérifier PHP-FPM
echo -e "${YELLOW}Vérification de PHP-FPM...${NC}"
if systemctl is-active --quiet php-fpm; then
    echo -e "${GREEN}✅ PHP-FPM est actif${NC}"
else
    echo -e "${YELLOW}⚠️  Démarrage de PHP-FPM...${NC}"
    systemctl enable php-fpm
    systemctl start php-fpm
    echo -e "${GREEN}✅ PHP-FPM démarré${NC}"
fi

echo ""
echo "========================================="
echo -e "${GREEN}Configuration terminée!${NC}"
echo "========================================="
echo ""
echo "Le site dev.bantudelice.cg pointe maintenant vers TheDrop247"
echo ""
echo "Prochaines étapes:"
echo "  1. Compiler les assets: npm run production"
echo "  2. Créer un utilisateur admin"
echo "  3. Tester l'application: https://dev.bantudelice.cg"
echo ""



