# Audit de préparation du miroir des encaissements

## Objectif

Contrôler les paiements historiques `PAID` avant toute activation de `FINANCIAL_MIRROR_COLLECTIONS_ENABLED`.

La commande est strictement en lecture. Elle ne crée ni compte, ni écriture, ni événement miroir.

## Commandes

Audit complet :

```bash
php artisan finance:audit-payment-collection-readiness
```

Filtre sur la valeur fournisseur stockée :

```bash
php artisan finance:audit-payment-collection-readiness --provider=momo
```

Sortie JSON :

```bash
php artisan finance:audit-payment-collection-readiness --json
```

Blocage d’un pipeline lorsqu’une anomalie existe :

```bash
php artisan finance:audit-payment-collection-readiness --fail-on-blockers
```

## Indicateurs

L’audit restitue :

- nombre et montant total des paiements `PAID` ;
- paiements en ligne reconnus ;
- paiements espèces exclus ;
- paiements non classifiables ;
- paiements éligibles au miroir ;
- paiements déjà inscrits et vérifiés dans le registre ;
- paiements éligibles mais non encore miroités ;
- paiements bloqués ;
- groupes de références fournisseur dupliquées ;
- répartition par route de rapprochement ;
- statuts des événements miroir.

## Vérification d’un événement `posted`

Un événement miroir marqué `posted` n’est considéré comme valide que si :

- son UUID de lot est renseigné ;
- le lot existe dans `financial_posting_batches` ;
- le type d’événement est `payment_collection_received` ;
- la source est le paiement concerné ;
- la clé d’idempotence correspond au paiement ;
- le lot est au statut `posted` ;
- il contient exactement une ligne de débit et une ligne de crédit ;
- les deux lignes portent le montant exact du paiement ;
- les deux lignes sont en XAF ;
- le débit vise un compte `ASSET:PAYMENT_PROVIDER:*:COLLECTIONS` ;
- le crédit vise `LIABILITY:PAYMENT:CLEARING`.

Le statut du journal miroir ne peut donc pas masquer un lot absent, un mauvais montant ou une mauvaise affectation comptable.

## Anomalies bloquantes

- fournisseur absent ;
- fournisseur non supporté ;
- référence fournisseur absente pour un paiement en ligne ;
- devise différente de XAF ;
- montant nul, négatif ou non entier ;
- événement miroir en échec ;
- événement `posted` sans lot financier ;
- événement `posted` associé à un lot incohérent ;
- même référence fournisseur portée par plusieurs paiements du même canal canonique.

Les alias `momo`, `mtn` et `mtn_momo` sont comparés comme un même canal. Un doublon réparti entre ces alias reste donc détecté.

## Routes

Le résolveur commun au miroir et à l’audit classe les paiements ainsi :

- `mtn_momo` : MTN direct ;
- `gepay_mtn` : MTN traité par GePay ;
- `airtel_money` ;
- `paypal` ;
- `cash` : espèces exclues du miroir en ligne.

Le miroir et l’audit ne possèdent plus deux listes de fournisseurs séparées.

## Interprétation

`ready_for_activation = YES` signifie uniquement qu’aucune anomalie historique bloquante n’a été détectée pour le périmètre demandé.

Cela ne vaut pas autorisation de production. L’activation exige encore :

1. sauvegarde et test de restauration ;
2. migrations exécutées ;
3. validation des comptes de rapprochement ;
4. contrôle du solde fournisseur réel ;
5. période d’observation ;
6. procédure de reprise des événements en échec ;
7. validation Finance et Exploitation.

## Limite

Cet audit porte uniquement sur l’encaissement brut.

La ventilation vers restaurants, livreurs, taxes et revenus BantuDelice reste dépendante de l’issue #87.
