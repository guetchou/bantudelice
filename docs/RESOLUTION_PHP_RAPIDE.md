# RÉSOLUTION RAPIDE - PROBLÈME PHP

## 🔴 Problème

```
Your Composer dependencies require a PHP version ">= 8.1.0". 
You are running 8.0.30.
```

## ✅ Solution Rapide

Votre serveur AlmaLinux 9 a **PHP 8.2 disponible** dans les modules DNF.

### Option 1 : Script Automatique (RECOMMANDÉ)

```bash
cd /opt/thedrop247
bash upgrade-php.sh
```

Le script va :
- ✅ Sauvegarder votre configuration actuelle
- ✅ Installer PHP 8.2
- ✅ Installer toutes les extensions nécessaires
- ✅ Mettre à jour Composer
- ✅ Réinstaller les dépendances

### Option 2 : Commandes Manuelles

```bash
# 1. Mettre à jour Composer
composer self-update --stable

# 2. Réinitialiser le module PHP
dnf module reset php -y

# 3. Activer PHP 8.2
dnf module enable php:8.2 -y

# 4. Installer PHP et extensions
dnf install -y php php-cli php-fpm php-common php-mysqlnd php-pdo \
    php-zip php-devel php-gd php-mbstring php-curl php-xml \
    php-pear php-bcmath php-json php-opcache php-tokenizer php-openssl

# 5. Vérifier la version
php -v

# 6. Redémarrer PHP-FPM (si utilisé)
systemctl restart php-fpm

# 7. Dans le projet, réinstaller les dépendances
cd /opt/thedrop247
composer clear-cache
composer install --no-interaction --prefer-dist --optimize-autoloader
```

## 🎯 Vérifications

```bash
# Version PHP (doit être 8.2.x)
php -v

# Extensions requises
php -m | grep -E "(pdo|tokenizer|openssl|xml|json|mbstring|bcmath|zip)"

# Composer
composer --version
composer diagnose

# Laravel
cd /opt/thedrop247
php artisan --version
```

## ⚠️ Après la Migration

1. **Tester l'application** : Vérifier que tout fonctionne
2. **Vérifier les logs** : `tail -f storage/logs/laravel.log`
3. **Redémarrer les services** si nécessaire

## 📄 Documents Complets

- **PROBLEME_VERSION_PHP.md** : Guide détaillé complet
- **upgrade-php.sh** : Script automatisé de migration

---

**Note :** Le fichier `.env` existe déjà (créé le 21 octobre 2023). 
Vérifiez qu'il contient toutes les variables nécessaires.

