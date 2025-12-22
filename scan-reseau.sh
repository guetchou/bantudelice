#!/bin/bash
#
# Script de Scan Réseau pour BantuDelice/TheDrop247
# Analyse la configuration réseau du site
#
# Usage: ./scan-reseau.sh
#

set -e

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m'

echo "========================================="
echo "  SCAN RÉSEAU - BantuDelice/TheDrop247"
echo "========================================="
echo ""

# 1. Ports ouverts
echo -e "${BLUE}[1] Analyse des ports ouverts...${NC}"
echo "----------------------------------------"
if command -v ss &> /dev/null; then
    echo "Ports TCP en écoute:"
    ss -tuln | grep LISTEN | awk '{print $5}' | cut -d: -f2 | sort -n | uniq
elif command -v netstat &> /dev/null; then
    echo "Ports TCP en écoute:"
    netstat -tuln | grep LISTEN | awk '{print $4}' | cut -d: -f2 | sort -n | uniq
else
    echo -e "${RED}❌ netstat et ss non disponibles${NC}"
fi
echo ""

# 2. Services sur ports principaux
echo -e "${BLUE}[2] Services sur ports principaux...${NC}"
echo "----------------------------------------"
PORTS=(80 443 22 3306 9000)
for port in "${PORTS[@]}"; do
    if command -v lsof &> /dev/null; then
        service=$(sudo lsof -i :$port 2>/dev/null | tail -n +2 | awk '{print $1}' | head -1)
        if [ ! -z "$service" ]; then
            echo -e "${GREEN}✅ Port $port: $service${NC}"
        else
            echo -e "${YELLOW}⚠️  Port $port: Service non identifié${NC}"
        fi
    else
        echo -e "${YELLOW}⚠️  lsof non disponible pour port $port${NC}"
    fi
done
echo ""

# 3. Configuration Nginx
echo -e "${BLUE}[3] Vérification Nginx...${NC}"
echo "----------------------------------------"
NGINX_CONF="bantudelice-thedrop247.conf"
if [ -f "$NGINX_CONF" ]; then
    echo -e "${GREEN}✅ Configuration Nginx trouvée: $NGINX_CONF${NC}"
    
    # Extraire domaines
    domains=$(grep -i "server_name" "$NGINX_CONF" | head -1 | sed 's/.*server_name //' | sed 's/;.*//')
    echo "   Domaines configurés: $domains"
    
    # Extraire ports
    ports=$(grep -i "listen" "$NGINX_CONF" | grep -v "#" | awk '{print $2}' | sed 's/;//')
    echo "   Ports configurés: $ports"
    
    # Vérifier SSL
    if grep -q "ssl_certificate" "$NGINX_CONF"; then
        echo -e "${GREEN}   ✅ SSL/TLS configuré${NC}"
    else
        echo -e "${YELLOW}   ⚠️  SSL/TLS non configuré${NC}"
    fi
else
    echo -e "${RED}❌ Configuration Nginx non trouvée${NC}"
fi
echo ""

# 4. PHP-FPM
echo -e "${BLUE}[4] Vérification PHP-FPM...${NC}"
echo "----------------------------------------"
if systemctl is-active --quiet php-fpm 2>/dev/null; then
    echo -e "${GREEN}✅ PHP-FPM est actif${NC}"
    
    # Vérifier port 9000
    if ss -tuln | grep -q ":9000" || netstat -tuln 2>/dev/null | grep -q ":9000"; then
        echo -e "${GREEN}   ✅ Port 9000 en écoute (TCP)${NC}"
    else
        echo -e "${YELLOW}   ⚠️  Port 9000 non détecté (peut-être socket Unix)${NC}"
    fi
    
    # Vérifier socket Unix
    if [ -S "/run/php-fpm/www.sock" ]; then
        echo -e "${GREEN}   ✅ Socket Unix trouvé: /run/php-fpm/www.sock${NC}"
    else
        echo -e "${YELLOW}   ⚠️  Socket Unix non trouvé${NC}"
    fi
