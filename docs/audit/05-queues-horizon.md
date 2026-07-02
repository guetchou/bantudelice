# 05 — Redis, Horizon et jobs

## Configuration réelle

Horizon déclare les files `critical`, `payments`, `orders`, `food`, `notifications`, `default`, `colis` et `transport`. Les connexions nommées `database_food`, `database_colis` et `database_transport` utilisent en réalité Redis hors environnement de test, puis `sync` en test. Le nom historique de ces connexions crée une ambiguïté d’exploitation.

## Anomalies

### QUEUE-001 — Haute — Supervision des mauvaises sources

- **Fichiers :** `OperationalHealthService::queues`, `config/queue.php`.
- **Fait :** l’état des files compte les lignes des tables `jobs`/`failed_jobs`, alors que les files modulaires utilisent Redis en production.
- **Conséquence :** endpoint santé pouvant annoncer zéro job alors que Redis contient un backlog.
- **Correction :** métriques Horizon/Redis pour les connexions Redis ; conserver métriques DB seulement pour les vraies files DB.
- **Tests :** job Redis en attente visible par health.
- **Régression :** faible.

### QUEUE-002 — Haute — Horizon inactif non bloquant

- **Fichiers :** `scripts/deploy/production.sh:84-107`; documentation Horizon.
- **Fait :** absence ou inactivité Horizon produit un avertissement, puis les workers legacy sont supposés prendre le relais.
- **Conséquence :** déploiement déclaré réussi sans garantie qu’un worker consomme chaque file.
- **Correction :** matrice obligatoire file→runtime ; readiness rouge si une file critique n’a aucun consommateur.
- **Tests :** Horizon arrêté, worker absent, file non couverte.
- **Régression :** moyenne.

### QUEUE-003 — Haute — Retry callback conclut à tort un échec

- **Fichier :** `RetryPaymentCallbackJob.php:49-100`.
- **Fait :** après la dernière exception, le paiement est marqué `FAILED`.
- **Conséquence :** exception technique transformée en résultat fournisseur terminal.
- **Correction :** conserver `UNKNOWN/PENDING_REVIEW`, déclencher polling et alerte.
- **Tests :** fournisseur indisponible après encaissement.
- **Régression :** élevée.

### QUEUE-004 — Moyenne — Modèle sérialisé dans le retry

- **Fichiers :** `ModuleQueueService`, `RetryPaymentCallbackJob`.
- **Fait :** le job transporte un objet `Payment` au lieu d’un ID, puis le recharge.
- **Conséquence :** sérialisation inutile et comportement fragile après suppression/soft delete.
- **Correction :** transporter uniquement `payment_id` et une référence de payload chiffré/outbox.
- **Tests :** paiement supprimé, restauré, devenu terminal.
- **Régression :** faible.

### QUEUE-005 — Moyenne — Paramètres de retry incomplets

- **Fait :** certains jobs définissent timeout/backoff mais pas `tries`; ils dépendent du superviseur Horizon. Le même job peut donc se comporter différemment sous worker legacy.
- **Conséquence :** nombre d’essais non uniforme.
- **Correction :** politique explicite par job critique et validation automatique contre la configuration Horizon.
- **Tests :** timeout, backoff et échec définitif.
- **Régression :** faible.

### QUEUE-006 — Haute — Pas d’outbox générale

- **Fait :** plusieurs événements/jobs sont dispatchés après une transaction ou directement depuis un contrôleur ; tous les flux ne disposent pas d’une outbox durable.
- **Conséquence :** crash entre commit DB et dispatch, ou événement exécuté avant visibilité complète des données.
- **Correction :** outbox transactionnelle pour paiements, finance et transitions métier critiques.
- **Tests :** crash après commit, double publication et reprise.
- **Régression :** élevée.

### QUEUE-007 — Moyenne — Jobs sur entités disparues

- **Fait :** plusieurs jobs gèrent silencieusement une entité introuvable et terminent avec succès.
- **Conséquence :** perte silencieuse d’une opération qui aurait dû être investiguée.
- **Correction :** distinguer suppression légitime, soft delete, corruption et absence temporaire ; métrique dédiée.
- **Tests :** entité absente selon chaque état attendu.
- **Régression :** faible.

## Matrice cible

| File | Nature | Exigence |
|---|---|---|
| critical | incidents financiers critiques | aucun abandon silencieux, alerte immédiate |
| payments | callback/polling/réconciliation | idempotence et statut incertain |
| orders | orchestration commande | after-commit et déduplication |
| food/colis/transport | effets métier | état verrouillé et entité rechargée |
| notifications | non financier | peut échouer sans annuler l’opération métier |
| default | aucun flux critique non classé | surveillance et réduction progressive |

## Conclusion

Redis/Horizon est configuré, mais la preuve opérationnelle file→worker et les métriques sont insuffisantes. L’exploitation financière exige un readiness qui vérifie réellement Redis, Horizon, les consommateurs et l’âge du plus ancien job.