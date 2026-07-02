# 10 — Plan de correction priorisé

## Recommandation

**Verdict global : NON EXPLOITABLE pour des flux financiers réels multi-service en production.**

L’application peut servir de démonstrateur fonctionnel ou fonctionner sur un périmètre restreint sans argent réel, mais pas comme plateforme financière unifiée tant que les P0/P1 ci-dessous restent ouverts.

## Principes d’exécution

- Une branche et une PR par lot.
- Test reproduisant le défaut avant correction.
- Correction minimale, réversible et compatible avec les routes historiques.
- Tests ciblés SQLite + MySQL, puis suite complète.
- Aucun statut financier terminal sur simple exception réseau.
- Aucune fusion avec CI rouge ou sans revue humaine.

## Ordre des PR

### PR-A — Urgence identité et routes historiques — Critique

1. Désactiver l’ancien `forgot_password` sans OTP.
2. Forcer `type=user` à l’inscription publique.
3. Protéger profil, adresses, paniers et commandes par auth/policy.
4. Ajouter inventaire CI des routes sensibles sans middleware.

**Gate :** tests anonymes, IDOR A/B, élévation de rôle, reset sans OTP.

### PR-B — Idempotence centrale des paiements — Critique

1. Verrouiller `Payment` pendant toute transition terminale.
2. Émettre `PaymentConfirmed` une seule fois après commit.
3. Rendre l’idempotency key obligatoire dans les créations.
4. Préparer unicité `(provider,provider_reference)` après audit des données.

**Gate :** concurrence MySQL, double callback, callback+poll simultanés.

### PR-C — Statuts incertains et reprise fournisseur — Critique

1. Interdire `FAILED` après exception technique seule.
2. Introduire `UNKNOWN/REVIEW` ou état équivalent.
3. Unifier callback, polling et réconciliation.
4. Ajouter alerte sur paiement incertain trop ancien.

**Gate :** timeout après succès fournisseur, callback perdu, provider indisponible.

### PR-D — Flux cash/COD — Critique

1. Créer une preuve de collecte commune.
2. Retirer l’équivalence automatique livré/terminé = payé.
3. Sécuriser réconciliation COD Colis : périmètre coursier, somme serveur, locks et idempotence.
4. Poster cash en transit puis rapprochement.

**Gate :** livraison sans cash, double collecte, colis d’un autre coursier, montant divergent.

### PR-E — Raccord obligatoire au registre V2 — Critique

1. Outbox de confirmation de paiement.
2. Posting collecte exactement une fois.
3. Rendre le ledger de retrait obligatoire : réserve, confirme ou libère.
4. Définir la distribution partenaire versionnée.

**Gate :** équilibre débit/crédit, crash/reprise, double événement, solde négatif impossible.

### PR-F — Immuabilité et intégrité DB — Haute

1. Bloquer UPDATE/DELETE sur postings et batches validés au niveau DB.
2. CHECK montants/directions lorsque supportés.
3. Commande d’audit de tous les batches et soldes.

**Gate :** Eloquent, Query Builder et SQL direct.

### PR-G — Outbox et queues — Haute

1. Outbox paiements/finance/workflows critiques.
2. Jobs par ID, payload minimal chiffré.
3. Tries/backoff/timeouts explicites.
4. Failed jobs et paiements incertains monitorés.

**Gate :** crash entre commit et dispatch, retry, entité absente.

### PR-H — Horizon et production — Haute

1. Métriques Redis/Horizon réelles.
2. Readiness vérifiant toutes les connexions modulaires.
3. Déploiement échoue sans backup vérifié ou consommateur de file.
4. Smoke tests post-déploiement.
5. Test périodique de restauration.

**Gate :** Redis/Horizon/MySQL indisponibles et rollback N/N-1.

### PR-I — GePay portail — après merge de #102

1. Ne merger #102 qu’avec toutes CI vertes et revue humaine.
2. Guard/session/isolation marchande.
3. Services wallet/ledger idempotents.
4. Rapprochement GePay vers Finance V2.
5. MTN uniquement ; Airtel et lots différés.

**Gate :** isolation A/B, concurrence wallet, payout inconnu, rapprochement.

### PR-J — Dette et interfaces — après stabilisation

- Versionner les routes historiques.
- Réduire services dupliqués.
- Normaliser statuts/enums.
- Renommer connexions queues.
- Déprécier les anciennes couches avec télémétrie.

## Situation PR #102

Au SHA `faae109` : PR ouverte, mergeable Git, non draft. Les workflows Driver, Financial Core Conflict Guard, Frontend et Realtime sont verts. `GePay Quality` échoue sur ses jobs SQLite et MySQL à l’étape tests ; `BantuDelice CI` échoue aussi à l’étape tests. La PR ne doit pas être fusionnée et la PR guard ne doit pas commencer depuis cette branche.

## Commandes et tests de cet audit

### Commandes exécutées

Aucune commande Laravel, migration ou test n’a été exécuté sur une copie locale, faute d’accès réseau du runtime d’analyse au dépôt. Une tentative de lecture locale a échoué :

```bash
git clone --depth 1 --branch feat/gepay-portal-schema https://github.com/guetchou/bantudelice.git /tmp/bantudelice-audit
# échec : résolution DNS de github.com indisponible dans le runtime
```

L’audit repose sur les fichiers et résultats GitHub lus via le connecteur.

### Tests observés, non exécutés par l’auditeur

- PR #102 : quatre workflows verts.
- PR #102 : GePay Quality rouge, jobs SQLite et MySQL rouges à l’étape tests.
- PR #102 : BantuDelice CI rouge à l’étape tests.
- La sortie finale détaillée des échecs n’était pas entièrement accessible dans la réponse tronquée du connecteur.

## Éléments non vérifiables

- contenu réel de la base de production ;
- doublons existants de références fournisseur ;
- configuration `.env` et secrets ;
- état réel systemd, supervisor, Redis, Horizon et scheduler ;
- sauvegardes et capacité de restauration ;
- contrats MTN/Airtel et comportements production ;
- volumétrie, latence p95 et charge concurrente ;
- consommateurs actifs des anciennes routes mobiles.

## Gates pour changer le verdict

Le verdict peut devenir **exploitable sous conditions** lorsque PR-A à PR-H sont fusionnées, que les invariants financiers passent sur MySQL et qu’une restauration a été démontrée. Il devient **exploitable** seulement après rapprochement réel, supervision, revue sécurité externe et pilote financier plafonné.