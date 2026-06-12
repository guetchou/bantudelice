# PRD — Certification Production BantuDelice

**Version :** 1.0  
**Date :** 2026-05-31  
**Plateforme :** BantuDelice — livraison de repas, Congo-Brazzaville  
**Stack :** Laravel 10 · PHP 8.3.6 · MySQL · Soketi WebSocket · FCM v1 · MTN MoMo  
**Référence source :** `docs/CONTROLE-CODE.md`

---

## Problem Statement

BantuDelice est une plateforme de livraison de repas opérationnelle sur le VPS OVH (`bantudelice.cg`). Le projet a accumulé une dette technique significative : intégrations défaillantes, workflows brisés, incohérences architecturales, et modules jamais validés en condition réelle. Avant toute ouverture au public, chacun des 21 modules critiques doit être audité, corrigé, et certifié. L'absence de backup restaurable, de tests de régression sur les parcours critiques, et de validation des flux paiement/livraison constitue un risque opérationnel et financier inacceptable.

L'objectif est d'obtenir une certification technique formelle autorisant la mise en production, module par module, avec des preuves concrètes pour chaque point de contrôle.

---

## Solution

Mettre en place un processus de certification production structuré en 21 modules, exécuté dans un ordre précis (infrastructure → sécurité → métier → performance → backup). Chaque module produit une preuve vérifiable (commande CLI, test automatisé, capture, ou requête SQL). La production est autorisée uniquement si zéro critère bloquant n'est ouvert. Un rapport Markdown signé est généré à l'issue du processus.

---

## User Stories

### Socle et Infrastructure

1. Comme responsable technique, je veux que `APP_ENV=production` et `APP_DEBUG=false` soient vérifiés automatiquement au démarrage, afin d'éviter toute exposition d'erreurs Laravel en production.
2. Comme responsable technique, je veux que toutes les 572 routes soient testées pour l'absence de réponse 500, afin de garantir qu'aucun parcours utilisateur ne conduit à une erreur serveur.
3. Comme responsable technique, je veux que toutes les migrations soient au statut `Ran` sans migration pendante, afin que la base de données soit cohérente avec le code déployé.
4. Comme responsable technique, je veux que `config:cache`, `route:cache` soient actifs en production, afin d'optimiser les temps de réponse et de prévenir les appels `env()` directs au runtime.
5. Comme responsable technique, je veux que les logs de production soient consultables et ne contiennent aucune erreur CRITICAL non traitée, afin de détecter les problèmes avant qu'ils n'impactent les utilisateurs.

### Authentification et Sécurité

