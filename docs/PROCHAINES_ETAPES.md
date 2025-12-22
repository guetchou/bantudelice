# 🎯 PROCHAINES ÉTAPES - PLAN D'ACTION

**Date :** $(date)  
**Situation actuelle :** Fichier .env configuré, migration PHP requise

---

## 📊 ÉTAT ACTUEL

✅ **Fichier .env existe et contient :**
- APP_KEY définie
- Configuration base de données présente
- APP_ENV=local (à changer en production)
- APP_DEBUG=true (à changer en production)

❌ **Problème critique :**
- PHP 8.0.30 installé
- Laravel 10 nécessite PHP >= 8.1.0
- Impossible d'exécuter composer install/migrate

---

## 🚀 PROCHAINES ÉTAPES (PAR ORDRE DE PRIORITÉ)

### 🔴 ÉTAPE 1 : MIGRATION PHP (URGENT - 5-10 min)

**Objectif :** Mettre à jour PHP 8.0.30 → PHP 8.2

**Option A : Script Automatique (RECOMMANDÉ)**

```bash
cd /opt/thedrop247
bash upgrade-php.sh
```

**Option B : Commandes Manuelles**

```bash
# 1. Mettre à jour Composer
composer self-update --stable

# 2. Réinitialiser et activer PHP 8.2
dnf module reset php -y
dnf module enable php:8.2 -y

# 3. Installer PHP 8.2 et extensions
dnf install -y php php-cli php-fpm php-common php-mysqlnd php-pdo \
    php-zip php-devel php-gd php-mbstring php-curl php-xml \
    php-pear php-bcmath php-json php-opcache php-tokenizer php-openssl

# 4. Vérifier
php -v

# 5. Redémarrer PHP-FPM
systemctl restart php-fpm

# 6. Réinstaller dépendances
cd /opt/thedrop247
composer clear-cache
composer install --no-interaction --prefer-dist --optimize-autoloader
```

**Vérification :**
```bash
php -v  # Doit afficher PHP 8.2.x
```

---

### 🟡 ÉTAPE 2 : VÉRIFIER LA BASE DE DONNÉES (5 min)

**Objectif :** S'assurer que la base de données existe et est accessible

```bash
# Vérifier si la base existe
mysql -u root -e "SHOW DATABASES LIKE 'thedrop247';"

# Si elle n'existe pas, la créer
mysql -u root -e "CREATE DATABASE IF NOT EXISTS thedrop247 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Vérifier les permissions
mysql -u root -e "SHOW GRANTS FOR 'root'@'localhost';"
```

**Si mot de passe MySQL requis :**
- Vérifier le mot de passe dans `.env` (DB_PASSWORD)
- Ou configurer un utilisateur MySQL dédié

---

### 🟡 ÉTAPE 3 : METTRE À JOUR LE FICHIER .ENV (5 min)

**Objectif :** Configurer pour la production

**Changements recommandés :**

```bash
# Éditer le fichier .env
nano /opt/thedrop247/.env
```

**Variables à modifier :**

1. **APP_NAME** : Changer de "Laravel" à "TheDrop247"
   ```env
   APP_NAME=TheDrop247
   ```

2. **APP_ENV** : Changer de "local" à "production"
   ```env
   APP_ENV=production
   ```

3. **APP_DEBUG** : Changer de "true" à "false"
   ```env
   APP_DEBUG=false
   ```

4. **APP_URL** : Mettre l'URL réelle du site
   ```env
   APP_URL=https://votre-domaine.com
   ```

5. **DB_PASSWORD** : Vérifier/ajouter le mot de passe MySQL si nécessaire
   ```env
   DB_PASSWORD=votre_mot_de_passe
   ```

**Après modifications, vider les caches :**
```bash
php artisan config:clear
php artisan cache:clear
```

---

### 🟢 ÉTAPE 4 : INSTALLER LES DÉPENDANCES (10-15 min)

**Objectif :** Installer toutes les dépendances PHP et JavaScript

```bash
cd /opt/thedrop247

# Installer dépendances PHP (après migration PHP)
composer install --no-interaction --prefer-dist --optimize-autoloader

# Installer dépendances JavaScript
npm install

# Compiler les assets
npm run production
```

**Vérification :**
```bash
composer show | head -5
npm list --depth=0
```

---

### 🟢 ÉTAPE 5 : EXÉCUTER LES MIGRATIONS (5 min)

**Objectif :** Créer les tables de la base de données

```bash
cd /opt/thedrop247

# Vérifier le statut des migrations
php artisan migrate:status

# Exécuter les migrations
php artisan migrate

# Si erreurs, forcer (ATTENTION : peut écraser des données)
# php artisan migrate:fresh
```

**Vérification :**
```bash
mysql -u root thedrop247 -e "SHOW TABLES;" | head -10
```

---

### 🟢 ÉTAPE 6 : INSTALLER PASSPORT (5 min)

**Objectif :** Configurer l'authentification API OAuth2

```bash
cd /opt/thedrop247

# Installer Passport
php artisan passport:install

# Cela va créer les clés OAuth2 nécessaires pour l'API
```

