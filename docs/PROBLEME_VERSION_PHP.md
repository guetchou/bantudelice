# RÉSOLUTION DU PROBLÈME DE VERSION PHP

## 🔴 Problème Identifié

**Erreur Composer :**
```
Your Composer dependencies require a PHP version ">= 8.1.0". 
You are running 8.0.30.
```

**Constat :**
- ✅ PHP 8.0.30 installé actuellement
- ❌ Laravel 10 nécessite PHP >= 8.1.0
- ⚠️ Incompatibilité détectée

---

## 📋 Informations Système

- **OS :** AlmaLinux 9.7 (Moss Jungle Cat)
- **PHP actuel :** 8.0.30
- **PHP requis :** >= 8.1.0
- **Laravel :** 10.10
- **Composer :** Version snapshot (à mettre à jour)

---

## 🛠️ SOLUTIONS

### Option 1 : Mettre à Jour PHP vers 8.1+ (RECOMMANDÉ)

#### Pour AlmaLinux 9 / RHEL 9

AlmaLinux 9 inclut généralement PHP 8.1 ou 8.2 dans ses dépôts par défaut.

#### Étape 1 : Vérifier les modules PHP disponibles

```bash
dnf module list php
```

#### Étape 2 : Installer PHP 8.1, 8.2 ou 8.3

**✅ Versions disponibles sur votre serveur :**
- PHP 8.1 (stream)
- PHP 8.2 (stream) - **RECOMMANDÉ**
- PHP 8.3 (stream)

**Option A : Via les modules (recommandé)**

```bash
# Voir les versions disponibles
dnf module list php

# Réinitialiser le module PHP (si déjà activé)
dnf module reset php -y

# Installer PHP 8.2 (recommandé pour stabilité)
dnf module enable php:8.2 -y
dnf install php php-cli php-fpm php-common php-mysqlnd php-zip php-devel php-gd php-mbstring php-curl php-xml php-pear php-bcmath php-json php-opcache -y
```

**Note :** `php-mcrypt` n'est plus disponible dans PHP 8.x, utilisez `libsodium` à la place si nécessaire.

**Option B : Via Remi Repository (alternative)**

```bash
# Installer le dépôt Remi (si pas déjà fait)
dnf install https://rpms.remirepo.net/enterprise/remi-release-9.rpm -y

# Installer PHP 8.2 depuis Remi
dnf module enable php:remi-8.2 -y
dnf install php php-cli php-fpm php-common php-mysqlnd php-zip php-devel php-gd php-mcrypt php-mbstring php-curl php-xml php-pear php-bcmath php-json php-opcache -y
```

#### Étape 3 : Vérifier la version installée

```bash
php -v
```

Vous devriez voir PHP 8.1.x ou 8.2.x

#### Étape 4 : Installer les extensions requises pour Laravel

```bash
dnf install php-pdo php-tokenizer php-openssl php-xml php-json php-mbstring php-bcmath php-zip -y
```

#### Étape 5 : Redémarrer les services

```bash
# Si vous utilisez PHP-FPM
systemctl restart php-fpm

# Si vous utilisez Apache
systemctl restart httpd

# Si vous utilisez Nginx (et PHP-FPM)
systemctl restart php-fpm nginx
```

---

### Option 2 : Mettre à Jour Composer (Temporaire)

Composer signale aussi un problème avec la version snapshot :

```bash
# Mettre à jour Composer vers la version stable
composer self-update --stable
```

**Note :** Cela ne résoudra pas le problème de version PHP, mais corrigera l'avertissement Composer.

---

### Option 3 : Modifier composer.json (NON RECOMMANDÉ)

⚠️ **ATTENTION :** Cette option n'est PAS recommandée car Laravel 10 nécessite réellement PHP 8.1+.

Si vous modifiez `composer.json` pour accepter PHP 8.0, vous risquez de rencontrer des erreurs d'exécution.

**Seulement si absolument nécessaire :**

```bash
# Éditer composer.json
nano composer.json

# Changer la ligne :
"php": "^8.1",
# en :
"php": "^8.0",

# Mais ATTENTION : Laravel 10 ne fonctionnera pas correctement avec PHP 8.0 !
```

---

## 🔧 ACTIONS IMMÉDIATES RECOMMANDÉES

### 1. Mettre à Jour Composer

```bash
cd /opt/thedrop247
composer self-update --stable
```

### 2. Vérifier les Versions PHP Disponibles

```bash
dnf module list php
```

