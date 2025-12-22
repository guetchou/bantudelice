# 🚀 BantuDelice - Guide CI/CD

## 📋 Vue d'Ensemble

Ce guide explique comment configurer et utiliser le pipeline CI/CD pour BantuDelice (Laravel).

## 🔧 Configuration Initiale

### 1. Secrets GitHub

Dans les paramètres du dépôt GitHub (`Settings` > `Secrets and variables` > `Actions`), ajouter :

- **`DEPLOY_HOST`** : Adresse IP du serveur (ex: `5.196.22.149`)
- **`DEPLOY_USER`** : Utilisateur SSH (ex: `root`)
- **`DEPLOY_SSH_KEY`** : Clé privée SSH pour l'authentification (même clé que pour LVAClean)

### 2. Clé SSH

La même clé SSH que pour LVAClean peut être utilisée :
- Clé : `~/.ssh/github_actions_lvaclean` (déjà configurée)

## 🔄 Utilisation

### Déploiement Automatique

Le déploiement se déclenche automatiquement lors d'un push sur `main` ou `master`.

### Déploiement Manuel

1. Aller dans l'onglet **Actions** du dépôt GitHub
2. Sélectionner le workflow **🚀 BantuDelice - Deploy to Production**
3. Cliquer sur **Run workflow**
4. Optionnellement :
   - Cocher **Skip tests** pour ignorer les tests
   - Cocher **Skip migrations** pour ignorer les migrations

### Déploiement Local

```bash
# Sur le serveur
cd /opt/bantudelice242
./scripts/deploy.sh
```

## 📊 Étapes du Pipeline

### 1. Tests (`test` job)
- ✅ Checkout du code
- ✅ Installation des dépendances (Composer)
- ✅ Exécution des tests PHPUnit

### 2. Déploiement (`deploy` job)
- ✅ Backup de l'état actuel
- ✅ Pull du code
- ✅ Installation des dépendances
- ✅ Clear cache Laravel
- ✅ Exécution des migrations
- ✅ Optimisation Laravel (cache config, routes, views)
- ✅ Redémarrage PHP-FPM
- ✅ Vérification de conformité

## 🛡️ Sécurité

### Vérifications Automatiques

Le pipeline exécute automatiquement :
- `/usr/local/bin/policy-check-ports.sh --strict`
- `/usr/local/bin/policy-check-proxy-net.sh --strict`

### Rollback Automatique

En cas d'échec :
1. Restauration automatique depuis le backup
2. Restauration de la base de données si disponible
3. Clear cache
4. Notification dans les logs GitHub Actions

## 🔍 Health Checks

Le pipeline vérifie :
- ✅ **PHP-FPM** : Container `thedrop247_php` en cours d'exécution
- ✅ **Application** : Test HTTP sur `https://bantudelice.cg`

## 📝 Logs

### Voir les logs du déploiement

```bash
# Sur le serveur
tail -f /opt/bantudelice242/storage/logs/laravel.log
```

### Logs GitHub Actions

Dans l'onglet **Actions** du dépôt GitHub, cliquer sur le workflow pour voir les logs détaillés.

## 🚨 Dépannage

### Échec de connexion SSH

1. Vérifier que la clé SSH est correcte dans GitHub Secrets
2. Vérifier que la clé publique est dans `~/.ssh/authorized_keys` sur le serveur
3. Vérifier les permissions SSH

### Échec de migration

1. Vérifier la connexion à la base de données
2. Vérifier les logs : `tail -f storage/logs/laravel.log`
3. Vérifier les migrations : `php artisan migrate:status`

### Échec de build

1. Vérifier l'espace disque : `df -h`
2. Vérifier les dépendances : `composer install --no-dev`
3. Vérifier les logs : `composer install -vvv`

## 📦 Backups

Les backups sont stockés dans `/opt/backups/bantudelice242/` avec le format :
```
/opt/backups/bantudelice242/YYYYMMDD_HHMMSS/
├── .env.backup
└── database.sql
```

### Restauration manuelle

```bash
# Trouver le backup
ls -td /opt/backups/bantudelice242/*/

# Restaurer
cd /opt/bantudelice242
cp /opt/backups/bantudelice242/YYYYMMDD_HHMMSS/.env.backup .env

# Restaurer la base de données
docker exec -i $(docker ps | grep bantudelice.*postgres | awk '{print $1}') \
  psql -U bantudelice bantudelice < /opt/backups/bantudelice242/YYYYMMDD_HHMMSS/database.sql

# Clear cache
php artisan config:clear
php artisan cache:clear
```

## 🔗 Références

- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [Laravel Documentation](https://laravel.com/docs)
- [Architecture Nginx Centralisée](../reseau-telecom-nginx/GOVERNANCE.md)

