# Horizon production runtime

## Déploiement

Le déploiement production exécute `php artisan horizon:terminate` après les nettoyages Laravel. Cette commande demande à Horizon de s'arrêter proprement pour être relancé par son runtime, sans tuer brutalement les anciens workers.

Le script vérifie ensuite le runtime disponible :

- `systemd` via `horizon.service`;
- `supervisor` si un programme Horizon existe;
- sinon les workers legacy `bantudelice-worker:*` restent le fallback actif.

La vérification post-deploy est :

```bash
php artisan horizon:status
```

Un Horizon inactif est traité comme un avertissement tant que les queues legacy n'ont pas été validées et migrées.

## Rollback

Le rollback code exécute aussi :

```bash
php artisan horizon:terminate
php artisan horizon:status
```

Si Horizon a traité des jobs pendant un déploiement échoué, ne restaurez pas la base automatiquement. Utilisez le dump du dossier `/opt/backups/bantudelice/<timestamp>/` uniquement après décision humaine, car une restauration DB peut effacer des écritures post-déploiement.

## Sudoers cible

`github-runner` ne doit pas recevoir de sudo global. Les seules commandes à autoriser sans mot de passe sont :

```sudoers
github-runner ALL=(root) NOPASSWD: /usr/local/bin/bantudelice-fix-permissions.sh
github-runner ALL=(root) NOPASSWD: /bin/systemctl reload php8.3-fpm
github-runner ALL=(root) NOPASSWD: /usr/bin/supervisorctl restart bantudelice-worker:*
github-runner ALL=(root) NOPASSWD: /bin/systemctl restart horizon
github-runner ALL=(root) NOPASSWD: /usr/bin/supervisorctl restart horizon:*
```

À adapter avec `command -v systemctl supervisorctl` sur le serveur avant installation.
