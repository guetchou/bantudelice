# VARIABLES D'ENVIRONNEMENT - RÉFÉRENCE COMPLÈTE

Ce document liste toutes les variables d'environnement utilisées dans l'application TheDrop247.

## FICHIER .env RECOMMANDÉ

Créez un fichier `.env` à la racine du projet avec les variables suivantes :

```env
# ==========================================
# APPLICATION
# ==========================================
APP_NAME="TheDrop247"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://votre-domaine.com
ASSET_URL=

# ==========================================
# LOGGING
# ==========================================
LOG_CHANNEL=stack
LOG_LEVEL=debug
LOG_SLACK_WEBHOOK_URL=
PAPERTRAIL_URL=
PAPERTRAIL_PORT=
LOG_STDERR_FORMATTER=

# ==========================================
# BASE DE DONNÉES
# ==========================================
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=thedrop247
DB_USERNAME=thedrop247_user
DB_PASSWORD=votre_mot_de_passe_securise
DB_SOCKET=
DB_FOREIGN_KEYS=true
MYSQL_ATTR_SSL_CA=
DATABASE_URL=

# ==========================================
# CACHE
# ==========================================
CACHE_DRIVER=file
CACHE_PREFIX=thedrop247_cache

# ==========================================
# SESSIONS
# ==========================================
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_DOMAIN=
SESSION_SECURE_COOKIE=true
SESSION_CONNECTION=
SESSION_STORE=
SESSION_COOKIE=thedrop247_session

# ==========================================
# REDIS (Optionnel)
# ==========================================
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1
REDIS_PREFIX=
REDIS_CLUSTER=
REDIS_URL=

# ==========================================
# MEMCACHED (Optionnel)
# ==========================================
MEMCACHED_PERSISTENT_ID=
MEMCACHED_USERNAME=
MEMCACHED_PASSWORD=
MEMCACHED_HOST=127.0.0.1
MEMCACHED_PORT=11211

# ==========================================
# QUEUE (File d'attente)
# ==========================================
QUEUE_CONNECTION=sync
QUEUE_FAILED_DRIVER=database
REDIS_QUEUE=default
SQS_PREFIX=
SQS_QUEUE=
DYNAMODB_CACHE_TABLE=cache
DYNAMODB_ENDPOINT=

# ==========================================
# EMAIL / MAIL
# ==========================================
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@thedrop247.com"
MAIL_FROM_NAME="TheDrop247"
MAIL_LOG_CHANNEL=

# Mailgun
MAILGUN_DOMAIN=
MAILGUN_SECRET=
MAILGUN_ENDPOINT=api.mailgun.net

# Postmark
POSTMARK_TOKEN=

# ==========================================
# AWS (S3, SES, SQS)
# ==========================================
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false
AWS_URL=

# ==========================================
# PAYPAL
# ==========================================
PAYPAL_CLIENT_ID=
PAYPAL_SECRET=
PAYPAL_MODE=sandbox

# ==========================================
# MOBILE MONEY (MoMo)
# ==========================================
MOMO_API_KEY=
MOMO_API_SECRET=
MOMO_API_URL=https://api.momo.cg/v1
MOMO_ENVIRONMENT=sandbox

# ==========================================
# BROADCASTING (Pusher)
# ==========================================
BROADCAST_DRIVER=null
PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_APP_CLUSTER=mt1

MIX_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
MIX_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"

# ==========================================
# FILESYSTEM
# ==========================================
FILESYSTEM_DRIVER=local
FILESYSTEM_CLOUD=s3

# ==========================================
# HASHING
# ==========================================
BCRYPT_ROUNDS=10

# ==========================================
# VIEW
# ==========================================
VIEW_COMPILED_PATH=
```

## VARIABLES PAR CATÉGORIE

### 🔴 Variables OBLIGATOIRES

Ces variables doivent absolument être configurées pour que l'application fonctionne :

