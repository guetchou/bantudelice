# 09 — Dette technique

## Dette structurante

### DEBT-001 — Haute — Responsabilités trop larges

- **Exemples :** `PaymentService` gère gateway, callbacks, statuts, événements et compatibilité de création de commande ; `TransportService` gère tarification, workflow, paiement, notifications et finance cash.
- **Conséquence :** correction locale à fort rayon de régression.
- **Action :** extraire orchestrateurs et contrats sans supprimer les façades historiques avant inventaire de leurs consommateurs.

### DEBT-002 — Haute — Statuts textuels multiples

- **Exemples :** `PENDING/PAID/FAILED`, `pending/paid`, statuts legacy Food, statuts métier et statuts techniques.
- **Conséquence :** comparaisons sensibles à la casse et transitions incohérentes.
- **Action :** enums par bounded context, normalisation aux frontières et migration progressive.

### DEBT-003 — Haute — Routes historiques non versionnées

- **Fichier :** `routes/api.php`.
- **Conséquence :** impossible de renforcer authentification/contrats sans casser d’anciens clients.
- **Action :** inventorier télémétrie, figer `/legacy`, introduire `/v2`, politique de dépréciation.

### DEBT-004 — Moyenne — Noms de connexions trompeurs

- **Exemple :** `database_food` utilise Redis hors tests.
- **Conséquence :** erreurs d’exploitation et supervision incorrecte.
- **Action :** nouvelles connexions `redis_food/colis/transport`, alias temporaires et migration de configuration.

### DEBT-005 — Haute — Documentation décalée du code

- **Exemples :** contrat financier indiquant qu’aucun binding global n’existe alors que `FinanceMirrorServiceProvider` le réalise ; documents Horizon parlant de workers legacy sans matrice actuelle.
- **Action :** chaque décision d’architecture doit référencer un test d’architecture et un owner.

### DEBT-006 — Haute — Services instanciés directement

- **Exemple :** `RetryPaymentCallbackJob` utilise `new PaymentService()`.
- **Conséquence :** contournement du conteneur et de ses bindings.
- **Action :** injection obligatoire et test interdisant `new` sur services critiques.

### DEBT-007 — Moyenne — Données JSON utilisées comme index métier

- **Exemples :** références Bridge dans `payments.meta`, callbacks et métadonnées diverses.
- **Conséquence :** absence de contraintes, requêtes lentes et ambiguïtés.
- **Action :** colonnes normalisées pour identifiants, statuts et clés de rapprochement.

### DEBT-008 — Haute — Tests SQLite dominants pour règles MySQL

- **Conséquence :** contraintes, locks et concurrence réels non vérifiés.
- **Action :** job MySQL obligatoire pour paiements, finance, GePay et migrations.

### DEBT-009 — Moyenne — Contrôleurs historiques avec validation et métier mêlés

- **Exemples :** `api/UserController`, contrôleurs commandes/panier.
- **Action :** Form Requests, policies et services ; conserver adapters de compatibilité jusqu’à extinction des consommateurs.

### DEBT-010 — Haute — Preuves financières absentes des workflows cash

- **Exemples :** livraison Food, trajet Transport, COD Colis.
- **Action :** objet commun `CashCollection`, preuve, acteur, montant, état, rapprochement et posting.

### DEBT-011 — Moyenne — Observabilité fragmentée

- **Exemples :** logs applicatifs, `financial_events`, mirror events, GePay webhook events, audit logs.
- **Action :** correlation ID unique par opération et tableau de bord de parcours bout en bout.

### DEBT-012 — Moyenne — Feature flags sans registre central

- **Exemples :** GePay collections/retraits, miroir financier, modules.
- **Action :** catalogue documenté : propriétaire, défaut, prérequis, métrique et plan de retrait.

## Doublons à arbitrer

| Besoin | Implémentations |
|---|---|
| Checkout | historique + `WorkflowCheckoutService` |
| Acceptation Food | base + workflow |
| Machine Food | base + workflow |
| Journal financier | `financial_events` + Finance V2 + futur ledger GePay |
| Paiement mobile | adapters directs + Bridge + GePay |
| Workers | Horizon + workers legacy |
| Position partenaire | dashboards historiques + comptes Finance V2 |

## Plan de réduction

1. Corriger sécurité et argent avant refactoring.
2. Ajouter tests d’architecture et de contrats.
3. Introduire des façades stables autour des couches historiques.
4. Mesurer les consommateurs des anciennes routes/classes.
5. Migrer un domaine à la fois.
6. Supprimer seulement après télémétrie nulle et période de dépréciation.

## Critères de dette remboursée

- un seul chemin officiel par opération critique ;
- aucune mise à jour financière directe depuis contrôleur ;
- statuts typés et transitions auditées ;
- tests MySQL de concurrence ;
- documentation liée au code et CI ;
- routes historiques protégées ou retirées.