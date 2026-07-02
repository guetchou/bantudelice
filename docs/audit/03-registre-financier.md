# 03 — Registre financier

## État réel

Le registre V2 utilise `financial_accounts`, `financial_posting_batches` et `financial_postings`. `LedgerPostingService` impose au niveau applicatif : au moins deux lignes, montants entiers positifs, directions débit/crédit, équilibre par devise, clé d’idempotence, hash du contenu, transaction SQL et verrouillage ordonné des comptes.

Le miroir d’encaissement est branché sur `PaymentConfirmed`, mais désactivé par défaut. Il crédite un compte d’attente et ne ventile pas encore restaurant, livreur, taxe, remise ou commission. Les flux cash/COD sont exclus.

## Anomalies

### FIN-001 — Critique — Registre non obligatoire

- **Fichiers :** `FinanceMirrorServiceProvider`, `PaymentCollectionMirrorService`, configuration `FINANCIAL_MIRROR_COLLECTIONS_ENABLED`.
- **Fait :** un paiement peut devenir `PAID` alors que le miroir est désactivé ou en échec.
- **Conséquence :** paiement fournisseur confirmé sans écriture comptable V2.
- **Correction :** outbox transactionnelle obligatoire pour toute confirmation ; traitement asynchrone idempotent et alerte bloquante de rapprochement.
- **Tests :** miroir désactivé, panne DB secondaire, reprise après crash.
- **Régression :** élevée.

### FIN-002 — Critique — Soldes partenaires non alimentés par la ventilation réelle

- **Preuve :** `docs/finance/payment-collection-mirror.md` maintient les fonds dans `LIABILITY:PAYMENT:CLEARING`.
- **Fait :** les règles de commission, livraison, pourboire, taxe et remise ne sont pas finalisées.
- **Conséquence :** le solde partenaire du registre ne peut pas être considéré comme complet.
- **Correction :** contrat de distribution versionné par commande, puis backfill contrôlé.
- **Tests :** commande multi-lignes, remise, pourboire, taxe, livraison et annulation.
- **Régression :** très élevée.

### FIN-003 — Critique — Retraits partenaires non raccordés au ledger

- **Fichiers :** `PartnerWithdrawalService`, `ResilientPartnerWithdrawalService`, `PartnerLedgerWorkflowService`.
- **Fait :** les recherches d’usage montrent que `PartnerLedgerWorkflowService` n’est consommé que par ses tests. Le retrait opérationnel calcule le disponible via un dashboard et crée un statut `reserved`, sans preuve d’appel obligatoire au ledger V2.
- **Conséquence :** retrait possible sans réservation comptable immuable correspondante.
- **Correction :** rendre `FinancialLedgerGateway` obligatoire dans le service de retrait : réserve avant appel fournisseur, confirme ou libère après résultat terminal.
- **Tests :** concurrence, double retrait, crash après réserve, succès fournisseur avant réponse, échec et inversion.
- **Régression :** élevée.

### FIN-004 — Haute — Immuabilité seulement Eloquent

- **Fichiers :** `FinancialPosting.php`, `FinancialPostingBatch.php`.
- **Fait :** événements Eloquent bloquent update/delete, mais Query Builder ou SQL direct contournent cette protection.
- **Conséquence :** altération silencieuse possible avec les mêmes privilèges DB que l’application.
- **Correction :** triggers DB ou compte SQL séparé sans UPDATE/DELETE sur les tables validées.
- **Tests :** Eloquent, Query Builder et SQL direct.
- **Régression :** faible.

### FIN-005 — Haute — Contraintes comptables non garanties par la DB

- **Fichier :** migration `create_financial_ledger_v2_tables`.
- **Fait :** pas de CHECK DB sur montant positif ou direction ; équilibre garanti uniquement par le service.
- **Conséquence :** import ou maintenance directe peut créer une écriture invalide.
- **Correction :** CHECK, procédures d’import contrôlées et test d’intégrité périodique.
- **Tests :** insertion directe invalide.
- **Régression :** moyenne.

### FIN-006 — Haute — Deux ledgers spécialisés sans rapprochement défini

- **Fait :** registre V2 général et futur wallet/ledger GePay représentent tous deux des positions financières.
- **Conséquence :** double comptabilisation ou divergences entre trésorerie GePay et comptabilité générale.
- **Correction :** GePay comme sous-ledger opérationnel, avec posting V2 par événement terminal et rapport de rapprochement.
- **Tests :** somme wallets GePay versus comptes de contrôle V2.
- **Régression :** élevée.

## Invariants exécutables

1. Chaque batch validé est équilibré par devise.
2. Aucun batch/posting validé n’est modifiable ou supprimable.
3. Une clé d’idempotence ne peut référencer deux contenus différents.
4. Un paiement fournisseur confirmé produit exactement un lot de collecte.
5. Un retrait produit : réserve, puis paiement ou libération, jamais les deux.
6. Aucun solde disponible partenaire ne devient négatif.
7. Toute annulation passe par une contre-écriture.
8. La somme des sous-ledgers est rapprochée avec les comptes de contrôle V2.

## Conclusion

Le moteur V2 est une bonne base, mais l’entreprise ne doit pas encore utiliser ses soldes comme vérité opérationnelle complète tant que les flux historiques, la ventilation partenaire et les retraits ne sont pas raccordés de manière obligatoire.