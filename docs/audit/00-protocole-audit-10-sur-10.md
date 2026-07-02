# 00 — Protocole d’audit 10/10

## Objectif

Atteindre 100 % du **périmètre vérifiable**, avec une preuve reproductible pour chaque affirmation. Un audit est considéré terminé uniquement lorsqu’il couvre le code, les tests, les données, le runtime, les fournisseurs et la reprise après sinistre.

## Règle de preuve

Chaque contrôle doit avoir l’un des statuts suivants :

- **VÉRIFIÉ** : preuve directe disponible et reproductible ;
- **ÉCHEC CONFIRMÉ** : défaut reproduit par test ou observation ;
- **NON APPLICABLE** : justification écrite ;
- **BLOQUÉ** : accès ou environnement manquant, sans conclusion implicite.

Aucun élément `BLOQUÉ` ne peut être compté comme audité à 100 %.

## Matrice de couverture

| Axe | Couverture exigée | Preuve de clôture |
|---|---|---|
| Inventaire dépôt | 100 % fichiers applicatifs, migrations, routes, configs, scripts, tests et docs | manifeste versionné avec chemin, rôle, owner et statut de revue |
| Architecture | tous domaines et dépendances croisées | diagrammes + matrice consommateurs/producteurs |
| Paiements | initiation, callback, polling, réconciliation, annulation, remboursement | tests E2E et concurrence MySQL par provider |
| Finance | tous événements monétaires et soldes | invariants exécutables, rapprochement et audit DB |
| GePay | API, admin, portail, wallets, payouts, lots | isolation multi-tenant et tests d’idempotence/concurrence |
| Workflows | toutes transitions Food/Colis/Transport | table exhaustive et tests pour chaque arc autorisé/interdit |
| Queues | tous jobs, files, retry, timeout et échecs | inventaire job→queue→worker + tests de reprise |
| Sécurité | auth, autorisation, entrées, secrets, webhooks, uploads | SAST, DAST, tests IDOR/CSRF/XSS/SSRF et revue manuelle |
| Production | déploiement, migrations, scheduler, workers, monitoring | exécution contrôlée sur staging identique production |
| Données | intégrité et anomalies historiques | requêtes d’audit sur copie anonymisée de production |
| Résilience | panne MySQL, Redis, provider, réseau, worker | exercices de chaos documentés |
| Sauvegarde | RPO, RTO et restauration | restauration complète réussie et chronométrée |
| Performance | charge et concurrence réalistes | résultats p50/p95/p99 et seuils d’acceptation |
| Compatibilité | anciens clients web/mobile et routes historiques | télémétrie des consommateurs + tests de contrat |

## Phases obligatoires

### Phase 1 — Inventaire statique exhaustif

1. Générer le manifeste complet du dépôt.
2. Classer chaque fichier : actif, legacy actif, test, migration, documentation, infrastructure ou candidat mort.
3. Rechercher les appels directs aux modèles et services financiers.
4. Inventorier chaque route avec middleware, garde et policy.
5. Inventorier chaque job avec connexion, file, timeout, tries, backoff et idempotence.
6. Inventorier chaque statut et chaque transition.

### Phase 2 — Tests dynamiques locaux et CI

1. Exécuter la suite complète SQLite et MySQL.
2. Exécuter migrations fraîches et rollback complet.
3. Ajouter les tests manquants avant toute correction.
4. Exécuter les tests de concurrence avec plusieurs connexions DB.
5. Vérifier les triggers, contraintes et transactions réelles MySQL.

### Phase 3 — Audit d’une copie de production

1. Dump anonymisé ou accès read-only.
2. Recherche de doublons, orphelins, statuts impossibles et écarts de soldes.
3. Recalcul indépendant de tous les soldes.
4. Rapprochement paiements, registre, retraits et fournisseurs.
5. Mesure des volumes et de l’ancienneté des états intermédiaires.

### Phase 4 — Runtime et exploitation

1. Vérifier `.env` sans révéler les secrets.
2. Vérifier systemd/Supervisor, Horizon, Redis, scheduler et cron.
3. Vérifier alertes, rotation logs, espace disque et certificats.
4. Exécuter un déploiement complet sur staging.
5. Exécuter rollback code et scénario de migration compatible N/N-1.

### Phase 5 — Fournisseurs et résilience

1. Tester MTN et Airtel en sandbox ou environnement contractuel.
2. Vérifier signatures, callbacks perdus, doublons et polling.
3. Simuler timeout avant/après succès fournisseur.
4. Couper Redis, un worker, MySQL secondaire et le réseau fournisseur.
5. Vérifier qu’aucune opération monétaire n’est perdue ou dupliquée.

### Phase 6 — Sécurité offensive

1. IDOR horizontal et vertical sur toutes les ressources.
2. CSRF sur routes web.
3. XSS stockée/réfléchie dans champs et imports.
4. SSRF sur callbacks et URLs externes.
5. Injection SQL et mass assignment.
6. Uploads polyglottes et traversal.
7. Rejeu de webhooks et signatures invalides.
8. Scan des secrets et dépendances vulnérables.

### Phase 7 — Restauration, charge et décision

1. Restaurer une sauvegarde dans un environnement vierge.
2. Mesurer le RPO/RTO réel.
3. Charger les flux critiques avec concurrence réaliste.
4. Exécuter tous les invariants financiers après charge.
5. Émettre le verdict final avec zéro contrôle critique bloqué.

## Accès nécessaires pour un 100 % littéral

- clone ou archive complète du dépôt et historique utile ;
- environnement local/staging exécutable ;
- copie anonymisée de la base de production ;
- lecture de la configuration runtime ;
- accès aux logs et métriques ;
- accès sandbox MTN/Airtel et autres providers actifs ;
- inventaire des applications mobiles/web déployées ;
- accès aux sauvegardes et environnement de restauration.

## Critère final 10/10

Le score 10/10 n’est accordé que si :

- tous les contrôles critiques sont `VÉRIFIÉ` ou `NON APPLICABLE` ;
- aucune CI obligatoire n’est rouge ;
- les invariants financiers passent après tests de concurrence et chaos ;
- la restauration est démontrée ;
- les risques résiduels sont acceptés formellement par un responsable identifié.