else
    echo -e "${RED}❌ PHP-FPM n'est pas actif${NC}"
fi
echo ""

# 5. Test connectivité
echo -e "${BLUE}[5] Test de connectivité...${NC}"
echo "----------------------------------------"
DOMAINS=("bantudelice.cg" "www.bantudelice.cg" "dev.bantudelice.cg")
for domain in "${DOMAINS[@]}"; do
    if command -v curl &> /dev/null; then
        # Test HTTP
        http_code=$(curl -s -o /dev/null -w "%{http_code}" --max-time 5 "http://$domain" 2>/dev/null || echo "000")
        if [ "$http_code" = "301" ] || [ "$http_code" = "200" ]; then
            echo -e "${GREEN}✅ HTTP $domain: $http_code${NC}"
        else
            echo -e "${YELLOW}⚠️  HTTP $domain: $http_code${NC}"
        fi
        
        # Test HTTPS
        https_code=$(curl -s -o /dev/null -w "%{http_code}" --max-time 5 "https://$domain" 2>/dev/null || echo "000")
        if [ "$https_code" = "200" ]; then
            echo -e "${GREEN}✅ HTTPS $domain: $https_code${NC}"
        else
            echo -e "${YELLOW}⚠️  HTTPS $domain: $https_code${NC}"
        fi
    else
        echo -e "${YELLOW}⚠️  curl non disponible pour tester $domain${NC}"
    fi
done
echo ""

# 6. Test API
echo -e "${BLUE}[6] Test endpoints API...${NC}"
echo "----------------------------------------"
if command -v curl &> /dev/null; then
    API_ENDPOINTS=(
        "/api/restaurants"
        "/api/home_data"
    )
    
    BASE_URL="https://dev.bantudelice.cg"
    for endpoint in "${API_ENDPOINTS[@]}"; do
        http_code=$(curl -s -o /dev/null -w "%{http_code}" --max-time 5 "$BASE_URL$endpoint" 2>/dev/null || echo "000")
        if [ "$http_code" = "200" ] || [ "$http_code" = "401" ] || [ "$http_code" = "422" ]; then
            echo -e "${GREEN}✅ $endpoint: $http_code${NC}"
        else
            echo -e "${YELLOW}⚠️  $endpoint: $http_code${NC}"
        fi
    done
else
    echo -e "${YELLOW}⚠️  curl non disponible${NC}"
fi
echo ""

# 7. Docker Nginx
echo -e "${BLUE}[7] Vérification Docker Nginx...${NC}"
echo "----------------------------------------"
if command -v docker &> /dev/null; then
    if docker ps | grep -q nginx-proxy; then
        echo -e "${GREEN}✅ Container nginx-proxy est actif${NC}"
        
        # Vérifier ports exposés
        nginx_ports=$(docker port nginx-proxy 2>/dev/null | awk '{print $3}' | cut -d: -f2 | sort -n | uniq)
        if [ ! -z "$nginx_ports" ]; then
            echo "   Ports exposés: $nginx_ports"
        fi
    else
        echo -e "${YELLOW}⚠️  Container nginx-proxy non trouvé${NC}"
    fi
else
    echo -e "${YELLOW}⚠️  Docker non disponible${NC}"
fi
echo ""

# 8. Résumé
echo "========================================="
echo -e "${BLUE}RÉSUMÉ${NC}"
echo "========================================="
echo ""
echo "Pour plus de détails, consultez:"
echo "  - SCAN_RESEAU_SITE.md (documentation complète)"
echo "  - bantudelice-thedrop247.conf (configuration Nginx)"
echo ""
echo "Actions recommandées:"
echo "  1. Identifier les services sur ports inconnus"
echo "  2. Vérifier la configuration PHP-FPM"
echo "  3. S'assurer que le firewall est configuré"
echo "  4. Vérifier les certificats SSL"
echo ""

