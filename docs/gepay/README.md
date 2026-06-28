# GePay Gateway

GePay est la passerelle de paiement réutilisable de l’écosystème. Elle expose une API uniforme pour MTN MoMo Collection et Disbursement, avec authentification HMAC, idempotence, journal des transactions, webhooks et rapprochement.

## API v1

- `GET /api/gepay/v1/health`
- `GET /api/gepay/v1/client`
- `POST /api/gepay/v1/collections`
- `POST /api/gepay/v1/disbursements`
- `GET /api/gepay/v1/transactions/{reference}`
- `POST /api/gepay/v1/transactions/{reference}/refresh`
- `POST /api/gepay/v1/webhooks/mtn`

## Authentification cliente

En production, chaque requête privée contient :

```text
X-GePay-Key: gpk_...
X-GePay-Timestamp: 1710000000
X-GePay-Nonce: identifiant-unique-de-la-requete
X-GePay-Signature: HMAC_SHA256(...)
Idempotency-Key: reference-unique-du-client
```

`Idempotency-Key` est obligatoire sur les créations de transactions. Il peut rester vide sur les lectures.

Chaîne canonique :

```text
{timestamp}\n{nonce}\n{idempotency_key}\n{METHOD}\n{request_uri}\n{sha256(raw_body)}
```

Le nonce :

- est obligatoire hors environnement de test ;
- est inclus dans la signature ;
- ne peut être utilisé qu’une fois pendant la fenêtre de signature ;
- n’est enregistré qu’après validation cryptographique réussie.

Créer un client :

```bash
php artisan gepay:client-create "BantuDelice" \
  --capability=collection \
  --capability=disbursement
```

## Configuration GePay

```env
GEPAY_ENABLED=true
GEPAY_REQUIRE_NONCE=true
GEPAY_SIGNATURE_TOLERANCE_SECONDS=300
GEPAY_SUBMISSION_CLAIM_TIMEOUT_SECONDS=120
GEPAY_INTERNAL_CLIENT_UUID=

GEPAY_BANTUDELICE_COLLECTIONS_ENABLED=false
GEPAY_BANTUDELICE_WITHDRAWALS_ENABLED=false
```

`GEPAY_SUBMISSION_CLAIM_TIMEOUT_SECONDS` définit le délai minimal avant qu’un processus puisse reprendre une transaction restée `submitted` sans référence fournisseur après un crash.

## Configuration MTN

```env
GEPAY_MTN_ENABLED=true
GEPAY_MTN_ENVIRONMENT=production
GEPAY_MTN_TARGET_ENVIRONMENT=mtncongo
GEPAY_MTN_CALLBACK_URL=https://pay.example.cg/api/gepay/v1/webhooks/mtn

GEPAY_MTN_COLLECTIONS_SUBSCRIPTION_KEY=
GEPAY_MTN_COLLECTIONS_API_USER=
GEPAY_MTN_COLLECTIONS_API_KEY=

GEPAY_MTN_DISBURSEMENTS_SUBSCRIPTION_KEY=
GEPAY_MTN_DISBURSEMENTS_API_USER=
GEPAY_MTN_DISBURSEMENTS_API_KEY=
```

`X-Target-Environment` est envoyé pendant la création des tokens et pendant les opérations MTN.

## Exemple Collection

```json
{
  "amount": 5000,
  "currency": "XAF",
  "phone": "24206XXXXXXX",
  "provider": "mtn_momo",
  "external_reference": "ORDER-10045",
  "payer_message": "Commande 10045",
  "payee_note": "Paiement commande"
}
```

## Garanties de soumission

Avant tout appel Collection ou Disbursement, GePay :

1. crée ou retrouve la transaction par clé d’idempotence ;
2. revendique atomiquement le droit de soumettre ;
3. obtient le token MTN ;
4. enregistre la référence MTN en base ;
5. appelle ensuite l’API métier MTN.

Deux requêtes utilisant la même clé d’idempotence ne peuvent donc pas initier deux appels MTN.

Classification des erreurs après soumission :

```text
HTTP 202 / autre 2xx valide   -> pending
HTTP 408 / 409 / 425 / 429    -> unknown
HTTP 5xx                      -> unknown
timeout ou coupure réseau     -> unknown
refus métier explicite 4xx    -> failed
```

Un statut `unknown` ne libère jamais le solde réservé d’un partenaire.

## Rapprochement

```bash
php artisan gepay:reconcile --limit=100
php artisan gepay:reconcile-withdrawals --limit=100
```

Les deux rapprochements sont planifiés chaque minute :

- le premier contrôle les transactions GePay ;
- le second rattache et met à jour les retraits partenaires GePay.

Pour un retrait sans référence GePay, le rapprochement recherche également la transaction par `external_reference`. Si aucune preuve n’est trouvée, le retrait reste `unknown` et son montant reste réservé.

Les statuts `reversed` et `refunded` sont traités comme des inversions financières explicites et nécessitent une revue comptable.

## Compatibilité BantuDelice

Lorsque le flag Collection GePay est actif :

- les nouvelles transactions utilisent leur UUID GePay ;
- les anciennes transactions contenant directement une référence MTN restent vérifiées via l’adapter MTN historique.

Les callbacks MTN destinés à GePay doivent utiliser :

```text
POST /api/gepay/v1/webhooks/mtn
```

La route de callback historique BantuDelice refuse les callbacks lorsqu’ils sont routés vers `GePayAdapter`.

## Déploiement prudent

1. laisser les deux flags BantuDelice à `false` ;
2. déployer le code et vider les caches Laravel ;
3. vérifier `php artisan schedule:list` ;
4. exécuter les tests GePay ;
5. valider Collection en staging ;
6. activer Collection ;
7. surveiller `pending` et `unknown` ;
8. valider puis activer les retraits partenaires.

Rollback immédiat :

```env
GEPAY_BANTUDELICE_COLLECTIONS_ENABLED=false
GEPAY_BANTUDELICE_WITHDRAWALS_ENABLED=false
```

Les transactions déjà initiées par GePay continuent d’être rapprochées.

## Limites actuelles

- Remittance reste désactivé tant que le contrat et les chemins MTN Congo ne sont pas confirmés.
- Le portefeuille/ledger multi-tenant complet reste à construire.
- Les webhooks sortants vers les applications clientes ne sont pas encore opérationnels.
- Le tableau de bord d’administration GePay n’est pas encore inclus.
