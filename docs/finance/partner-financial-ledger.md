# Registre financier partenaires BantuDelice

## Décision

BantuDelice utilise une trésorerie physique mutualisée auprès des opérateurs de paiement, mais chaque restaurant et chaque livreur possède des sous-comptes financiers distincts dans le registre interne.

Un dashboard partenaire ne doit jamais recalculer un « solde disponible » depuis des commandes, livraisons et tables de reversement. Il doit lire la position produite par les écritures du registre.

## Comptes minimaux

### Trésorerie BantuDelice

- `ASSET:MTN:COLLECTIONS` : argent encaissé auprès des clients ;
- `ASSET:MTN:DISBURSEMENT` : argent disponible pour les reversements ;
- `ASSET:CASH:IN_TRANSIT` : espèces détenues temporairement par les livreurs ;
- `ASSET:LEGACY:CONTROL` : compte de contrôle temporaire pour les reprises historiques validées.

### Revenus et obligations BantuDelice

- `REVENUE:BANTUDELICE:COMMISSION` : commissions acquises ;
- `REVENUE:BANTUDELICE:SERVICE_FEE` : frais de service acquis ;
- `EXPENSE:PAYMENT:OPERATOR_FEE` : frais MTN, Airtel, carte ou autre opérateur ;
- `LIABILITY:TAX:PAYABLE` : taxes collectées mais non acquises.

### Sous-comptes de chaque partenaire

Pour chaque restaurant et chaque livreur :

- `...:AVAILABLE` : dette BantuDelice actuellement retirable par le partenaire ;
- `...:RESERVED` : dette déjà réservée par une demande de retrait en cours.

Ces comptes sont individualisés par `owner_type`, `owner_id`, `purpose` et `currency`.

## Exemple : encaissement de 10 000 FCFA

Ventilation contractuelle :

- restaurant : 7 000 ;
- livreur : 1 000 ;
- commission BantuDelice : 1 500 ;
- frais de service BantuDelice : 300 ;
- taxe à reverser : 200.

Écriture :

| Compte | Débit | Crédit |
|---|---:|---:|
| MTN Collections | 10 000 | 0 |
| Dette disponible Restaurant | 0 | 7 000 |
| Dette disponible Livreur | 0 | 1 000 |
| Revenu commission BantuDelice | 0 | 1 500 |
| Revenu frais de service BantuDelice | 0 | 300 |
| Taxe à reverser | 0 | 200 |

Le total des crédits doit être strictement égal au montant encaissé. Sinon l’opération est rejetée.

## Retrait partenaire

### Réservation

Lorsqu’un partenaire demande 2 000 FCFA :

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

La dette envers le partenaire et la trésorerie diminuent simultanément.

### Échec explicite

| Compte | Débit | Crédit |
|---|---:|---:|
| Dette réservée partenaire | 2 000 | 0 |
| Dette disponible partenaire | 0 | 2 000 |

Le partenaire retrouve son disponible. Un statut inconnu ne déclenche pas cette libération.

### Inversion après paiement

Si l’opérateur retourne les fonds après avoir annoncé le paiement :

| Compte | Débit | Crédit |
|---|---:|---:|
| MTN Disbursements | 2 000 | 0 |
| Dette disponible partenaire | 0 | 2 000 |

BantuDelice récupère la trésorerie et redevient débiteur du partenaire.

## Garanties introduites

- écriture équilibrée par devise ;
- montants en entiers FCFA ;
- comptes et lots identifiés de façon unique ;
- clé d’idempotence obligatoire ;
- aucune mise à jour ni suppression d’une écriture validée par les modèles applicatifs ;
- correction par nouvelle écriture, jamais par altération de l’historique ;
- séparation entre argent encaissé, dette partenaire, argent réservé et revenu BantuDelice.

## Déploiement

1. Exécuter les migrations.
2. Simuler le provisionnement :

   ```bash
   php artisan finance:provision-accounts
   ```

3. Créer les comptes :

   ```bash
   php artisan finance:provision-accounts --commit
   ```

4. Produire un état de reprise des positions historiques.
5. Comparer cet état aux soldes MTN Collections, MTN Disbursements, aux reversements et aux espèces en transit.
6. Faire approuver les soldes d’ouverture.
7. Activer `FINANCIAL_LEDGER_WRITE_ENABLED=true` pour les nouveaux mouvements.
8. Contrôler une période parallèle sans utiliser le registre pour les dashboards.
9. Activer `FINANCIAL_LEDGER_READ_PARTNER_BALANCES=true` seulement après rapprochement signé.

## Interdictions

- ne pas créer automatiquement un solde d’ouverture à partir du dashboard historique ;
- ne pas libérer une réservation sur timeout ou statut inconnu ;
- ne pas comptabiliser deux fois les anciens `restaurant_payments`, `driver_payments` et les nouveaux `partner_withdrawals` ;
- ne pas afficher la trésorerie MTN comme un revenu BantuDelice ;
- ne pas confondre la commission calculée avec une commission effectivement acquise et rapprochée.
