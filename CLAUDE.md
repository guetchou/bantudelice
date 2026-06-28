# Politique Claude — BantuDelice

Toute modification doit suivre :

```text
branche dédiée → commits → push GitHub → Pull Request → CI verte → revue humaine → merge main → staging → production approuvée
```

## Obligatoire

- partir de `main` à jour ;
- travailler sur `feat/...`, `fix/...`, `chore/...` ou `docs/...` ;
- exécuter les tests ciblés puis la suite adaptée ;
- pousser tous les changements dans GitHub ;
- documenter dans la PR les tests, résultats et risques ;
- attendre la CI et la revue humaine ;
- ne jamais fusionner sans demande explicite.

## Interdit

- modification directe de `main` ;
- copie manuelle de code vers un serveur avec `rsync`, `scp`, FTP ou équivalent ;
- édition de fichiers applicatifs sur le VPS ;
- déploiement d'une branche non fusionnée ;
- contournement d'un test rouge ;
- activation d'un feature flag de production sans autorisation ;
- publication de secrets, tokens ou données personnelles ;
- exécution des Pull Requests sur le VPS de production ;
- ajout de `sudo` au compte du runner CI.

## Runners obligatoires

Les Pull Requests et contrôles qualité utilisent exclusivement :

```yaml
runs-on: [self-hosted, ci, isolated]
```

Ce runner doit être installé sur une machine ou VM dédiée, distincte de la production, sans secret de production et sans sudo.

Les déploiements utilisent exclusivement :

```yaml
runs-on: [self-hosted, deploy, isolated]
```

Le runner de déploiement doit être distinct du runner CI et ne doit jamais exécuter un workflow `pull_request`.

## Pipelines

```text
.github/workflows/ci.yml              contrôle qualité
.github/workflows/deploy-staging.yml  staging automatique
.github/workflows/deploy.yml          production manuelle
```

Le déploiement doit toujours utiliser un SHA Git identifiable. Le VPS n'est jamais une source de vérité.

Si GitHub Actions ne démarre pas, Claude doit signaler le blocage dans la PR et attendre une décision humaine. Il ne doit pas remplacer la CI/CD par une copie manuelle.

Rapport final obligatoire : branche, PR, SHA, tests, CI, staging, production, risques résiduels et décision `FUSIONNABLE` ou `NON FUSIONNABLE`.
