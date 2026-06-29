# Modèle opératoire industriel — Paiements BantuDelice

## 1. Principe directeur

Le module Paiements ne doit pas être un simple écran sur la table `payments`. Il doit représenter six réalités distinctes :

1. tentative d’encaissement ;
2. confirmation fournisseur ;
3. affectation à une commande ;
4. obligation envers un partenaire ;
5. réservation puis décaissement ;
6. correction, remboursement, litige ou inversion.

Les équivalences suivantes sont interdites :

- commande = paiement ;
- paiement initié = argent encaissé ;
- argent encaissé = argent disponible ;
- retrait demandé = retrait payé ;
- statut inconnu = échec ;
- correction = suppression de l’écriture initiale.

## 2. Encaissement client

Workflow nominal :

`CREATED → SUBMITTED → PENDING → SUCCESSFUL`

Sorties contrôlées :

- `FAILED` ;
- `EXPIRED` ;
- `CANCELLED` ;
- `UNKNOWN` ;
- `REVERSED` ;
- `REFUNDED` ;
- `DISPUTED`.

Règles :

- seule une confirmation fiable du fournisseur produit un encaissement ;
- chaque tentative conserve une référence et une clé d’idempotence ;
- un callback rejoué ne doit pas créer un second mouvement ;
- `UNKNOWN` interdit tout nouveau débit automatique ;
- une commande peut avoir plusieurs tentatives mais une seule valeur encaissée affectée.

## 3. Affectation

Cible industrielle :

- `payments` : fait fournisseur immuable ;
- `payment_allocations` : affectation d’un paiement à une ou plusieurs obligations ;
- aucune réaffectation ne modifie le paiement original ;
- une correction produit une nouvelle affectation ou une contre-affectation.

Cas à contrôler :

- paiement confirmé sans commande ;
- paiement supérieur au montant dû ;
- double paiement ;
- paiement tardif après expiration ;
- paiement rattaché à une commande déjà réglée ;
- paiement partiellement affecté.

## 4. Obligations partenaires

Les montants restaurants et livreurs sont des dettes de la plateforme, pas des revenus disponibles.

Séparation minimale :

- brut de commande ;
- commission plateforme ;
- frais de livraison ;
- retenues et ajustements ;
- net restaurant ;
- net livreur ;
- somme réservée ;
- somme déjà versée ;
- solde restant dû.

Le calcul d’un solde disponible doit provenir d’un registre financier, jamais d’une simple soustraction de tables opérationnelles.

## 5. Retraits partenaires

Workflow nominal :

`REQUESTED → RESERVED → SUBMITTED → PENDING → PAID`

Sorties contrôlées :

- `REJECTED` ;
- `FAILED` ;
- `UNKNOWN` ;
- `REVERSED` ;
- `CANCELLED`.

Règles :

- la réservation rend immédiatement le montant indisponible ;
- `UNKNOWN` maintient la réservation ;
- un échec ne libère les fonds qu’après confirmation certaine ;
- `PAID` produit un débit définitif ;
- `REVERSED` produit une contre-écriture ;
- une même demande ne peut pas être soumise deux fois.

## 6. Registre financier cible

Table cible : `ledger_entries` ou équivalent.

Types d’écriture :

- `ORDER_CREDIT` ;
- `PLATFORM_COMMISSION` ;
- `DELIVERY_CREDIT` ;
- `PROVIDER_FEE` ;
- `WITHDRAWAL_RESERVATION` ;
- `WITHDRAWAL_PAYMENT` ;
- `WITHDRAWAL_RELEASE` ;
- `REFUND` ;
- `REVERSAL` ;
- `ADJUSTMENT`.

Chaque écriture porte :

- bénéficiaire ;
- montant et devise ;
- sens débit ou crédit ;
- source et référence métier ;
- date effective ;
- auteur ou processus ;
- motif ;
- écriture opposée en cas d’annulation.

Une écriture validée n’est ni supprimée ni modifiée silencieusement.

## 7. Rapprochement

Le rapprochement compare :

1. vérité interne BantuDelice ;
2. transaction GePay ;
3. vérité opérateur MTN ou Airtel.

Résultats :

- `MATCHED` ;
- `MISSING_INTERNAL` ;
- `MISSING_PROVIDER` ;
- `AMOUNT_MISMATCH` ;
- `STATUS_MISMATCH` ;
- `DUPLICATE` ;
- `UNKNOWN` ;
- `REVERSED`.

Une action de rapprochement doit enregistrer la preuve, le résultat, l’auteur, la date et les écarts détectés.

## 8. Remboursements et litiges

Cibles séparées :

- `refunds` ou `payment_refunds` ;
- `payment_disputes` ou équivalent.

Un remboursement possède son propre workflow et ses propres écritures. Un litige possède une responsabilité, des preuves, une décision et une clôture.

## 9. Centre de contrôle

Le tableau de bord industriel doit présenter :

- encaissements confirmés ;
- encaissements non résolus ;
- paiements confirmés non affectés ;
- références fournisseur dupliquées ;
- obligations restaurants et livreurs ;
- fonds partenaires réservés ;
- retraits inconnus ou inversés ;
- file de contrôle priorisée ;
- couverture réelle des contrôles métier.

Il ne doit pas afficher un « disponible » global tant qu’un registre financier immuable n’existe pas.

## 10. Découpage de mise en œuvre

### Lot A — contrôle industriel de lecture

- modèle opératoire canonique ;
- dashboard métier ;
- file de contrôle unifiée ;
- séparation encaissement, non-résolu, non-affecté, réserve et décaissement ;
- affichage explicite des contrôles manquants.

### Lot B — affectations

- table `payment_allocations` ;
- workflow d’affectation et de réaffectation ;
- gestion du trop-perçu ;
- paiements non identifiés.

### Lot C — registre financier

- ledger immuable ;
- écritures automatiques ;
- contre-écritures ;
- soldes partenaires calculés depuis le ledger.

### Lot D — remboursements et litiges

- workflows dédiés ;
- pièces justificatives ;
- double validation selon seuil ;
- reprise sur solde partenaire.

### Lot E — rapprochement comptable

- comparaison interne / GePay / opérateur ;
- journal des écarts ;
- clôture quotidienne ;
- export comptable et preuve de contrôle.
