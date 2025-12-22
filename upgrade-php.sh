#!/bin/bash
#
# Script de migration PHP 8.0 -> PHP 8.2
# Pour TheDrop247 sur AlmaLinux 9
#
# Usage: bash upgrade-php.sh
#

set -e

echo "========================================="
echo "Migration PHP 8.0 -> PHP 8.2"
echo "Projet: TheDrop247"
echo "========================================="
echo ""

# Couleurs pour les messages
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Fonction de log
log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Vérifier que nous sommes root
if [ "$EUID" -ne 0 ]; then 
    log_error "Ce script doit être exécuté en tant que root"
    exit 1
fi

# Vérifier la version PHP actuelle
PHP_CURRENT=$(php -v | head -n 1 | cut -d ' ' -f 2 | cut -d '.' -f 1,2)
log_info "Version PHP actuelle: $PHP_CURRENT"

if [[ "$PHP_CURRENT" == "8.2" ]] || [[ "$PHP_CURRENT" == "8.3" ]]; then
    log_info "PHP 8.2 ou supérieur est déjà installé. Aucune action nécessaire."
    exit 0
fi

# Confirmer avant de continuer
read -p "Continuer la migration vers PHP 8.2? (y/N) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    log_warn "Migration annulée par l'utilisateur"
    exit 0
fi

# Créer un point de restauration
BACKUP_DIR="/root/php_backup_$(date +%Y%m%d_%H%M%S)"
log_info "Création d'un point de restauration dans: $BACKUP_DIR"
mkdir -p "$BACKUP_DIR"

# Sauvegarder la configuration PHP actuelle
if [ -d "/etc/php.d" ]; then
    cp -r /etc/php* "$BACKUP_DIR/" 2>/dev/null || true
    log_info "Configuration PHP sauvegardée"
fi

# Mettre à jour Composer d'abord
log_info "Mise à jour de Composer..."
composer self-update --stable || log_warn "Échec de la mise à jour de Composer"

# Vérifier les modules PHP disponibles
log_info "Vérification des modules PHP disponibles..."
dnf module list php

# Réinitialiser le module PHP
log_info "Réinitialisation du module PHP..."
dnf module reset php -y

# Activer PHP 8.2
log_info "Activation du module PHP 8.2..."
dnf module enable php:8.2 -y

# Installer PHP et les extensions essentielles
log_info "Installation de PHP 8.2 et des extensions..."

dnf install -y \
    php \
    php-cli \
    php-fpm \
    php-common \
    php-mysqlnd \
    php-pdo \
    php-zip \
    php-devel \
    php-gd \
    php-mbstring \
    php-curl \
    php-xml \
    php-pear \
    php-bcmath \
    php-json \
    php-opcache \
    php-tokenizer \
    php-openssl \
    php-fileinfo

log_info "Extensions PHP installées"

# Vérifier la nouvelle version
PHP_NEW=$(php -v | head -n 1)
log_info "Nouvelle version PHP: $PHP_NEW"

# Vérifier les extensions installées
log_info "Extensions PHP installées:"
php -m | grep -E "(pdo|tokenizer|openssl|xml|json|mbstring|bcmath|zip)" || log_warn "Certaines extensions peuvent manquer"

# Vérifier PHP-FPM
if systemctl is-active --quiet php-fpm; then
    log_info "Redémarrage de PHP-FPM..."
    systemctl restart php-fpm
    systemctl status php-fpm --no-pager -l
else
    log_warn "PHP-FPM n'est pas actif ou n'est pas installé"
fi

# Redémarrer les services web si nécessaire
if systemctl is-active --quiet httpd; then
    log_info "Redémarrage d'Apache..."
    systemctl restart httpd
fi

if systemctl is-active --quiet nginx; then
    log_info "Redémarrage de Nginx..."
    systemctl restart nginx
fi

# Aller dans le répertoire du projet
cd /opt/thedrop247 || {
    log_error "Impossible d'accéder à /opt/thedrop247"
    exit 1
}

# Nettoyer le cache Composer
log_info "Nettoyage du cache Composer..."
composer clear-cache

# Réinstaller les dépendances
log_info "Réinstallation des dépendances Composer..."
composer install --no-interaction --prefer-dist --optimize-autoloader || {
    log_error "Échec de l'installation des dépendances Composer"
    log_warn "Vérifiez les erreurs ci-dessus"
}

# Vérifier Laravel
log_info "Vérification de Laravel..."
if [ -f "artisan" ]; then
    php artisan --version || log_warn "Impossible de vérifier la version Laravel"
else
    log_warn "Fichier artisan non trouvé"
fi

# Résumé
echo ""
echo "========================================="
echo "Migration terminée!"
echo "========================================="
echo ""
log_info "Version PHP: $(php -v | head -n 1)"
log_info "Version Composer: $(composer --version)"
echo ""
log_info "Point de restauration créé dans: $BACKUP_DIR"
echo ""
log_warn "Vérifications recommandées:"
echo "  1. php -v"
echo "  2. php -m"
echo "  3. composer diagnose"
echo "  4. php artisan --version"
echo ""
log_info "Si tout fonctionne correctement, vous pouvez supprimer le backup:"
echo "  rm -rf $BACKUP_DIR"
echo ""

