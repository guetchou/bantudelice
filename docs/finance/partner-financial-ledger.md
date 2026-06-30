# Registre financier partenaires BantuDelice

## Décision

BantuDelice utilise une trésorerie physique mutualisée auprès des opérateurs de paiement. Chaque restaurant et chaque livreur possède néanmoins ses propres sous-comptes dans le registre interne.

Le dashboard partenaire ne doit pas recalculer un solde depuis les commandes et les tables de reversement. Il doit lire les écritures du registre.

## Vérités métier

- paiement initié ≠ argent encaissé ;
- argent encaissé ≠ argent disponible pour le partenaire ;
- commission calculée ≠ revenu BantuDelice déjà acquis ;
- retrait demandé ≠ argent payé ;
- statut inconnu ≠ échec ;
- correction ≠ suppression.

## Comptes de trésorerie

- `ASSET:MTN:COLLECTIONS` : argent confirmé sur le canal d’encaissement ;
- `ASSET:MTN:DISBURSEMENT` : argent effectivement disponible pour les reversements ;
- `ASSET:CASH:IN_TRANSIT` : espèces temporairement détenues par des livreurs ;
- `ASSET:LEGACY:CONTROL` : compte de contrôle réservé aux reprises historiques approuvées.

## Comptes BantuDelice

- `LIABILITY:BANTUDELICE:COMMISSION:DEFERRED` : commission calculée mais non encore acquise ;
- `LIABILITY:BANTUDELICE:SERVICE_FEE:DEFERRED` : frais de service non encore acquis ;
- `REVENUE:BANTUDELICE:COMMISSION` : commission devenue acquise ;
- `REVENUE:BANTUDELICE:SERVICE_FEE` : frais de service devenus acquis ;
- `EXPENSE:PAYMENT:OPERATOR_FEE` : frais des opérateurs ;
- `LIABILITY:TAX:PAYABLE` : taxes collectées à reverser.

## Trois sous-comptes par partenaire

Pour chaque restaurant et chaque livreur :

- `...:PENDING` : montant encaissé mais non encore libéré ;
- `...:AVAILABLE` : montant réellement retirable ;
- `...:RESERVED` : montant bloqué par un retrait en cours.

Un paiement confirmé crédite d’abord `PENDING`. Le passage à `AVAILABLE` nécessite un événement métier explicite, par exemple livraison terminée, délai de contestation expiré ou clôture validée.

## Exemple d’encaissement : 10 000 FCFA

Ventilation :

- restaurant : 7 000 ;
- livreur : 1 000 ;
- commission BantuDelice différée : 1 500 ;
- frais de service différés : 300 ;
- taxe à reverser : 200.

| Compte | Débit | Crédit |
|---|---:|---:|
| MTN Collections | 10 000 | 0 |
| Restaurant en attente | 0 | 7 000 |
| Livreur en attente | 0 | 1 000 |
| Commission BantuDelice différée | 0 | 1 500 |
| Frais de service différés | 0 | 300 |
| Taxe à reverser | 0 | 200 |

Le total des crédits doit être exactement égal au montant encaissé.

## Libération du partenaire

Après exécution du service :

| Compte | Débit | Crédit |
|---|---:|---:|
| Dette partenaire en attente | 7 000 | 0 |
| Dette partenaire disponible | 0 | 7 000 |

Le partenaire peut ensuite demander un retrait dans la limite de `AVAILABLE`.

## Reconnaissance du revenu BantuDelice

Lorsque les conditions contractuelles sont remplies :

| Compte | Débit | Crédit |
|---|---:|---:|
| Commission différée | 1 500 | 0 |
| Revenu de commission | 0 | 1 500 |
| Frais de service différés | 300 | 0 |
| Revenu de frais de service | 0 | 300 |

Le déclencheur exact doit être défini par contrat : livraison confirmée, commande clôturée ou autre événement approuvé.

## Retrait partenaire

### Réservation

| Compte | Débit | Crédit |
|---|---:|---:|
| Dette disponible partenaire | 2 000 | 0 |
| Dette réservée partenaire | 0 | 2 000 |

Aucun argent ne quitte encore BantuDelice.

### Paiement confirmé

| Compte | Débit | Crédit |
|---|---:|---:|
| Dette réservée partenaire | 2 000 | 0 |
| MTN Disbursements | 0 | 2 000 |

### Échec explicite

| Compte | Débit | Crédit |
|---|---:|---:|
| Dette réservée partenaire | 2 000 | 0 |
| Dette disponible partenaire | 0 | 2 000 |

Un timeout ou un statut inconnu ne libère pas la réservation.

### Inversion après paiement

| Compte | Débit | Crédit |
|---|---:|---:|
| MTN Disbursements | 2 000 | 0 |
| Dette disponible partenaire | 0 | 2 000 |

## Garanties

- équilibre obligatoire par devise ;
- montants en entiers FCFA ;
- clé d’idempotence obligatoire ;
- empreinte du contenu financier associé à chaque clé ;
- refus d’une même clé avec un montant ou une ventilation différente ;
- écritures et lots non modifiables par les modèles applicatifs ;
- clés étrangères restrictives, sans suppression en cascade ;
- correction par contre-mouvement ;
- séparation entre trésorerie, dette partenaire et revenu BantuDelice.

## Déploiement

1. Exécuter la migration.
2. Simuler le provisionnement :

   ```bash
   php artisan finance:provision-accounts
   ```

3. Créer les comptes :

   ```bash
   php artisan finance:provision-accounts --commit
   ```

4. Auditer les positions historiques.
5. Rapprocher MTN Collections, MTN Disbursements, espèces et anciens reversements.
6. Faire approuver les écritures d’ouverture.
7. Activer la double écriture avec `FINANCIAL_LEDGER_WRITE_ENABLED=true`.
8. Comparer le registre et les calculs historiques pendant une période contrôlée.
9. Activer `FINANCIAL_LEDGER_READ_PARTNER_BALANCES=true` après rapprochement signé.

## Interdictions

- ne pas créer automatiquement un solde d’ouverture depuis les cartes actuelles ;
- ne pas rendre un encaissement immédiatement retirable ;
- ne pas reconnaître automatiquement une commission comme acquise sans événement métier ;
- ne pas libérer une réservation sur un statut inconnu ;
- ne pas comptabiliser deux fois les anciens et nouveaux circuits de reversement ;
- ne pas présenter le solde MTN comme un revenu BantuDelice.
