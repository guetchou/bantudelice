# CI/CD BantuDelice

## Objectif

Aucun code applicatif ne doit être copié manuellement vers un VPS. GitHub est la source de vérité et chaque serveur doit exécuter un SHA Git identifiable.

## Chaîne officielle

```text
branche
→ Pull Request
→ BantuDelice CI
→ revue humaine
→ merge main
→ staging automatique
→ validation staging
→ approbation environnement production
→ production
→ smoke tests
→ rollback du code si nécessaire
```

## Workflows

| Fichier | Fonction |
|---|---|
| `.github/workflows/ci.yml` | syntaxe, migrations, tests, routes, scheduler et build frontend |
| `.github/workflows/deploy-staging.yml` | déploie automatiquement sur staging le SHA de `main` validé par la CI |
| `.github/workflows/deploy.yml` | qualité, déploiement production, smoke tests et rollback |
| `.github/workflows/frontend-quality.yml` | validation indépendante du frontend et des fichiers YAML |

## Protection de `main`

Dans GitHub :

```text
Settings → Branches → Branch protection rules → main
```

Activer au minimum :

- Pull Request obligatoire avant fusion ;
- une approbation humaine minimum ;
- conversation résolue avant fusion ;
- branche à jour avant fusion ;
- status checks obligatoires ;
- interdiction du push direct ;
- interdiction du force push ;
- interdiction de suppression de la branche.

Checks à rendre obligatoires :

```text
BantuDelice CI / quality
Frontend Quality / build
```

Ajouter les autres checks spécialisés stables du dépôt lorsqu'ils sont opérationnels.

## Environnement `staging`

Créer :

```text
Settings → Environments → New environment → staging
```

Secrets requis :

```text
STAGING_DEPLOY_HOST
STAGING_DEPLOY_USER
STAGING_DEPLOY_SSH_KEY
STAGING_APP_URL
```

Le staging doit être un serveur distinct. Le dépôt doit être cloné dans :

```text
/opt/bantudelice
```

Préparation minimale :

```bash
sudo mkdir -p /opt/bantudelice /opt/backups/bantudelice
sudo chown -R deploy:deploy /opt/bantudelice /opt/backups/bantudelice

git clone git@github.com:guetchou/bantudelice.git /opt/bantudelice
cd /opt/bantudelice
cp .env.example .env
composer install
php artisan key:generate
```

Le `.env`, la base, les files d'attente, les domaines et les identifiants MTN staging doivent être distincts de la production.

## Environnement `production`

L'environnement `production` existe déjà dans le workflow de déploiement. Sa protection est obligatoire.

Dans GitHub :

```text
Settings → Environments → production
```

Configurer :

- required reviewers ;
- empêcher l'auto-approbation si l'option est disponible ;
- limiter les branches de déploiement à `main` ;
- conserver les secrets uniquement dans cet environnement.

Secrets utilisés par le workflow existant :

```text
DEPLOY_HOST
DEPLOY_USER
DEPLOY_SSH_KEY
```

Sans required reviewer, un push fusionné dans `main` peut atteindre automatiquement la production. La protection doit donc être configurée avant la fusion de cette gouvernance CI/CD.

## Déploiement

Le workflow transmet un SHA exact à :

```text
scripts/deploy/production.sh
```

Ce script :

- refuse les fichiers suivis modifiés sur le serveur ;
- enregistre le SHA précédent ;
- sauvegarde `.env` et tente un dump de la base ;
- exécute `git fetch` puis `git reset --hard` vers le SHA validé ;
- installe les dépendances ;
- construit le frontend ;
- exécute les migrations ;
- vide les caches ;
- redémarre PHP-FPM et les workers ;
- affiche le SHA réellement déployé.

Le workflow staging vérifie ensuite :

```bash
git rev-parse HEAD
```

et compare le résultat au SHA attendu.

## Rollback

Le rollback officiel utilise :

```text
scripts/deploy/rollback.sh
```

Il restaure le SHA précédent. Il ne restaure jamais automatiquement la base de données, car une restauration DB peut supprimer des données créées après le déploiement.

## Règles serveur

Interdits :

```text
rsync
scp de fichiers applicatifs
sftp/FTP
édition directe avec nano/vim
copie depuis un poste local
```

Autorisés :

- consultation des logs ;
- commandes de diagnostic en lecture seule ;
- commandes Artisan prévues par les scripts ;
- déploiement et rollback déclenchés par GitHub Actions.

Si `git status --short` affiche un fichier suivi modifié, le déploiement doit être arrêté et la cause examinée.

## GePay

Le déploiement du code et l'activation métier sont deux opérations distinctes.

Valeurs par défaut lors du déploiement :

```env
GEPAY_BANTUDELICE_COLLECTIONS_ENABLED=false
GEPAY_BANTUDELICE_WITHDRAWALS_ENABLED=false
```

Ordre d'activation :

1. déployer le code en staging ;
2. tester Collection ;
3. tester Disbursement ;
4. fusionner et déployer le même SHA ;
5. activer Collection avec surveillance ;
6. activer les retraits après validation séparée.

## Vérification d'un déploiement

```bash
cd /opt/bantudelice
git rev-parse HEAD
git status --short
php artisan migrate:status
php artisan schedule:list
```

Le SHA doit correspondre au SHA annoncé dans GitHub Actions et `git status --short` ne doit afficher aucune modification suivie.

## Incident GitHub Actions

Si un workflow échoue avant la première étape et sans log :

1. vérifier `Settings → Actions → General` ;
2. vérifier les limites d'utilisation ou la facturation ;
3. vérifier les permissions des actions tierces ;
4. vérifier les environnements et secrets ;
5. ne jamais remplacer la CI/CD par un déploiement manuel.
