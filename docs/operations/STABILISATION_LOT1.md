# BantuDelice — Lot 1 de stabilisation

## Périmètre de cette étape

Cette première étape ajoute une pile autonome et réversible :

- Redis 7.4 avec persistance AOF ;
- Uptime Kuma ;
- réseau Docker interne dédié ;
- volumes persistants ;
- contrôle automatisé de l'état des services.

Aucune dépendance PHP n'est ajoutée dans cette étape. Laravel Horizon sera intégré dans une étape séparée afin que `composer.json` et `composer.lock` soient régénérés ensemble puis testés.

## 1. Préparer les variables

```bash
cp deploy/.env.stabilisation.example deploy/.env.stabilisation
```

Remplacer impérativement `REDIS_PASSWORD` par un secret long et aléatoire.

Le fichier `deploy/.env.stabilisation` ne doit pas être versionné.

## 2. Démarrer les services

```bash
docker compose \
  --env-file deploy/.env.stabilisation \
  -f deploy/compose.stabilisation.yml \
  up -d
```

## 3. Vérifier les services

```bash
chmod +x scripts/check_stabilisation_stack.sh
./scripts/check_stabilisation_stack.sh
```

Le contrôle échoue explicitement lorsque Redis est absent, non sain ou lorsque Uptime Kuma n'est pas démarré.

## 4. Relier Laravel à Redis

Reporter dans le `.env` de production Laravel :

```dotenv
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=<secret Redis>
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1
```

Attention : dans la configuration Docker fournie, Redis n'est volontairement pas publié sur une interface publique. Si Laravel tourne hors Docker, il faut soit rattacher Laravel au même réseau Docker, soit publier Redis uniquement sur `127.0.0.1` avec une règle explicite. Ne jamais exposer le port 6379 sur Internet.

## 5. Configurer Uptime Kuma

Uptime Kuma écoute uniquement sur l'interface locale du serveur :

```text
127.0.0.1:3001
```

L'accès doit passer par un tunnel SSH ou un reverse proxy HTTPS protégé.

Moniteurs initiaux recommandés :

1. page publique BantuDelice ;
2. endpoint de santé API ;
3. serveur Soketi/WebSocket ;
4. callback MTN MoMo ;
5. callback Airtel Money ;
6. expiration du certificat TLS.

## Critères d'acceptation

- Redis est `healthy` ;
- la persistance AOF est active ;
- Uptime Kuma redémarre automatiquement après redémarrage du serveur ;
- Redis n'est pas accessible publiquement ;
- Laravel peut lire et écrire dans Redis ;
- une file de test Laravel est exécutée avec succès avant migration complète des jobs.

## Étape suivante

Intégration de Laravel Horizon :

- ajout contrôlé de `laravel/horizon` compatible Laravel 10 ;
- régénération de `composer.lock` ;
- configuration des files `critical`, `payments`, `orders`, `notifications` et `default` ;
- service systemd ou conteneur dédié ;
- accès Horizon protégé par rôle ;
- tests d'échec, reprise et délai des jobs.
