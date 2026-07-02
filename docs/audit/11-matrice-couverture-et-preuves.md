# 11 — Matrice de couverture et preuves

**Objectif :** suivre l’avancement vers un audit 10/10 sans confondre lecture statique, test exécuté et preuve production.

| Domaine | Statique | Tests locaux | MySQL/concurrence | Données production | Runtime production | Statut global |
|---|---:|---:|---:|---:|---:|---|
| Architecture | avancé | partiel | N/A | bloqué | bloqué | EN COURS |
| Food | avancé | observé CI | non exécuté | bloqué | bloqué | EN COURS |
| Colis | avancé | observé CI | non exécuté | bloqué | bloqué | EN COURS |
| Transport | avancé | observé CI | non exécuté | bloqué | bloqué | EN COURS |
| Payments | avancé | partiel | non exécuté | bloqué | bloqué | EN COURS |
| Finance V2 | avancé | partiel | non exécuté | bloqué | bloqué | EN COURS |
| GePay API | avancé | CI partiellement rouge | non validé | bloqué | bloqué | EN COURS |
| Portail GePay | schéma en PR | CI rouge | non validé | N/A | N/A | BLOQUÉ PR #102 |
| Queues/Horizon | avancé | non exécuté | N/A | bloqué | bloqué | EN COURS |
| Sécurité | statique partielle | non exécuté | N/A | bloqué | bloqué | EN COURS |
| Déploiement | scripts lus | non exécuté | N/A | bloqué | bloqué | EN COURS |
| Sauvegarde/restauration | documentation lue | non exécuté | N/A | bloqué | bloqué | BLOQUÉ |
| Performance/charge | non commencé | non exécuté | non exécuté | bloqué | bloqué | BLOQUÉ |
| Fournisseurs MTN/Airtel | code/config lus | non exécuté | N/A | bloqué | bloqué | BLOQUÉ |
| Compatibilité clients historiques | routes lues | non exécuté | N/A | télémétrie absente | bloqué | BLOQUÉ |

## Preuves déjà disponibles

- Cartographie des principaux domaines, registres, routes et services.
- Analyse du registre Finance V2 et du futur ledger GePay.
- Analyse des callbacks, polling, réconciliation et retraits.
- Analyse statique de plusieurs vulnérabilités critiques.
- Analyse des scripts de déploiement, readiness et gestion Horizon.
- Résultats GitHub Actions observés pour PR #102.

## Preuves encore obligatoires

### Accès dépôt exécutable

- clone complet du dépôt ;
- version PHP/Composer/Node identique à la CI ;
- MySQL 8 et Redis ;
- possibilité d’exécuter la suite complète et les migrations.

### Données

- dump anonymisé de production ou accès read-only ;
- dictionnaire des tables réellement utilisées ;
- volumes par table ;
- doublons de références fournisseurs ;
- paiements et retraits bloqués ;
- rapprochement indépendant des soldes.

### Runtime

- `.env` expurgé des valeurs secrètes ;
- sorties `systemctl`, Supervisor, Horizon, scheduler et cron ;
- configuration Nginx/PHP-FPM ;
- métriques Redis/MySQL ;
- rotation et rétention des logs.

### Fournisseurs

- accès sandbox ou preuves contractuelles MTN/Airtel ;
- exemples signés de callback ;
- statuts possibles et délais ;
- comportement en doublon, timeout et inversion.

### Sécurité dynamique

- environnement staging isolé ;
- comptes de test par rôle ;
- tests IDOR, CSRF, XSS, SSRF, upload et replay ;
- scan secrets et dépendances.

### Continuité

- sauvegarde récente ;
- environnement de restauration ;
- mesure RPO/RTO ;
- exercice rollback et reprise des queues.

## Règle de score

Aucun pourcentage final ne sera annoncé tant que des axes critiques restent `BLOQUÉ`. Le score 10/10 exige des preuves dynamiques et production, pas seulement une revue du code.