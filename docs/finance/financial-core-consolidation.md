# Consolidation non destructive du noyau financier

## Statut

Décision d’intégration destinée à éviter que plusieurs PR financières concurrentes soient fusionnées ensemble sans arbitrage.

## Constat

Plusieurs branches ouvertes couvrent les mêmes responsabilités avec des schémas incompatibles :

| PR | Périmètre principal | Collision connue |
|---|---|---|
| #72 | statuts et affectations de paiement | crée `payment_allocations` |
| #74 | journal en partie double, affectations, rapprochement | crée `financial_accounts`, `payment_allocations`, `payment_reconciliation_cases` |
| #77 | métier Paiements complet et dashboard | modifie le ledger historique et le workflow de paiement |
| #81 | paiements, affectations, remboursements, rapprochement | crée `payment_allocations`, `payment_reconciliation_cases` et étend `financial_ledger_entries` |
| #82 | sous-comptes partenaires et trésorerie en partie double | crée `financial_accounts`, `financial_posting_batches`, `financial_postings` |

Ces PR ne doivent pas être fusionnées successivement telles quelles.

## Règles de travail

1. Ne jamais force-push sur la branche d’un autre développeur.
2. Ne jamais modifier directement une PR tierce pour la rendre compatible.
3. Créer une branche d’intégration distincte avec une base explicite.
4. Un seul propriétaire est autorisé pour chaque table métier.
5. Une migration déjà fusionnée n’est jamais remplacée silencieusement.
6. Les adaptations sont réalisées par une nouvelle migration ou une nouvelle PR documentée.
7. Les feature flags restent désactivés tant que le rapprochement n’est pas validé.
8. Une PR de dashboard ne devient jamais la source de vérité financière.

## Cible recommandée

### Registre et comptes partenaires

La PR #82 porte le registre de trésorerie et les sous-comptes :

- partenaire en attente ;
- partenaire disponible ;
- partenaire réservé ;
- MTN Collections ;
- MTN Disbursements ;
- revenus différés et acquis de BantuDelice ;
- taxes et frais opérateurs.

### Domaine Paiements

La PR #81 contient le périmètre métier le plus complet pour :

- états canoniques ;
- affectations ;
- remboursements ;
- dossiers de rapprochement.

Elle ne doit cependant pas être fusionnée telle quelle avec #82. Une PR d’intégration devra reprendre uniquement ce périmètre et remplacer ses appels au ledger historique par une interface vers le registre retenu.

### Dashboard

La PR #73 reste une couche de lecture. Elle doit être rebasée après stabilisation des sources financières et ne doit pas recalculer les soldes depuis les tables opérationnelles.

### Branches de comparaison

Les PR #72, #74 et #77 restent utiles comme références de règles et de tests. Elles ne doivent pas être fusionnées en bloc avec #81 et #82.

## Ordre d’intégration

1. Fusionner le garde-fou de conflit de migrations.
2. Valider puis fusionner un seul registre financier.
3. Créer une PR d’adaptation du domaine Paiements vers ce registre.
4. Exécuter les migrations sur une copie de la base de production.
5. Auditer et rapprocher les soldes historiques.
6. Activer la double écriture en mode miroir.
7. Comparer les résultats pendant une période contrôlée.
8. Basculer les dashboards après validation Finance et Exploitation.
9. Fermer les PR devenues obsolètes avec un commentaire de traçabilité, sans supprimer leurs branches.

## Contrôle automatisé

La commande suivante recherche plusieurs migrations créant la même table financière :

```bash
php artisan finance:audit-core-architecture
```

Un conflit provoque un code de sortie non nul et bloque la CI ou le déploiement.

Le moteur documenté est défini par :

```env
FINANCIAL_CORE_ENGINE=legacy
FINANCIAL_CORE_FAIL_ON_CONFLICT=true
```

La valeur `legacy` reste la valeur par défaut tant qu’aucun nouveau noyau n’est officiellement activé.
