# 08 — Production et exploitation

## État réel

Le dépôt contient un script de déploiement reproductible, sauvegarde de `.env`, tentative de dump MySQL, migrations sous maintenance, redémarrage PHP-FPM/workers/Horizon et correction des permissions. Un rollback code existe et exige une décision humaine pour la base. Liveness/readiness applicatifs existent.

Un incident documenté montre cependant qu’une restauration antérieure n’avait pas de configuration transactionnelle valide ni dump exploitable identifié.

## Anomalies

### PROD-001 — Critique — Déploiement autorisé sans sauvegarde DB

- **Fichier :** `scripts/deploy/production.sh:33-43`.
- **Fait :** échec de `mysqldump` ou conteneur absent produit un avertissement, puis le déploiement continue.
- **Conséquence :** migration exécutée sans point de restauration.
- **Correction :** backup vérifié obligatoire avant toute migration ; taille non nulle, checksum et test d’ouverture.
- **Tests :** credentials invalides, conteneur absent, disque plein.
- **Régression :** faible, disponibilité de déploiement réduite mais sécurité accrue.

### PROD-002 — Haute — Rollback code seulement

- **Fichier :** `scripts/deploy/rollback.sh`.
- **Fait :** le code revient au SHA précédent, la DB reste au nouveau schéma sauf intervention humaine.
- **Conséquence :** ancien code incompatible avec migration non rétrocompatible.
- **Correction :** migrations expand/contract, déclaration de compatibilité N/N-1 et runbook de restauration.
- **Tests :** déploiement puis rollback avec migrations.
- **Régression :** moyenne.

### PROD-003 — Haute — Horizon non obligatoire

- **Fichier :** `production.sh:84-107`.
- **Fait :** Horizon absent/inactif est un avertissement ; workers legacy supposés disponibles sans vérification de couverture.
- **Conséquence :** files critiques non consommées après un déploiement déclaré réussi.
- **Correction :** vérification stricte file→consommateur et échec du déploiement si couverture incomplète.
- **Tests :** Horizon stoppé, supervisor incomplet.
- **Régression :** moyenne.

### PROD-004 — Haute — Readiness Redis conditionnelle au mauvais signal

- **Fichier :** `ModuleHealthController::ready`.
- **Fait :** Redis n’est requis que si `queue.default`, cache ou session vaut `redis`. Les connexions modulaires peuvent utiliser Redis tandis que `queue.default` reste `sync`.
- **Conséquence :** readiness verte avec Redis indisponible alors que Food/Colis/Transport en dépendent.
- **Correction :** vérifier toutes les connexions actives, Horizon et queues critiques.
- **Tests :** default sync + modules Redis indisponibles.
- **Régression :** faible.

### PROD-005 — Haute — Métriques de queues inexactes

- **Fichier :** `OperationalHealthService::queues`.
- **Fait :** lecture des tables DB pour des queues Redis.
- **Conséquence :** backlog invisible.
- **Correction :** métriques Horizon/Redis et âge du plus ancien job.
- **Tests :** backlog Redis simulé.

### PROD-006 — Haute — Pas de smoke test dans le script principal

- **Fait :** après migrations et redémarrages, le script affiche un succès sans appeler liveness, readiness, route critique ou transaction de test.
- **Correction :** smoke tests bloquants et rollback automatique du code si échec.
- **Tests :** DB/Redis/route indisponibles.
- **Régression :** faible.

### PROD-007 — Moyenne — Caches uniquement vidés

- **Fait :** règle projet `*:clear` seulement ; pas de `config:cache`/`route:cache`.
- **Conséquence :** performance moindre et configuration plus sensible aux lectures runtime.
- **Correction :** décider et tester une politique unique ; ne pas changer sans audit des routes/closures.
- **Tests :** boot après cache de configuration et routes.

### PROD-008 — Haute — Sauvegarde sans preuve de restauration

- **Fait :** dump créé, mais aucun test automatisé de restauration ni politique de rétention visible dans le script.
- **Correction :** restauration périodique sur environnement isolé, rapport RPO/RTO et chiffrement des backups.
- **Tests :** restauration complète et validation d’intégrité.

### PROD-009 — Moyenne — Endpoints de santé trop détaillés et publics

- **Fait :** modules, dépendances, queues et workers sont accessibles sans auth dans `routes/api.php`.
- **Correction :** liveness minimal public, détails internes protégés.

### PROD-010 — Haute — Dépendances externes sans mode dégradé formalisé

- **Fait :** comportements dispersés lorsque Redis, MySQL, MTN/Airtel ou Pusher sont indisponibles.
- **Correction :** matrice de dégradation : opérations refusées, mises en attente, reprises et alertes.
- **Tests :** chaos ciblé par dépendance.

## Checklist de mise en production minimale

- backup vérifié et restauration testée ;
- migrations expand/contract ;
- Redis, Horizon et scheduler actifs ;
- couverture de toutes les files ;
- liveness/readiness et smoke tests ;
- rotation logs et masquage ;
- supervision backlog/failed jobs/paiements incertains ;
- runbook fournisseur indisponible ;
- rollback code compatible DB ;
- validation humaine des opérations financières critiques.

## Conclusion

Le déploiement est partiellement industrialisé, mais il tolère encore trop d’échecs critiques sous forme d’avertissements. Il n’est pas suffisant pour garantir un service financier 24/7 sans renforcer backup, readiness, queues et smoke tests.