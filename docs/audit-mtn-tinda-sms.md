# Audit technique — SMS MTN Congo Tinda

## Conclusion vérifiée

L’existant ne correspondait pas au guide MTN Congo fourni.

- `MtnSmsService.php` utilisait l’API globale `api.mtn.com`, OAuth2 Client Credentials et un payload SMS v3.
- Le guide MTN Congo Tinda impose `POST https://sms.mtncongo.net/api/sms/`, un en-tête `Authorization: Token ...` et les champs JSON `msg`, `sender`, `receivers`.
- `SmsService.php` contient une seconde implémentation MTN OAuth2 distincte. Il existe donc deux implémentations concurrentes et incohérentes.
- `.env.example` ne contenait aucune variable SMS MTN.
- Aucun test automatisé Tinda ni commande d’essai réelle n’existait.

## Modifications de cette branche

- Remplacement de `MtnSmsService` par un client Tinda conforme au document fourni.
- Configuration dédiée `MTN_TINDA_*` sans secret committé.
- Normalisation des numéros congolais vers le format `242...` attendu par Tinda.
- Limite de 1000 destinataires par requête.
- Lecture des réponses `status`, `id`, `resultat`, `detail`.
- Consultation du statut via `{ "op": "status", "id": "..." }`.
- Commande réelle protégée par `--force`.
- Tests HTTP simulés couvrant succès, erreur MSISDN, statut et limite de destinataires.

## Commandes de vérification

```bash
php artisan config:clear
php artisan test --filter=MtnSmsServiceTest
```

Configuration serveur :

```dotenv
MTN_TINDA_ENABLED=true
MTN_TINDA_API_URL=https://sms.mtncongo.net/api/sms/
MTN_TINDA_TOKEN=VALEUR_DU_PROFIL_TINDA
MTN_TINDA_AUTH_PREFIX=Token
MTN_TINDA_SENDER_ID=BantuDelice
```

Envoi réel, facturable :

```bash
php artisan sms:mtn-test 068463499 --message="BantuDelice test API" --force
```

Contrôle du statut :

```bash
php artisan sms:mtn-test --status=10
```

## Point bloquant avant fusion

Le routeur historique `App\Services\SmsService` appelle encore sa propre méthode MTN OAuth2. Pour que tous les OTP et notifications existants utilisent Tinda, cette méthode doit déléguer à `MtnSmsService` et l’ancienne méthode `mtnOAuthToken()` doit être supprimée.

La branche reste volontairement en revue tant que ce raccordement et un essai réel avec token, sender validé et numéro de test autorisé ne sont pas terminés.

## Données manquantes pour le test réel

- Token Tinda actif.
- Sender enregistré et validé sur Tinda.
- Numéro destinataire de test.
- Confirmation de la syntaxe exacte de l’en-tête : le texte du guide mentionne `Token-...`, tandis que les exemples affichent `Token ...`. La configuration `MTN_TINDA_AUTH_PREFIX` permet d’ajuster sans modifier le code.
