# Contrat d’intégration du registre financier

## Objectif

Permettre au domaine Paiements, aux commandes et aux retraits d’utiliser le registre financier sans dépendre directement de ses tables ou de ses modèles internes.

## Principe

Les modules consommateurs dépendent de l’interface :

```php
App\Domain\Finance\Contracts\FinancialLedgerGateway
```

L’implémentation actuelle est :

```php
App\Domain\Finance\Adapters\PartnerLedgerV2Gateway
```

Cette séparation permet de remplacer ou d’adapter le moteur financier sans réécrire les workflows Paiements.

## Opérations exposées

- enregistrer la ventilation d’un encaissement ;
- libérer le gain d’un restaurant ou d’un livreur ;
- reconnaître séparément le revenu BantuDelice ;
- réserver un retrait ;
- confirmer un retrait ;
- libérer un retrait après échec explicite ;
- lire la position financière d’un partenaire.

## Ce que cette PR ne fait pas

- aucune modification de `PaymentService` ;
- aucune modification de `PartnerWithdrawalService` ;
- aucune modification d’un provider existant ;
- aucune activation automatique ;
- aucune bascule de dashboard ;
- aucune écriture historique.

## Règle d’intégration avec les autres PR

Une future PR d’adaptation du domaine Paiements devra appeler le contrat, et non les modèles ou tables du registre.

Elle sera créée sur une branche distincte après arbitrage des PR concurrentes. Les branches existantes ne seront ni réécrites ni force-pushées.

## Liaison au conteneur

Aucun binding global n’est ajouté dans ce lot afin de ne pas modifier `AppServiceProvider`, déjà touché par d’autres travaux.

Le binding sera introduit dans une PR d’intégration dédiée, après sélection officielle du moteur :

```php
FinancialLedgerGateway::class => PartnerLedgerV2Gateway::class
```

## Idempotence

Le contrat retourne un `PostingReceipt` contenant :

- l’UUID du lot financier ;
- l’indication qu’une écriture existante a été réutilisée.

Les consommateurs peuvent donc rejouer une opération technique sans fabriquer un second mouvement financier.