6. Comme client, je veux pouvoir m'inscrire avec email/téléphone et mot de passe, avec validation stricte et message d'erreur générique (pas d'énumération d'email), afin de protéger mon compte.
7. Comme client, je veux pouvoir me connecter et obtenir un token Passport valide, afin d'accéder aux fonctionnalités authentifiées.
8. Comme client, je veux que la réinitialisation de mot de passe utilise un token à expiration, sans révéler si le compte existe, afin de sécuriser mon accès.
9. Comme restaurateur, je veux accéder uniquement à mon espace restaurant (pas à celui d'un autre), afin que mes données soient protégées.
10. Comme livreur, je veux accéder uniquement à mes livraisons assignées (pas à celles d'un autre driver), afin de respecter la confidentialité.
11. Comme client, je veux ne jamais pouvoir accéder aux commandes, adresses, ou données d'un autre client, même en manipulant les URLs, afin que mes données personnelles soient sécurisées.
12. Comme administrateur, je veux que toutes les actions sensibles (activation restaurant, suspension compte, modification paiement) requièrent une vérification serveur, afin qu'aucune action ne soit possible depuis le frontend seul.
13. Comme système, je veux que le rate limiting soit actif sur les endpoints d'authentification (10 req/min login, 5 req/min forgot_password), afin de prévenir les attaques par force brute.

### Module Utilisateurs et Profils

14. Comme client, je veux consulter et modifier mon profil (nom, téléphone, photo) avec validation serveur, afin de maintenir mes informations à jour.
15. Comme client, je veux gérer mes adresses de livraison (ajout, modification, suppression), afin de simplifier mes commandes récurrentes.
16. Comme restaurateur, je veux gérer le profil complet de mon restaurant (nom, description, horaires, localisation, logo, bannière), afin de présenter mon établissement correctement.
17. Comme livreur, je veux accéder à mon profil (documents, téléphone, statut disponibilité), avec une route dédiée et sécurisée, afin que mes données soient gérées correctement.
18. Comme administrateur, je veux consulter tous les profils utilisateurs avec accès contrôlé (lecture seule ou modification tracée), afin de gérer la plateforme sans risque.

### Module Restaurants

19. Comme administrateur, je veux activer ou suspendre un restaurant avec un statut immédiatement reflété côté client, afin de contrôler la qualité du service.
20. Comme restaurateur, je veux définir mes horaires d'ouverture et mon statut disponible/indisponible, afin que les clients voient en temps réel si je suis opérationnel.
21. Comme client, je veux voir uniquement les restaurants actifs et approuvés sur la page d'accueil, triés par pertinence ou proximité GPS, afin de trouver rapidement ce que je cherche.
22. Comme restaurateur, je veux que mon restaurant n'apparaisse qu'après validation admin, afin d'éviter les établissements frauduleux.

### Module Catalogue et Produits

23. Comme restaurateur, je veux créer des catégories et des produits avec prix, description, image et disponibilité, afin de gérer mon menu.
24. Comme restaurateur, je veux désactiver un produit sans le supprimer, afin de gérer les ruptures temporaires sans perdre les données.
25. Comme client, je veux que le prix affiché corresponde exactement au prix facturé, et que toute manipulation frontend du prix soit rejetée serveur, afin de ne pas être surtaxé ou sous-facturé.
26. Comme système, je veux que le calcul du sous-total, TVA, frais de livraison et remises soit effectué exclusivement côté serveur, afin d'éviter les fraudes au prix.

### Module Panier

27. Comme client, je veux ajouter des produits à mon panier avec validation serveur (produit existant, disponible, quantité valide 1-99), afin d'être sûr que ma commande est réalisable.
28. Comme client, je veux que mon panier soit strictement isolé (je ne peux pas modifier ni voir le panier d'un autre client), afin de protéger mes achats.
29. Comme client, je veux modifier la quantité d'un article (min 1, max 99) et supprimer des articles, avec vérification IDOR à chaque opération, afin de gérer ma commande librement.
30. Comme système, je veux qu'une quantité négative, nulle, ou excessivement grande soit rejetée avec code 422, afin d'éviter les incohérences de stock.

### Module Commandes

31. Comme client, je veux passer une commande complète (panier → adresse → mode paiement → confirmation), avec un numéro de commande unique généré côté serveur, afin d'avoir une référence traçable.
32. Comme restaurateur, je veux recevoir une notification pour chaque nouvelle commande et pouvoir l'accepter ou la refuser, afin de gérer ma capacité.
33. Comme livreur, je veux être notifié quand une livraison m'est assignée et pouvoir accepter ou refuser, afin de gérer ma disponibilité.
34. Comme client, je veux suivre l'état de ma commande en temps réel (pending → accepted → in_kitchen → ready → out_for_delivery → delivered), afin de savoir où en est ma livraison.
35. Comme client, je veux annuler une commande avant la préparation et voir une confirmation d'annulation, afin d'avoir une option de rétractation.
36. Comme client, je veux télécharger mon reçu après livraison, avec le montant exact facturé, afin d'avoir une preuve de paiement.
37. Comme système, je veux qu'une commande annulée ne puisse plus changer de statut comme une commande active, afin d'éviter les incohérences d'état.
38. Comme restaurateur, je veux ne voir que mes propres commandes (jamais celles d'un autre restaurant), afin de respecter la confidentialité commerciale.

### Module Paiement

39. Comme client, je veux payer en espèces avec statut "à encaisser" clair sur le reçu et dans l'admin, afin que le driver soit informé du mode de paiement.
40. Comme client, je veux payer par MTN Mobile Money avec callback sécurisé (signature vérifiée) côté serveur, et commande confirmée uniquement après preuve de paiement, afin de ne pas être débité sans confirmation.
41. Comme système, je veux que chaque callback de paiement soit idempotent (même callback deux fois = une seule confirmation), afin d'éviter les doubles confirmations.
42. Comme système, je veux que le callback MoMo vérifie la signature cryptographique avant toute action, afin d'éviter les callbacks frauduleux.
43. Comme client, je veux que l'échec de paiement n'entraîne jamais une commande confirmée, afin de ne pas être livré sans avoir payé.

### Module Livraison et Drivers

44. Comme livreur, je veux voir les livraisons qui me sont assignées (uniquement les miennes), afin de gérer mon planning.
45. Comme livreur, je veux mettre à jour mon statut (en route, arrivé restaurant, en livraison, livré) avec horodatage, afin que le client soit informé.
46. Comme livreur, je veux accéder à mon historique de livraisons et mes revenus cumulés, avec sécurité IDOR (je ne vois que mes données), afin de suivre mon activité.
47. Comme système, je veux qu'un driver ne puisse pas s'auto-affecter à une commande sans règle validée (affectation manuelle admin ou auto-dispatch), afin d'éviter les conflits.

### Module Tracking GPS

48. Comme client, je veux voir la position de mon livreur sur une carte Leaflet/Mapbox en temps réel (polling 10s ou WebSocket Soketi), afin de savoir quand il arrive.
49. Comme système, je veux que la position GPS d'un driver ne soit visible que pour le client qui attend sa livraison active (pas pour tous les clients), afin de respecter la vie privée du driver.
50. Comme système, je veux que si le WebSocket Soketi échoue, le polling HTTP (toutes les 10 secondes via `/api/orders/{id}/tracking`) prenne le relais, afin que le tracking reste opérationnel.
51. Comme administrateur, je veux voir la position de tous les drivers actifs sur une carte admin, afin de superviser les opérations.

### Module Notifications

52. Comme restaurant, je veux recevoir une notification push FCM v1 pour chaque nouvelle commande, afin de ne pas manquer une commande.
53. Comme client, je veux recevoir une notification push pour chaque changement de statut de ma commande (acceptée, en préparation, en route, livrée), afin d'être informé sans surveiller l'écran.
54. Comme livreur, je veux recevoir une notification push quand une livraison m'est assignée, afin de démarrer la prise en charge rapidement.
55. Comme système, je veux que les notifications FCM utilisent l'API v1 avec JWT base64url (RFC 4648 §5), afin de ne pas échouer silencieusement sur les credentials Google.
56. Comme client, je veux recevoir un email de confirmation de commande (si email non test/bantudelice.cg), afin d'avoir une trace écrite.
57. Comme client, je veux recevoir un SMS de confirmation si le fournisseur SMS est configuré (MTN SMS v3 ou Twilio), afin d'être informé même sans connexion internet.

### Module Admin

58. Comme administrateur, je veux accéder à un dashboard affichant des KPIs exacts (commandes du jour, revenus, commandes en attente), cohérents avec la base de données, afin de piloter la plateforme.
59. Comme administrateur, je veux filtrer les commandes par statut, date, restaurant, et driver, afin d'investiguer rapidement un problème.
60. Comme administrateur, je veux activer, suspendre ou supprimer un restaurant avec un motif obligatoire, afin de tracer mes actions.
61. Comme administrateur, je veux voir tous les paiements avec leur état (confirmé, en attente, échoué, remboursé), cohérents avec les callbacks reçus, afin de détecter les anomalies.
62. Comme système, je veux qu'aucune action admin (activation, suspension, suppression) ne soit possible sans vérification serveur (middleware + policy), afin d'éviter les actions non autorisées depuis un frontend compromis.

### Module KPIs et Revenus

63. Comme administrateur, je veux consulter les revenus par restaurant, par driver, et par période, avec calcul des commissions correct (% plateforme, frais livraison, pourboire driver), afin de générer les virements.
64. Comme restaurateur, je veux consulter mes revenus nets (après commission plateforme), afin de vérifier ma rémunération.
65. Comme livreur, je veux consulter mes revenus accumulés (frais livraison + pourboires), afin de suivre mes gains.
66. Comme système, je veux que tous les calculs financiers soient effectués côté serveur et stockés en FCFA (devise principale), sans arrondi non contrôlé, afin d'éviter les erreurs de comptabilité.

### Module Fichiers et Images

67. Comme restaurateur, je veux uploader logo et bannière avec validation serveur (type MIME: jpeg/png/webp, taille max 4MB, pas de fichier exécutable), afin de personnaliser mon profil.
68. Comme système, je veux que les fichiers uploadés soient stockés hors du webroot public (ou avec validation stricte), et que les extensions `.php`, `.js`, `.sh` soient interdites, afin d'éviter l'exécution de code malveillant.
69. Comme système, je veux que l'ancien fichier (logo, image produit) soit supprimé lors d'un remplacement, afin d'éviter l'accumulation de fichiers orphelins.

### Module API Mobile

70. Comme développeur mobile, je veux que tous les endpoints API retournent des codes HTTP corrects (200, 201, 400, 401, 403, 404, 422, 500), afin de gérer correctement les erreurs dans l'app.
71. Comme développeur mobile, je veux que les erreurs Laravel ne soient jamais exposées brutes (stack trace, .env, SQL), afin de ne pas exposer l'architecture interne.
72. Comme développeur mobile, je veux que chaque endpoint POST/PUT/PATCH soit protégé par un FormRequest avec validation, afin de garantir l'intégrité des données.

### Module UI/UX

73. Comme client, je veux que toutes les pages critiques (login, accueil, restaurants, panier, checkout, suivi, profil) soient responsive sur mobile (320px-390px), afin d'utiliser la plateforme depuis mon téléphone.
74. Comme client, je veux voir des états de chargement (spinner), états vides (empty state), et états d'erreur sur toutes les listes et formulaires, afin de comprendre ce qui se passe.
75. Comme client, je veux que les boutons d'action (valider commande, payer) soient désactivés pendant le traitement, afin d'éviter les doubles soumissions.

### Module Légal

76. Comme client, je veux consulter les CGU, politique de confidentialité, et politique de remboursement depuis une page dédiée non vide, afin de connaître mes droits.
77. Comme système, je veux que les données personnelles soient gérées conformément aux règles de base (pas de stockage carte bancaire, rétention logs contrôlée), afin de limiter les risques légaux.

### Module Performance

78. Comme client, je veux que les pages publiques (accueil, restaurants) chargent en moins de 3 secondes, afin de ne pas abandonner le parcours.
79. Comme système, je veux que les listes paginées (commandes admin, restaurants, produits) utilisent `paginate()` et non `->get()->all()`, afin d'éviter les chargements complets de table.
80. Comme système, je veux que les notifications et emails lourds soient traités via les queues (`database` queue + workers actifs), afin de ne pas bloquer les requêtes HTTP.

### Module Backup et Rollback

81. Comme responsable technique, je veux un backup quotidien automatisé de la base de données MySQL, stocké hors du VPS principal, afin de pouvoir restaurer après une panne.
82. Comme responsable technique, je veux une procédure de rollback documentée et testée (restauration DB + fichiers + déploiement commit précédent), afin de récupérer en moins de 30 minutes.
83. Comme responsable technique, je veux un tag Git sur chaque version déployée en production, afin de savoir exactement quel code tourne.

---

## Implementation Decisions

### Architecture de certification

**Approche par module fermé** : chaque module est certifié indépendamment dans l'ordre défini par `CONTROLE-CODE.md` (infrastructure → auth → métier → perf → backup). Un module ne peut pas être certifié si un module dont il dépend est KO.

**Rapport de certification** : le fichier `docs/audit-production-bantudelice.md` est le document de référence. Il est généré automatiquement (commandes CLI) et complété manuellement pour les tests de parcours.

### État actuel (baseline au 2026-05-31)

| Critère | État |
|---|---|
| `APP_ENV=production` | ✅ |
| `APP_DEBUG=false` | ✅ |
| `config:cache` actif | ✅ |
| `route:cache` actif | ✅ |
| Migrations toutes `Ran` | ✅ |
| Routes (572) sans méthode inexistante | À vérifier |
| Backup DB restaurable | ❌ BLOQUANT |
| Git tag production | ❌ BLOQUANT |
| env() directs éliminés (app/) | ✅ |
| IDOR Cart, Driver, User corrigés | ✅ |
| FCM v1 JWT base64url | ✅ |
| FormRequests sur POST critiques | ✅ (8 ajoutés) |
| SMS fonctionnel +242 | ⚠️ En cours (MTN activation) |
| Rate limiting API auth | ✅ |
| Tests automatisés (suite) | ⚠️ phpunit absent VPS |

### Décisions techniques

**Sécurité IDOR** : pattern `soft guard` adopté sur tous les endpoints sensibles — si le token Passport est présent, l'ID du token doit correspondre à l'URL param. Backward-compatible avec l'app mobile tokenless.

**Paiement MoMo** : le callback `POST /api/payments/callback/momo` est la seule source de vérité pour la confirmation de paiement. Toute confirmation sans callback valide est rejetée. Idempotence garantie par `payment_status` en DB.

**Notifications FCM** : JWT OAuth2 utilise `base64url` (RFC 4648 §5) via closure `$b64url`. Fallback legacy key désactivé.

**SMS Congo** : MTN SMS v3 API (OAuth2 client credentials + scope SEND-SMS) est le provider principal. Token mis en cache 14 jours. Twilio comme fallback international. AfricasTalking retiré (couverture +242 absente).

**Tracking temps réel** : WebSocket Soketi (port 6001) + polling HTTP 10s en fallback. La position driver n'est visible que pour la commande active du client concerné.

**Calcul financier** : sous-total, TVA, frais livraison, remise, et commission sont exclusivement calculés côté serveur dans `OrderPricingService`. Toute valeur venant du frontend est ignorée.

**Queue workers** : deux workers actifs en production (`php artisan queue:work database --queue=food,default,colis,transport`). Les notifications, emails, et SMS lourds passent par ces queues.

### Modules deep à tester en isolation

1. `PostOrderService` — post-commit fire-and-forget (ledger, signals, risk, push, email)
2. `RestaurantService::searchRestaurants` — proximity GPS (Haversine) + text fallback
3. `FcmTransport` — JWT base64url OAuth2
4. `PaymentCallbackController::handle` — validation signature + idempotence
5. `DispatchService::processPendingDeliveries` — filtre business_status valides

---

## Testing Decisions

### Principe directeur

Un bon test vérifie le comportement observable depuis l'extérieur du système, pas les détails d'implémentation. Pour BantuDelice : tester les réponses HTTP (codes, corps JSON), les états en base de données après action, et les effets de bord (emails envoyés, jobs dispatchés). Ne jamais asserter sur des méthodes privées ou l'ordre d'appel interne.

### Modules à couvrir en priorité

| Module | Type de test | Priorité |
|---|---|---|
| `PostOrderService` | Unit (mock services) | P0 |
| FormRequests (PlaceOrder, ConfirmReceipt, ReportIncident, AddToCart, UpdateProfile) | Unit (Validator::make) | P0 |
| `RestaurantService::searchRestaurants` GPS | Feature (RefreshDatabase) | P0 |
| Auth (login, register, IDOR profil) | Feature HTTP | P0 |
| Panier IDOR (A ne voit pas B) | Feature HTTP multi-user | P0 |
| Commande cycle complet | Feature HTTP | P1 |
| Callback MoMo (idempotence, signature) | Feature HTTP | P1 |
| `FcmTransport` JWT base64url | Unit | P1 |
| Tracking GPS isolation client | Feature HTTP | P1 |
| Admin actions (middleware guard) | Feature HTTP | P2 |

### Prior art dans la codebase

Les tests existants (`tests/Feature/`) couvrent déjà :
- `IndexControllerPhase4CartCheckoutOrderTest` — panier IDOR, addToCart validation
- `OrderTrackingApiTest` — accès tracking par commande
- `PaymentFlowRegressionTest` — callbacks paiement
- `CheckoutOrchestratorTest` — injection de dépendances

Les nouveaux tests suivent le même pattern : `actingAs($user)` + assertions HTTP + `assertDatabaseHas`.

---

## Out of Scope

- **WhatsApp Business** : validation Meta en cours (2-4 semaines), pas bloquant pour la production.
- **MTN SMS v3 API** : activation en attente côté MTN Congo. Le service `MtnSmsService` est déployé et prêt ; l'activation est hors du périmètre de ce PRD.
- **Twilio upgrade** : dépend d'un paiement externe. Le `TwilioService` est prêt ; l'upgrade compte est hors périmètre.
- **Optimisations N+1** : `AdminOrderController` charge toutes les commandes en mémoire (25 actuellement, problème à 10k+). Non bloquant pour la phase actuelle.
- **Tests de charge** : hors périmètre, traité séparément après stabilisation.
- **Internationalisation i18n** : la plateforme est en français uniquement pour l'instant.
- **Application mobile native** : seule la web app est dans le périmètre de certification.

---

## Further Notes

### Critères bloquants production (rappel CONTROLE-CODE.md)

La production est interdite si l'un de ces points est ouvert :

1. `APP_DEBUG=true` → ✅ résolu
2. Route cassée (méthode controller inexistante) → À vérifier exhaustivement
3. Erreur 500 sur parcours critique → À tester manuellement
4. Utilisateur A peut voir/modifier données utilisateur B → ✅ IDOR corrigé (8 endpoints)
5. Paiement confirmé sans preuve serveur → ✅ callback requis
6. Commande validée avec prix manipulable frontend → ✅ recalcul serveur
7. Admin action sans vérification serveur → À auditer
8. Aucun backup restaurable → ❌ **BLOQUANT ACTIF**
9. Logs remplis d'erreurs non traitées → 3 erreurs aujourd'hui, à investiguer
10. Dashboard faux chiffres → À vérifier
11. Driver sans route profil ou sans contrôle d'accès → À vérifier
12. Restaurant sur commande qui ne lui appartient pas → ✅ guards en place

### Ordre d'exécution recommandé pour la certification

```
Phase 1 — Infrastructure (1 jour)
  → Backup DB + procédure rollback + git tag
  → Route list exhaustif (572 routes, zéro 500)
  → Logs investigation (3 erreurs du 31/05)

Phase 2 — Sécurité (1 jour)
  → Tests IDOR complets (client/restaurant/driver/admin)
  → Callback MoMo signature test
  → Upload sécurité (extensions refusées)

Phase 3 — Métier (2 jours)
  → Parcours commande complet (client → resto → driver → livraison)
  → Paiement cash + MoMo end-to-end
  → Notifications FCM tous les statuts
  → Tracking GPS isolation

Phase 4 — Performance et UI (1 jour)
  → Pagination listes admin
  → Responsive mobile (320px)
  → Queue workers actifs

Phase 5 — Certification (0.5 jour)
  → Rapport docs/audit-production-bantudelice.md
  → Git tag vX.Y.Z
  → Go / No-go
```

### Commandes de validation à exécuter sur le VPS

```bash
php artisan route:list --columns=method,uri,action > /tmp/routes.txt
php artisan migrate:status
php artisan test --no-coverage
grep -c "ERROR\|CRITICAL" storage/logs/laravel-$(date +%Y-%m-%d).log
php artisan queue:monitor
```
