# RÉSOLUTION PROBLÈME dev.bantudelice.cg

**Date :** 2025-12-16  
**Problème :** dev.bantudelice.cg affichait le site de la Préfecture au lieu de BantuDelice

---

## 🔍 PROBLÈME IDENTIFIÉ

Le fichier de configuration `dev.bantudelice.cg.conf` pointait vers l'ancien chemin `/opt/thedrop247/public` au lieu de `/opt/bantudelice242/public`.

**Cause :** 
- Fichier de configuration dans `/opt/nginx-docker/conf.d/` (pas `/opt/nginx-docker/config/conf.d/`)
- Configuration obsolète avec anciens chemins

---

## ✅ SOLUTION APPLIQUÉE

### 1. Création du fichier de configuration correct
**Fichier :** `/opt/nginx-docker/conf.d/dev.bantudelice.cg.conf`

**Corrections apportées :**
- ✅ Socket PHP-FPM : `/var/run/php/php8.1-fpm.sock`
- ✅ Root Laravel : `/var/www/bantudelice242`
- ✅ FastCGI params : `/var/www/bantudelice242$fastcgi_script_name`
- ✅ Upstream PHP-FPM : `dev_bantudelice_php`

### 2. Configuration SSL
- Remplacement de `include /etc/nginx/conf.d/snippets/ssl-params.conf;` par configuration inline (fichier snippets non disponible)

### 3. Redémarrage Nginx
```bash
cd /opt/nginx-docker
docker compose restart nginx-proxy
```

---

## 📋 STATUT ACTUEL

**bantudelice.cg :** ✅ **HTTP 200** (Fonctionne)  
**dev.bantudelice.cg :** ⚠️ **HTTP 404/500** (PHP-FPM fonctionne, mais erreur Laravel)

**Progression :**
- ✅ Configuration Nginx corrigée
- ✅ PHP-FPM connecté (réponse HTML Laravel visible)
- ⚠️ Erreur Laravel à résoudre (probablement .env, permissions, ou base de données)

---

## 🔧 ACTIONS RESTANTES

### 1. Vérifier les logs Laravel
```bash
tail -f /opt/bantudelice242/storage/logs/laravel-$(date +%Y-%m-%d).log
```

### 2. Vérifier les permissions
```bash
chmod -R 775 /opt/bantudelice242/storage /opt/bantudelice242/bootstrap/cache
chown -R www-data:www-data /opt/bantudelice242/storage /opt/bantudelice242/bootstrap/cache
```

### 3. Vérifier le fichier .env
```bash
cd /opt/bantudelice242
php artisan config:clear
php artisan cache:clear
```

### 4. Vérifier la base de données
```bash
php artisan migrate:status
```

---

## 📝 FICHIERS MODIFIÉS

1. `/opt/nginx-docker/conf.d/dev.bantudelice.cg.conf` - Créé avec bonne configuration
2. `/opt/nginx-docker/config/conf.d/dev.bantudelice.cg.conf` - Créé (mais pas utilisé, volume monte `/opt/nginx-docker/conf.d/`)

---

**Note importante :** Le volume Docker monte `/opt/nginx-docker/conf.d/` (sans "config"), pas `/opt/nginx-docker/config/conf.d/`.

---

**Document généré le :** 2025-12-16

