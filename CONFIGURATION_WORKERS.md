# 🔧 Configuration des Workers Laravel

**Guide pour activer et configurer les workers de queue**

---

## 📋 Prérequis

1. **Queue driver configuré** : `database` ou `redis`
2. **Table `jobs` créée** (si driver = `database`)
3. **Supervisor ou systemd** pour gérer les workers

---

## ⚙️ Configuration

### 1. Activer la queue database

**Fichier `.env` :**
```env
QUEUE_CONNECTION=database
```

**Créer la table jobs (si pas déjà fait) :**
```bash
php artisan queue:table
php artisan migrate
```

### 2. Configuration Supervisor (Recommandé)

**Créer le fichier `/etc/supervisor/conf.d/bantudelice-worker.conf` :**

```ini
[program:bantudelice-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /opt/bantudelice242/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/opt/bantudelice242/storage/logs/worker.log
stopwaitsecs=3600
```

**Activer Supervisor :**
```bash
# Recharger la configuration
sudo supervisorctl reread
sudo supervisorctl update

# Démarrer les workers
sudo supervisorctl start bantudelice-worker:*

# Vérifier le statut
sudo supervisorctl status
```

### 3. Configuration systemd (Alternative)

**Créer le fichier `/etc/systemd/system/bantudelice-worker.service` :**

```ini
[Unit]
Description=BantuDelice Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /opt/bantudelice242/artisan queue:work database --sleep=3 --tries=3

[Install]
WantedBy=multi-user.target
```

**Activer le service :**
```bash
sudo systemctl daemon-reload
sudo systemctl enable bantudelice-worker
sudo systemctl start bantudelice-worker
sudo systemctl status bantudelice-worker
```

---

## 🧪 Tests

### Test 1 : Vérifier que les jobs sont traités

```bash
# Lancer un worker manuellement (pour test)
php artisan queue:work database --once

# Vérifier les jobs en attente
php artisan queue:work database --verbose
```

### Test 2 : Vérifier les jobs échoués

```bash
# Lister les jobs échoués
php artisan queue:failed

# Réessayer un job échoué
php artisan queue:retry {job_id}

# Réessayer tous les jobs échoués
php artisan queue:retry all
```

### Test 3 : Surveiller les workers

```bash
# Logs Supervisor
tail -f /opt/bantudelice242/storage/logs/worker.log

# Logs Laravel
tail -f storage/logs/laravel.log | grep "queue"
```

---

## 📊 Monitoring

### Commandes utiles

```bash
# Nombre de jobs en attente
php artisan queue:monitor database:default

# Statistiques
mysql -u root -p thedrop247 -e "SELECT COUNT(*) as pending FROM jobs WHERE queue = 'default';"
```

---

## 🐛 Dépannage

### Problème : Jobs ne sont pas traités

**Vérifier :**
1. Worker actif : `sudo supervisorctl status`
2. Queue connection : `php artisan tinker --execute="echo config('queue.default');"`
3. Table jobs existe : `php artisan migrate:status`

### Problème : Jobs échouent

**Vérifier les logs :**
```bash
tail -f storage/logs/laravel.log | grep "Failed"
php artisan queue:failed
```

---

## ✅ Checklist

- [ ] Queue driver configuré (`QUEUE_CONNECTION=database`)
- [ ] Table `jobs` créée
- [ ] Supervisor/systemd configuré
- [ ] Workers démarrés
- [ ] Test : créer une commande → vérifier que le job est traité