**Note :** Cela crée les tables `oauth_clients` et `oauth_personal_access_clients` et génère les clés.

---

### 🟢 ÉTAPE 7 : CONFIGURER LES PERMISSIONS (2 min)

**Objectif :** S'assurer que Laravel peut écrire dans storage et cache

```bash
cd /opt/thedrop247

# Donner les permissions d'écriture
chmod -R 775 storage bootstrap/cache

# Si vous utilisez un utilisateur web spécifique (ex: www-data)
chown -R www-data:www-data storage bootstrap/cache

# Ou si vous êtes root
chown -R root:root storage bootstrap/cache
```

**Vérification :**
```bash
ls -la storage/
ls -la bootstrap/cache/
```

---

### 🟢 ÉTAPE 8 : TESTER L'APPLICATION (10 min)

**Objectif :** Vérifier que tout fonctionne

```bash
cd /opt/thedrop247

# Vérifier la version Laravel
php artisan --version

# Afficher les informations système
php artisan about

# Vérifier les routes
php artisan route:list | head -10

# Vérifier la configuration
php artisan config:cache
php artisan route:cache
```

**Tester via navigateur :**
- Accéder à `http://votre-domaine.com` ou `http://localhost`
- Vérifier qu'il n'y a pas d'erreurs

---

## 📋 CHECKLIST COMPLÈTE

### Avant de commencer
- [ ] Sauvegarder la configuration actuelle
- [ ] Noter les credentials MySQL actuels
- [ ] Vérifier l'espace disque disponible

### Migration PHP
- [ ] Exécuter `bash upgrade-php.sh` ou commandes manuelles
- [ ] Vérifier `php -v` affiche PHP 8.2.x
- [ ] Vérifier extensions : `php -m`
- [ ] Redémarrer PHP-FPM

### Configuration
- [ ] Mettre à jour `.env` (APP_NAME, APP_ENV, APP_DEBUG, APP_URL)
- [ ] Vérifier credentials base de données
- [ ] Vérifier APP_KEY présente

### Installation
- [ ] `composer install` réussi
- [ ] `npm install` réussi
- [ ] `npm run production` réussi

### Base de données
- [ ] Base de données créée/existe
- [ ] Migrations exécutées : `php artisan migrate`
- [ ] Tables créées : `SHOW TABLES`

### Authentification
- [ ] Passport installé : `php artisan passport:install`
- [ ] Clés OAuth2 générées

### Permissions
- [ ] Permissions storage/cache correctes
- [ ] Propriétaire correct

### Tests
- [ ] `php artisan --version` fonctionne
- [ ] Site accessible via navigateur
- [ ] Pas d'erreurs dans les logs

---

## 🎯 ORDRE RECOMMANDÉ D'EXÉCUTION

1. ✅ **MIGRATION PHP** (Étape 1) - CRITIQUE
2. ✅ **VÉRIFIER BASE DE DONNÉES** (Étape 2)
3. ✅ **METTRE À JOUR .ENV** (Étape 3)
4. ✅ **INSTALLER DÉPENDANCES** (Étape 4)
5. ✅ **EXÉCUTER MIGRATIONS** (Étape 5)
6. ✅ **INSTALLER PASSPORT** (Étape 6)
7. ✅ **CONFIGURER PERMISSIONS** (Étape 7)
8. ✅ **TESTER** (Étape 8)

---

## ⏱️ TEMPS ESTIMÉ TOTAL

- Migration PHP : 5-10 minutes
- Vérification BD : 5 minutes
- Mise à jour .env : 5 minutes
- Installation dépendances : 10-15 minutes
- Migrations : 5 minutes
- Passport : 5 minutes
- Permissions : 2 minutes
- Tests : 10 minutes

**Total : ~50-60 minutes**

---

## 🆘 EN CAS DE PROBLÈME

### Si la migration PHP échoue
- Consulter `PROBLEME_VERSION_PHP.md`
- Vérifier les logs : `journalctl -xe`
- Vérifier les modules : `dnf module list php`

### Si les migrations échouent
- Vérifier la connexion BD : `mysql -u root`
- Vérifier les credentials dans `.env`
- Vérifier les logs : `tail -f storage/logs/laravel.log`

### Si Composer échoue
- Nettoyer : `rm -rf vendor composer.lock`
- Réinstaller : `composer install --no-interaction`

---

## 📚 DOCUMENTS DE RÉFÉRENCE

- **RESOLUTION_PHP_RAPIDE.md** - Guide rapide migration PHP
- **PROBLEME_VERSION_PHP.md** - Guide détaillé migration PHP
- **ACTIONS_IMMEDIATES.md** - Actions prioritaires
- **VARIABLES_ENVIRONNEMENT.md** - Liste complète variables .env
- **RESUME_AUDIT.md** - Résumé complet de l'audit

---

**🎯 Commencez par l'ÉTAPE 1 : Migration PHP**

C'est le blocage principal actuel. Une fois PHP 8.2 installé, vous pourrez continuer avec les autres étapes.

