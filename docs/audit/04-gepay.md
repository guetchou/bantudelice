# 04 — Audit GePay

## État réel

GePay dispose d’une API client signée, d’un nonce anti-rejeu, d’un gateway idempotent et d’un provider MTN. Les transactions sont suivies avec des statuts non terminaux et terminaux, puis rafraîchies par polling/réconciliation. L’intégration des retraits BantuDelice utilise un client interne.

Le portail marchand prévu par ADR-001 n’est pas encore en production. La PR #102 ajoute son schéma, mais elle est ouverte et ses jobs `GePay Quality` SQLite/MySQL ainsi que `BantuDelice CI` sont rouges au SHA `faae109` pendant cet audit.

## Anomalies

### GEPAY-001 — Haute — Découplage incomplet

- **Fichiers :** `GePayAdapter`, `PartnerWithdrawalService`, `GePayInternalClientResolver`, contrôleur admin GePay.
- **Fait :** GePay est réutilisé par les paiements et retraits historiques avec un client interne ; le portail futur introduit un modèle marchand distinct.
- **Conséquence :** confusion possible entre transaction interne BantuDelice, transaction API cliente et transaction portail.
- **Correction :** source de client explicite par canal, `merchant_id` dérivé serveur et invariant de rattachement.
- **Tests :** client interne, client API et client portail séparés.
- **Régression :** élevée.

### GEPAY-002 — Haute — Isolation marchande non encore livrée

- **Fait :** le guard marchand, middleware et routes du portail ne sont pas sur `main` ; le contrôleur admin historique liste des transactions globales.
- **Conséquence :** le portail ne doit pas réutiliser les contrôleurs admin.
- **Correction :** poursuivre les PR prévues après merge vert de #102, avec scope provenant uniquement du guard.
- **Tests :** marchand A contre toutes les ressources B.
- **Régression :** moyenne.

### GEPAY-003 — Haute — Ledger GePay distinct du registre V2

- **Fait :** PR #102 crée wallets et ledger GePay sans posting V2 correspondant dans ce lot.
- **Conséquence :** position marchand GePay et comptabilité générale peuvent diverger.
- **Correction :** compte de contrôle GePay dans Finance V2 et événements de rapprochement idempotents.
- **Tests :** encaissement, décaissement, payout, échec, inversion.
- **Régression :** élevée.

### GEPAY-004 — Moyenne — Support fournisseur limité

- **Fichier :** `GePayGateway` enregistre uniquement `MtnMomoProvider`.
- **Fait :** Airtel n’est pas un provider GePay actif malgré sa présence dans le domaine Payment historique.
- **Conséquence :** toute interface GePay annonçant Airtel serait trompeuse.
- **Correction :** conserver MTN-only jusqu’à provider Airtel, sandbox, callbacks et réconciliation testés.
- **Tests :** contrat provider commun.
- **Régression :** faible.

### GEPAY-005 — Haute — Lots, CSV et limite 500 absents

- **Recherche code :** aucune implémentation GePay de lot, import CSV ou suivi ligne par ligne trouvée.
- **Conséquence :** les exigences de décaissement en lot et reprise partielle ne sont pas couvertes.
- **Correction :** ne pas exposer cette fonction en MVP. Concevoir `batch`, `batch_items`, validation CSV, déduplication et états indépendants.
- **Tests :** doublons, 501 lignes, ligne invalide, succès partiel, reprise ciblée.
- **Régression :** nouvelle fonction.

### GEPAY-006 — Haute — Réconciliation retrait non comptable

- **Fichiers :** `GePayWithdrawalReconciler`, `PartnerWithdrawalService`.
- **Fait :** le statut opérationnel est réconcilié, mais le raccord obligatoire à `WithdrawalLedgerService` n’est pas visible.
- **Conséquence :** retrait payé/échoué sans mouvement comptable V2 correspondant.
- **Correction :** appliquer la transition fournisseur et la transition ledger dans une orchestration idempotente.
- **Tests :** `unknown`, `reversed`, paiement confirmé et échec terminal.
- **Régression :** élevée.

### GEPAY-007 — Haute — PR #102 non fusionnable opérationnellement

- **Fait :** le SHA `faae109` est mergeable au sens Git, mais les suites principales échouent. Les logs disponibles confirment l’échec à l’étape tests, sans sortie finale complète accessible via le connecteur.
- **Correction :** aucune fusion avant toutes CI vertes et revue humaine.
- **Tests :** SQLite, MySQL 8, rollback et triggers d’immuabilité.
- **Régression :** nulle.

## Contrôles sécurité requis pour le portail

- Guard et cookie distincts, host-only, Secure, HttpOnly, SameSite strict.
- CSRF sur toute écriture web.
- `merchant_id` jamais accepté depuis le navigateur.
- Téléphones et destinations chiffrés et masqués.
- `operation_token` durable et lié au marchand/utilisateur/type/hash.
- Autorisations admin/viewer côté serveur.
- Audit de toute vérification de destination et transition payout.

## Conclusion

Le noyau API GePay est avancé, mais le portail et son ledger ne sont pas encore exploitables. Les fonctions de lot demandées n’existent pas. GePay doit rester MTN-only et non public jusqu’à merge vert des PR, isolation marchande et rapprochement avec Finance V2.