### 3. Installer PHP 8.2 (Recommandé)

```bash
# Sauvegarder la configuration actuelle
cp -r /etc/php* /root/php_backup_$(date +%Y%m%d)/

# Réinitialiser le module PHP
dnf module reset php -y

# Activer PHP 8.2
dnf module enable php:8.2 -y

# Installer PHP et extensions
dnf install php php-cli php-fpm php-common php-mysqlnd php-zip \
    php-devel php-gd php-mcrypt php-mbstring php-curl php-xml \
    php-pear php-bcmath php-json php-opcache php-pdo php-tokenizer \
    php-openssl -y

# Vérifier
php -v
```

### 4. Vérifier les Extensions Requises

```bash
php -m | grep -E "(pdo|tokenizer|openssl|xml|json|mbstring|bcmath|zip)"
```

### 5. Reinstaller les Dépendances Composer

```bash
cd /opt/thedrop247

# Nettoyer le cache Composer
composer clear-cache

# Réinstaller les dépendances
composer install --no-interaction --prefer-dist --optimize-autoloader
```

### 6. Vérifier que Tout Fonctionne

```bash
# Vérifier la version PHP
php -v

# Vérifier Composer
composer --version

# Vérifier Laravel
php artisan --version
```

---

## 📝 EXTENSIONS PHP REQUISES PAR LARAVEL 10

Assurez-vous que ces extensions sont installées :

- ✅ **php-pdo** - PDO Database Abstraction Layer
- ✅ **php-tokenizer** - Tokenizer Extension
- ✅ **php-openssl** - OpenSSL Extension
- ✅ **php-xml** - XML Extension
- ✅ **php-json** - JSON Extension
- ✅ **php-mbstring** - Multibyte String Extension
- ✅ **php-bcmath** - BCMath Arbitrary Precision Mathematics
- ✅ **php-zip** - ZIP Extension
- ✅ **php-curl** - cURL Extension
- ✅ **php-gd** - GD Library (pour images)
- ✅ **php-mysqlnd** - MySQL Native Driver

Vérification :

```bash
php -m
```

---

## 🔍 VÉRIFICATIONS POST-INSTALLATION

### 1. Version PHP

```bash
php -v
# Doit afficher PHP 8.1.x ou 8.2.x
```

### 2. Extensions

```bash
php -m | grep -iE "(pdo|tokenizer|openssl|xml|json|mbstring|bcmath|zip)"
```

### 3. Composer

```bash
composer --version
composer diagnose
```

### 4. Laravel

```bash
cd /opt/thedrop247
php artisan --version
php artisan about
```

---

## ⚠️ NOTES IMPORTANTES

1. **Sauvegarde** : Toujours sauvegarder avant de mettre à jour PHP
2. **Services Web** : Redémarrer Apache/Nginx et PHP-FPM après installation
3. **Configuration** : Vérifier `/etc/php.ini` et les fichiers dans `/etc/php.d/`
4. **Permissions** : Vérifier que les permissions sur `storage/` et `bootstrap/cache/` sont correctes

---

## 🐛 DÉPANNAGE

### Problème : Plusieurs versions PHP installées

```bash
# Voir toutes les versions PHP
which -a php
update-alternatives --list php

# Si nécessaire, configurer l'alternative
update-alternatives --config php
```

### Problème : Extensions manquantes après mise à jour

```bash
# Réinstaller toutes les extensions
dnf reinstall php-* -y
```

### Problème : Services ne démarrent pas

```bash
# Vérifier les erreurs
systemctl status php-fpm
journalctl -xe

# Vérifier la configuration PHP
php --ini
php-fpm -t
```

---

## 📚 RESSOURCES

- [Laravel 10 Requirements](https://laravel.com/docs/10.x#server-requirements)
- [AlmaLinux PHP Installation](https://wiki.almalinux.org/)
- [PHP Installation Guide](https://www.php.net/manual/fr/install.php)

---

## ✅ CHECKLIST DE RÉSOLUTION

- [ ] Composer mis à jour vers version stable
- [ ] Version PHP >= 8.1 installée et vérifiée
- [ ] Toutes les extensions PHP requises installées
- [ ] Services web redémarrés
- [ ] Dépendances Composer réinstallées
- [ ] Laravel fonctionne correctement (`php artisan --version`)
- [ ] Aucune erreur de plateforme Composer

---

**Date de création :** $(date)  
**Serveur :** AlmaLinux 9.7  
**Problème :** PHP 8.0.30 → PHP 8.1+ requis

