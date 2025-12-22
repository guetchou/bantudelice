# 🚨 ACTIONS IMMÉDIATES REQUISES

**Date :** $(date)  
**Priorité :** 🔴 URGENTE

---

## ⚠️ PROBLÈME CRITIQUE DÉTECTÉ

**Erreur Composer :**
```
Your Composer dependencies require a PHP version ">= 8.1.0". 
You are running 8.0.30.
```

**Impact :**
- ❌ Impossible d'installer/mettre à jour les dépendances Composer
- ❌ Laravel 10 ne peut pas fonctionner avec PHP 8.0.30
- ❌ L'application ne peut pas démarrer correctement

---

## ✅ SOLUTION RAPIDE (5 minutes)

### Étape 1 : Mettre à jour Composer

```bash
composer self-update --stable
```

### Étape 2 : Migrer vers PHP 8.2

**Option A : Script Automatique (RECOMMANDÉ)**

```bash
cd /opt/thedrop247
bash upgrade-php.sh
```

**Option B : Commandes Manuelles**

```bash
# Réinitialiser le module PHP
dnf module reset php -y

# Activer PHP 8.2
dnf module enable php:8.2 -y

# Installer PHP et extensions
dnf install -y php php-cli php-fpm php-common php-mysqlnd php-pdo \
    php-zip php-devel php-gd php-mbstring php-curl php-xml \
    php-pear php-bcmath php-json php-opcache php-tokenizer php-openssl

# Vérifier
php -v

# Redémarrer PHP-FPM
systemctl restart php-fpm

# Dans le projet
cd /opt/thedrop247
composer clear-cache
composer install --no-interaction --prefer-dist --optimize-autoloader
```

### Étape 3 : Vérifier

```bash
# Doit afficher PHP 8.2.x
php -v

# Doit fonctionner sans erreur
cd /opt/thedrop247
php artisan --version
```

---

## 📋 CHECKLIST COMPLÈTE

### 🔴 URGENT (À faire MAINTENANT)
- [ ] Mettre à jour Composer : `composer self-update --stable`
- [ ] Exécuter la migration PHP : `bash upgrade-php.sh`
- [ ] Vérifier version PHP : `php -v` (doit être 8.2.x)
- [ ] Réinstaller dépendances : `composer install`
- [ ] Vérifier Laravel : `php artisan --version`

### 🟡 IMPORTANT (Après la migration PHP)
- [ ] Vérifier le fichier `.env` (existe déjà)
- [ ] Vérifier que `APP_KEY` est définie
- [ ] Générer `APP_KEY` si manquante : `php artisan key:generate`
- [ ] Vérifier configuration base de données dans `.env`
- [ ] Exécuter migrations : `php artisan migrate`

### 🟢 RECOMMANDÉ (Une fois que tout fonctionne)
- [ ] Installer Passport : `php artisan passport:install`
- [ ] Configurer permissions : `chmod -R 775 storage bootstrap/cache`
- [ ] Vérifier les services web (Apache/Nginx)
- [ ] Tester l'application

---

## 📄 DOCUMENTS DE RÉFÉRENCE

1. **RESOLUTION_PHP_RAPIDE.md** - Solution rapide étape par étape
2. **PROBLEME_VERSION_PHP.md** - Guide détaillé complet
3. **upgrade-php.sh** - Script automatisé de migration
4. **RESUME_AUDIT.md** - Résumé complet de l'audit
5. **VARIABLES_ENVIRONNEMENT.md** - Liste des variables .env

---

## 🆘 DÉPANNAGE

### Si le script échoue

```bash
# Vérifier les logs
tail -f /var/log/dnf.log

# Vérifier la version PHP installée
php -v
which php

# Vérifier les modules disponibles
dnf module list php

# Vérifier les extensions
php -m
```

### Si Composer échoue après la migration

```bash
# Nettoyer complètement
rm -rf vendor composer.lock
composer clear-cache
composer install --no-interaction
```

### Si Laravel ne démarre pas

```bash
# Vérifier les permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Vérifier les logs
tail -f storage/logs/laravel.log

# Vérifier la configuration
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

---

## 📞 INFORMATIONS SYSTÈME

- **OS :** AlmaLinux 9.7
- **PHP actuel :** 8.0.30
- **PHP requis :** >= 8.1.0
- **PHP disponible :** 8.1, 8.2, 8.3 (modules DNF)
- **Recommandé :** PHP 8.2

---

## ✅ VALIDATION FINALE

Une fois toutes les étapes complétées, exécuter :

```bash
# 1. Version PHP correcte
php -v
# → Doit afficher PHP 8.2.x

# 2. Composer fonctionne
composer --version
composer diagnose
# → Aucune erreur

# 3. Laravel fonctionne
php artisan --version
php artisan about
# → Affiche la version Laravel et les informations système

# 4. Extensions PHP
php -m | grep -E "(pdo|tokenizer|openssl|xml|json|mbstring|bcmath|zip)"
# → Toutes les extensions doivent être listées
```

---

**⏱️ Temps estimé :** 5-10 minutes  
**🎯 Difficulté :** Facile (script automatisé disponible)

---

**Bon courage ! 🚀**

