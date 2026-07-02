# 07 — Sécurité

## Verdict

Les nouvelles API Colis/Transport et GePay possèdent davantage de middleware, policies et signatures. En revanche, plusieurs routes historiques restent publiques ou utilisent un identifiant fourni par le client. Deux défauts permettent une compromission directe de comptes.

## Anomalies

### SEC-001 — Critique — Réinitialisation de mot de passe sans preuve

- **Fichiers :** `routes/api.php:27-33`; `api/UserController.php:132-169`.
- **Fait :** `forgot_password` accepte téléphone + nouveau mot de passe et met à jour le compte si le téléphone existe.
- **Reproduction :** appeler la route avec le numéro d’un tiers.
- **Conséquence :** prise de contrôle de compte.
- **Correction :** désactiver immédiatement la route historique ; OTP à durée courte, hashé, tentatives limitées, confirmation distincte et révocation des tokens.
- **Tests :** sans OTP, OTP expiré/rejoué, rate limit.
- **Régression :** élevée pour anciens clients mobiles ; prévoir version minimale.

### SEC-002 — Critique — Élévation de privilèges à l’inscription

- **Fichier :** `UserController::register`.
- **Fait :** `type` accepte `admin`, `restaurant`, `driver`, puis `User::create($request->all())`.
- **Conséquence :** création publique potentielle d’un compte privilégié.
- **Correction :** forcer `type=user`; workflows séparés et approuvés pour les autres rôles ; DTO/Request allowlist.
- **Tests :** tentative `type=admin/driver/restaurant`.
- **Régression :** moyenne.

### SEC-003 — Haute — PII accessible sans authentification

- **Routes :** `user_profile/{user}`, `get_user_address/{user}`, suivi de commandes historiques.
- **Fait :** profil et adresses sont retournés par identifiant ; certaines routes de suivi utilisent seulement numéro de commande + restaurant.
- **Conséquence :** exposition de nom, téléphone, adresse et géolocalisation.
- **Correction :** déplacer sous `auth:api`, vérifier propriétaire ou rôle, utiliser références opaques.
- **Tests :** utilisateur A contre B et requête anonyme.
- **Régression :** élevée pour anciennes applications.

### SEC-004 — Haute — Surface historique non authentifiée

- **Fichier :** `routes/api.php:26-101`.
- **Fait :** panier, commandes, profils, adresses et avis historiques sont en grande partie hors groupe d’authentification, même si certains contrôleurs ajoutent des gardes internes.
- **Conséquence :** protection incohérente et oubli facile lors d’une nouvelle méthode.
- **Correction :** authentification au niveau route, policies et suppression progressive des paramètres `user_id` contrôlés par le client.
- **Tests :** inventaire automatisé des routes sensibles sans middleware.
- **Régression :** élevée.

### SEC-005 — Haute — Message d’exception callback exposé

- **Fichier :** `PaymentCallbackController.php:161-173`.
- **Fait :** les `RuntimeException` sont renvoyées au client.
- **Conséquence :** fuite de références, règles et détails internes.
- **Correction :** message opaque, correlation ID et détail uniquement dans logs masqués.
- **Tests :** paiement absent et payload invalide.
- **Régression :** faible.

### SEC-006 — Haute — Callback Bridge sortant insuffisamment contrôlé

- **Fichier :** `MobileMoneyBridgeService::notifyCallbackIfNeeded`.
- **Fait :** URL externe configurable, validation générique, callback non signé et sans file durable.
- **Conséquence :** accès réseau non souhaité et falsification possible côté destinataire.
- **Correction :** allowlist, résolution IP contrôlée, HTTPS, signature HMAC et outbox.
- **Tests :** destinations privées/interdites et signature.
- **Régression :** moyenne.

### SEC-007 — Moyenne — Endpoints opérationnels publics

- **Routes :** `/api/health/modules`, `dependencies`, `queues`, `workers`.
- **Fait :** exposent tables présentes, providers configurés, files et services.
- **Conséquence :** cartographie technique utile à un attaquant.
- **Correction :** garder seulement liveness minimal public ; protéger readiness détaillé par réseau interne ou auth admin.
- **Tests :** accès externe refusé.
- **Régression :** faible pour monitoring si nouvelle URL interne.

### SEC-008 — Haute — Données sensibles dans logs/meta

- **Fichiers :** `PaymentService`, callbacks, certaines erreurs GePay/retrait.
- **Fait :** payloads complets et messages fournisseur peuvent être conservés.
- **Correction :** politique de classification, masqueurs centraux, interdiction des tokens/téléphones complets.
- **Tests :** inspection des logs de callbacks.

### SEC-009 — Moyenne — Uploads hérités

- **Fichier :** `UserController` et uploads de preuves.
- **Fait :** validation MIME existe, mais stockage direct sous répertoire public et nom dérivé du fichier client.
- **Correction :** stockage non exécutable, nom serveur, scan et diffusion contrôlée.
- **Tests :** contenu déguisé, double extension, taille.

## Priorité immédiate

1. Couper `forgot_password` historique.
2. Forcer `type=user` à l’inscription.
3. Protéger profil/adresses/commandes historiques.
4. Masquer callbacks et erreurs.
5. Restreindre endpoints health détaillés.

## Conclusion

La plateforme n’est pas publiable telle quelle sur Internet avec les routes historiques actives. Les deux défauts critiques d’identité doivent être corrigés avant tout nouveau module.