1. **APP_KEY** - Générer avec : `php artisan key:generate`
2. **APP_NAME** - Nom de l'application
3. **APP_URL** - URL de base (https://votre-domaine.com)
4. **DB_CONNECTION** - Type de base de données (mysql)
5. **DB_HOST** - Hôte de la base de données
6. **DB_DATABASE** - Nom de la base de données
7. **DB_USERNAME** - Utilisateur de la base
8. **DB_PASSWORD** - Mot de passe de la base

### 🟡 Variables RECOMMANDÉES

Ces variables sont recommandées pour un fonctionnement optimal :

1. **APP_ENV** - Environnement (production, staging, local)
2. **APP_DEBUG** - Mode debug (false en production)
3. **SESSION_SECURE_COOKIE** - true en production avec HTTPS
4. **MAIL_*** - Configuration email
5. **PAYPAL_*** - Configuration PayPal
6. **CACHE_DRIVER** - Driver de cache (file, redis)

### 🟢 Variables OPTIONNELLES

Ces variables sont optionnelles selon vos besoins :

1. **REDIS_*** - Si utilisation de Redis
2. **AWS_*** - Si utilisation de AWS S3/SES
3. **PUSHER_*** - Si utilisation de broadcasting temps réel
4. **MAILGUN_*** ou **POSTMARK_*** - Services email alternatifs

## INSTRUCTIONS D'INSTALLATION

1. **Créer le fichier .env**
   ```bash
   cp .env.example .env  # Si vous avez un .env.example
   # Sinon créer manuellement le fichier .env
   ```

2. **Générer la clé d'application**
   ```bash
   php artisan key:generate
   ```

3. **Configurer la base de données**
   - Créer la base de données MySQL
   - Remplir DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD

4. **Exécuter les migrations**
   ```bash
   php artisan migrate
   ```

5. **Installer Passport (OAuth2)**
   ```bash
   php artisan passport:install
   ```

## NOTES IMPORTANTES

### Sécurité en Production

- ⚠️ **APP_DEBUG=false** - Toujours désactiver en production
- ⚠️ **SESSION_SECURE_COOKIE=true** - Activer avec HTTPS
- ⚠️ **APP_ENV=production** - Utiliser l'environnement production
- ⚠️ Ne jamais commiter le fichier `.env` (déjà dans .gitignore)

### PayPal

- **PAYPAL_MODE=sandbox** pour les tests
- **PAYPAL_MODE=live** pour la production
- Obtenir les clés sur : https://developer.paypal.com/

### Base de Données

- Par défaut : MySQL sur localhost:3306
- Support PostgreSQL et SQLite si nécessaire
- Changer les credentials par défaut (`forge/forge`)

## VARIABLES UTILISÉES PAR CONFIG FILE

| Config File | Variables Utilisées |
|-------------|---------------------|
| `config/app.php` | APP_NAME, APP_ENV, APP_DEBUG, APP_URL, ASSET_URL, APP_KEY |
| `config/database.php` | DB_CONNECTION, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD, DB_SOCKET, MYSQL_ATTR_SSL_CA, DB_FOREIGN_KEYS, DATABASE_URL |
| `config/cache.php` | CACHE_DRIVER, MEMCACHED_*, REDIS_*, AWS_*, DYNAMODB_*, CACHE_PREFIX |
| `config/session.php` | SESSION_DRIVER, SESSION_LIFETIME, SESSION_DOMAIN, SESSION_SECURE_COOKIE, SESSION_CONNECTION, SESSION_STORE, SESSION_COOKIE |
| `config/queue.php` | QUEUE_CONNECTION, QUEUE_FAILED_DRIVER, REDIS_QUEUE, SQS_*, AWS_* |
| `config/mail.php` | MAIL_MAILER, MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_ENCRYPTION, MAIL_FROM_ADDRESS, MAIL_FROM_NAME, MAIL_LOG_CHANNEL |
| `config/services.php` | MAILGUN_*, POSTMARK_*, AWS_* |
| `config/paypal.php` | PAYPAL_CLIENT_ID, PAYPAL_SECRET, PAYPAL_MODE |
| `config/broadcasting.php` | BROADCAST_DRIVER, PUSHER_* |
| `config/filesystems.php` | FILESYSTEM_DRIVER, FILESYSTEM_CLOUD, AWS_* |
| `config/hashing.php` | BCRYPT_ROUNDS |
| `config/logging.php` | LOG_CHANNEL, LOG_SLACK_WEBHOOK_URL, PAPERTRAIL_*, LOG_STDERR_FORMATTER |
| `config/view.php` | VIEW_COMPILED_PATH |

## TOTAL DES VARIABLES

- **Variables obligatoires** : 8
- **Variables recommandées** : ~15
- **Variables optionnelles** : ~40
- **Total identifié** : ~63 variables d'environnement

