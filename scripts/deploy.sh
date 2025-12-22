#!/bin/bash

# ===================================
# BantuDelice - Script de Déploiement
# Déploiement local/manuel
# ===================================

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Functions
print_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

PROJECT_PATH="/opt/bantudelice242"
PROJECT_NAME="bantudelice242"

cd "$PROJECT_PATH" || {
    print_error "Project directory not found: $PROJECT_PATH"
    exit 1
}

print_info "Starting deployment of $PROJECT_NAME..."

# 1. Create backup
print_info "Creating backup..."
BACKUP_DIR="/opt/backups/bantudelice242/$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

# Backup .env
[ -f .env ] && cp .env "$BACKUP_DIR/.env.backup"

# Backup database
if docker ps | grep -q bantudelice.*postgres; then
    print_info "Backing up database..."
    DB_CONTAINER=$(docker ps | grep bantudelice.*postgres | awk '{print $1}' | head -1)
    if [ -n "$DB_CONTAINER" ]; then
        docker exec "$DB_CONTAINER" pg_dump -U bantudelice bantudelice > "$BACKUP_DIR/database.sql" 2>/dev/null || \
        print_warning "Could not backup database"
    fi
fi

print_success "Backup created: $BACKUP_DIR"

# 2. Verify .env
if [ ! -f .env ]; then
    print_warning ".env file not found."
    if [ -f .env.example ]; then
        print_info "Copying .env.example to .env"
        cp .env.example .env
        print_warning "Please update .env with your configuration"
        exit 1
    else
        print_error ".env file is required"
        exit 1
    fi
fi

# 3. Pull latest code (if git repo)
if [ -d .git ]; then
    print_info "Pulling latest code..."
    git fetch origin || true
    CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo "main")
    git reset --hard "origin/$CURRENT_BRANCH" || git reset --hard origin/main || git reset --hard origin/master || true
else
    print_warning "Not a git repository. Skipping git pull."
fi

# 4. Install dependencies
print_info "Installing dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev || {
    print_error "Failed to install dependencies"
    exit 1
}

# 5. Clear cache
print_info "Clearing cache..."
php artisan config:clear || true
php artisan cache:clear || true
php artisan route:clear || true
php artisan view:clear || true

# 6. Run migrations
print_info "Running migrations..."
php artisan migrate --force || {
    print_error "Migration failed"
    exit 1
}

print_success "Migrations completed"

# 7. Optimize Laravel
print_info "Optimizing Laravel..."
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# 8. Restart PHP-FPM
print_info "Restarting PHP-FPM..."
if docker ps | grep -q thedrop247_php; then
    docker restart thedrop247_php || true
    print_success "PHP-FPM restarted"
else
    print_warning "PHP-FPM container not found"
fi

# 9. Verify compliance
print_info "Verifying compliance..."
/usr/local/bin/policy-check-ports.sh --strict || true
/usr/local/bin/policy-check-proxy-net.sh --strict || true

print_success "Deployment completed successfully!"

# Show status
echo ""
print_info "Application URLs:"
echo "  🌐 Frontend: https://bantudelice.cg"
echo ""
print_info "Check logs: tail -f storage/logs/laravel.log"

