# Workflow restaurant — intégrité et contraintes SQL

Ce document décrit la procédure de contrôle avant activation des contraintes uniques.

## 1. Sauvegarde obligatoire

Effectuer une sauvegarde complète de la base avant toute réparation ou modification de schéma.

## 2. Exécuter les migrations non destructrices

```bash
php artisan migrate --force
```

Les migrations ajoutent :

- la clé générée nécessaire à MySQL pour distinguer les paiements actifs des paiements supprimés logiquement ;
- les colonnes de quarantaine réversible sur les paiements et les livraisons.

Aucun index unique n’est encore activé à cette étape.

## 3. Auditer la base

```bash
php artisan food:audit-integrity --json
```

Le résultat doit être lu intégralement. Les catégories suivantes bloquent l’activation des contraintes :

- paiements dupliqués ;
- livraisons dupliquées ;
- groupes de commandes incohérents ;
- cash marqué payé sans confirmation de collecte ;
- commandes programmées sans date ;
- comptes restaurant orphelins.

## 4. Simuler les réparations automatiques

```bash
php artisan food:repair-integrity
```

La simulation sépare :

- `safe` : doublons techniquement identiques, sans signal de double débit ni activité de livraison ;
- `manual` : groupes nécessitant une vérification humaine.

Un paiement est toujours classé manuel lorsque plusieurs lignes sont payées ou lorsque les références opérateur sont différentes.

## 5. Mettre en quarantaine uniquement les doublons sûrs

```bash
php artisan food:repair-integrity --apply --confirm=APPLY_SAFE_REPAIRS
```

La commande travaille dans une transaction et revérifie chaque groupe sous verrou.

Aucune ligne n’est effacée définitivement :

- le doublon est détaché de la commande ;
- il est relié à la ligne conservée par `integrity_duplicate_of_id` ;
- la date et le motif sont enregistrés ;
- le paiement conserve dans ses métadonnées son ancien `order_id`, son fournisseur et le motif ;
- une livraison mise en quarantaine passe à `CANCELLED`.

La correction peut donc être analysée et reconstruite manuellement si nécessaire.

## 6. Corriger les anomalies manuelles

Ne jamais traiter automatiquement :

- deux paiements `PAID` ;
- deux références Mobile Money ou PayPal différentes ;
- une livraison déjà assignée, prise en charge ou livrée ;
- un groupe de commandes dont le restaurant, le client, le total ou le mode de paiement diffère.

Chaque cas doit être rapproché avec les journaux, l’opérateur de paiement et les preuves de livraison.

## 7. Rejouer l’audit

```bash
php artisan food:audit-integrity --json
```

La valeur attendue est :

```json
{
  "status": "clean",
  "violations_count": 0
}
```

## 8. Simuler l’activation des contraintes

```bash
php artisan food:enforce-integrity-constraints
```

Cette commande affiche le pilote, l’état du schéma, les index déjà présents et le rapport d’audit. Elle ne modifie rien sans l’option `--apply`.

## 9. Activer les contraintes

```bash
php artisan food:enforce-integrity-constraints --apply --confirm=ENFORCE_FOOD_CONSTRAINTS
```

Contraintes activées :

- un seul paiement actif par couple `order_id` et `provider` ;
- une seule livraison par `order_id`.

La commande est idempotente : un nouvel appel confirme les index existants sans les recréer.

## 10. Vérification après activation

```bash
php artisan food:audit-integrity --json
php artisan food:enforce-integrity-constraints
```

Conserver les deux sorties avec le dossier de déploiement.

## Retour arrière

Aucun retour arrière automatique ne supprime les index d’intégrité. Leur retrait doit être une décision technique explicite, documentée et précédée d’une sauvegarde, car il réouvre la possibilité de doubles paiements et doubles livraisons.
