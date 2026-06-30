# Miroir des encaissements vers le registre financier

## Objectif

Enregistrer un paiement fournisseur confirmé dans le registre financier sans modifier le workflow client, sans attribuer prématurément l’argent à un restaurant ou à un livreur et sans remplacer les calculs historiques.

## Principe comptable

Lorsqu’un paiement en ligne est confirmé :

| Compte | Débit | Crédit |
|---|---:|---:|
| Encaissements du fournisseur concerné | Montant encaissé | 0 |
| Fonds clients à affecter | 0 | Montant encaissé |

Le premier compte représente l’actif à rapprocher avec le sous-système qui a réellement traité l’encaissement.

Le second représente une obligation temporaire : l’argent a été encaissé, mais sa ventilation métier n’est pas encore validée.

## Comptes fournisseurs

Les alias d’un même opérateur sont normalisés :

- `momo`, `mtn` et `mtn_momo` utilisent `ASSET:PAYMENT_PROVIDER:MTN_MOMO:COLLECTIONS` ;
- `airtel` et `airtel_money` utilisent `ASSET:PAYMENT_PROVIDER:AIRTEL_MONEY:COLLECTIONS` ;
- `paypal` utilise son propre compte ;
- un paiement MTN portant une référence GePay utilise `ASSET:PAYMENT_PROVIDER:GEPAY_MTN:COLLECTIONS`.

Cette séparation permet de rapprocher les transactions directes MTN des transactions passées par le sous-système GePay.

Un fournisseur inconnu provoque un événement miroir en échec. Aucun compte de trésorerie n’est créé automatiquement à partir d’une valeur non reconnue.

## Pourquoi aucun partenaire n’est encore crédité

Au moment du paiement :

- le livreur peut ne pas être affecté ;
- le total peut contenir livraison, taxe, frais de service, pourboire et remise ;
- plusieurs sources de commission existent encore dans le code historique ;
- une remise doit avoir une source de financement clairement définie ;
- une commande peut contenir plusieurs lignes partageant le même total.

Le miroir refuse donc de fabriquer une ventilation approximative.

## Déclenchement

`PaymentService` continue à déclencher l’événement existant `PaymentConfirmed`.

Un provider financier isolé enregistre un listener supplémentaire. Le listener :

1. attend la validation de la transaction principale ;
2. recharge le paiement ;
3. identifie le véritable canal de rapprochement ;
4. exécute le miroir ;
5. capture toute erreur ;
6. ne modifie jamais le statut du paiement.

Une panne du miroir ne doit pas annuler un paiement fournisseur déjà confirmé.

## Activation

Le miroir est désactivé par défaut :

```env
FINANCIAL_MIRROR_COLLECTIONS_ENABLED=false
```

L’activation ne doit intervenir qu’après :

- exécution des migrations ;
- vérification des soldes d’ouverture ;
- validation des comptes MTN direct et GePay–MTN ;
- contrôle d’un environnement de préproduction ;
- définition du processus de rapprochement quotidien.

## Idempotence

Chaque paiement utilise la clé :

```text
payment:{payment_id}:collection-received:v1
```

Un second événement pour le même paiement réutilise le lot existant au lieu de créer un nouvel encaissement.

## Journal miroir

La table `financial_mirror_events` conserve :

- la clé métier ;
- le type d’événement ;
- l’identifiant du paiement ;
- le statut ;
- le nombre de tentatives ;
- l’UUID du lot financier ;
- la dernière erreur ;
- les dates de traitement.

Le snapshot exclut les données brutes de callback et les informations sensibles inutiles.

## Statuts

- `pending` : événement créé ;
- `processing` : tentative en cours ;
- `posted` : écriture comptable créée ou réutilisée ;
- `failed` : erreur enregistrée, paiement inchangé ;
- `skipped` : opération volontairement non traitée par ce flux.

## Espèces

Les paiements `cash`, `cod` et `demo` sont ignorés par ce miroir.

Ils nécessitent un workflow distinct :

- collecte par le livreur ;
- preuve de remise ;
- espèces en transit ;
- versement ou compensation ;
- rapprochement de caisse.

## Reprise contrôlée

Reprise ciblée :

```bash
php artisan finance:retry-payment-collection-mirrors --payment-id=123
```

Reprise d’un lot d’échecs :

```bash
php artisan finance:retry-payment-collection-mirrors --limit=100
```

La commande ne rejoue pas le callback fournisseur. Elle reprend seulement l’écriture miroir locale.

## Étape suivante

La ventilation de `LIABILITY:PAYMENT:CLEARING` devra être traitée dans une PR séparée après résolution des règles suivantes :

- commission contractuelle restaurant ;
- frais de livraison et affectation du livreur ;
- traitement du pourboire ;
- taxe à reverser ;
- frais de service BantuDelice ;
- financement des remises ;
- commandes multi-lignes ;
- remboursements et inversions.

Tant que ces règles ne sont pas stabilisées, les fonds restent dans le compte d’attente et aucun solde partenaire n’est augmenté.
