#!/bin/bash
#
# Script pour créer la configuration Nginx Docker pour TheDrop247
# Remplace BantuDelice sans affecter les autres sites
#
set -e

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

NGINX_CONF_DIR="/opt/nginx-docker/config/conf.d"
BACKUP_DIR="/opt/nginx-docker/config/conf.d/backups"
THEDROP247_PUBLIC="/opt/thedrop247/public"
PHP_SOCKET="/run/php-fpm/www.sock"

echo "========================================="
echo "Configuration Nginx Docker pour TheDrop247"
echo "========================================="
echo ""

# 1. Créer le répertoire de backup
mkdir -p "$BACKUP_DIR"

# 2. Sauvegarder l'ancienne config
if [ -f "$NGINX_CONF_DIR/bantudelice.conf" ]; then
    BACKUP_FILE="$BACKUP_DIR/bantudelice.conf.backup.$(date +%Y%m%d_%H%M%S)"
    echo -e "${YELLOW}Sauvegarde de l'ancienne configuration...${NC}"
    cp "$NGINX_CONF_DIR/bantudelice.conf" "$BACKUP_FILE"
    echo -e "${GREEN}✅ Sauvegardée dans: $BACKUP_FILE${NC}"
fi

# 3. Créer la nouvelle configuration
echo -e "${YELLOW}Création de la nouvelle configuration...${NC}"

cat > "$NGINX_CONF_DIR/bantudelice.conf" << 'NGINX_CONFIG'
# Configuration Nginx pour TheDrop247 (Laravel)
# Remplace BantuDelice - Compatible avec nginx-proxy Docker
# Date: $(date +"%Y-%m-%d")

# PHP-FPM upstream - Socket Unix monté depuis l'hôte
upstream thedrop247_php {
    zone upstream_zone_thedrop247_php 64k;
    server unix:/var/run/php-fpm/www.sock;
}

server {
    listen 80;
    server_name bantudelice.cg www.bantudelice.cg dev.bantudelice.cg;

    location /.well-known/acme-challenge/ {
        root /var/www/certbot;
        allow all;
    }

    location / {
        return 301 https://$host$request_uri;
    }
}

server {
    listen 443 ssl;
    http2 on;
    server_name bantudelice.cg www.bantudelice.cg dev.bantudelice.cg;

    include /etc/nginx/conf.d/snippets/ssl-params.conf;
    ssl_certificate     /etc/nginx/certs/bantudelice.cg/fullchain.pem;
    ssl_certificate_key /etc/nginx/certs/bantudelice.cg/privkey.pem;

    # Root Laravel
    root /var/www/thedrop247;
    index index.php index.html;

    # Taille max upload
    client_max_body_size 50m;

    # Logs
    access_log /var/log/nginx/bantudelice-access.log;
    error_log /var/log/nginx/bantudelice-error.log;

    # API Laravel (routes /api/*)
    location ^~ /api/ {
        try_files $uri $uri/ /index.php?$query_string;
        
        fastcgi_pass thedrop247_php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        
        fastcgi_read_timeout 300;
        fastcgi_send_timeout 300;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
    }

    # Assets statiques Laravel
    location ~* \.(jpg|jpeg|gif|png|css|js|ico|svg|woff|woff2|ttf|eot|map)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
    }

    # Storage Laravel (uploads)
    location ^~ /storage/ {
        alias /var/www/thedrop247/storage/app/public/;
        expires 30d;
        add_header Cache-Control "public";
        try_files $uri =404;
    }

    # Laravel - Toutes les autres requêtes
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM pour Laravel
    location ~ \.php$ {
        fastcgi_pass thedrop247_php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        
        fastcgi_read_timeout 300;
        fastcgi_send_timeout 300;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
    }
    
    # Blocage des fichiers sensibles
    location ~ /\.(?!well-known).* {
        deny all;
        access_log off;
        log_not_found off;
    }

    # Blocage fichiers de config
    location ~ ^/(\.env|\.git|composer\.(json|lock)|package\.json|webpack\.mix\.js)$ {
        deny all;
        access_log off;
        log_not_found off;
    }
}
NGINX_CONFIG

echo -e "${GREEN}✅ Configuration créée${NC}"

# 4. Vérifier que le répertoire public existe
if [ ! -d "$THEDROP247_PUBLIC" ]; then
    echo -e "${RED}❌ Erreur: $THEDROP247_PUBLIC n'existe pas${NC}"
    exit 1
fi

echo ""
echo "========================================="
echo -e "${GREEN}Configuration créée avec succès!${NC}"
echo "========================================="
echo ""
echo -e "${YELLOW}⚠️  IMPORTANT - Actions manuelles requises:${NC}"
echo ""
echo "1. Ajouter le volume dans nginx-proxy Docker:"
echo "   /opt/thedrop247/public:/var/www/thedrop247:ro"
echo "   /run/php-fpm:/var/run/php-fpm:ro"
echo ""
echo "2. Recharger le container nginx-proxy:"
echo "   docker exec nginx-proxy nginx -t"
echo "   docker exec nginx-proxy nginx -s reload"
echo ""
echo "Ou redémarrer le container:"
echo "   docker restart nginx-proxy"
echo ""
echo "3. Vérifier les logs:"
echo "   docker logs nginx-proxy"
echo ""

