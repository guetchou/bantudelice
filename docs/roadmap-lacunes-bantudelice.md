# Roadmap des lacunes L1–L9 — BantuDelice

**Date** : 2026-06-19 (mise à jour 2026-06-23 : L1 close, L9 clarifié)
**Source** : `docs/benchmark-github-bantudelice.md` (synthèse L1-L9, non modifié par ce document)
**Méthode** : chaque lacune a été re-vérifiée dans le code actuel (`/opt/bantudelice/projects/bantudelice-prod-audit`) avant d'être détaillée ci-dessous — certaines descriptions du benchmark se sont révélées **partiellement obsolètes** ; les corrections sont signalées explicitement.

**Ce document ne contient aucun code. Aucun chantier n'est ouvert par sa seule publication — chaque item reste à l'état de proposition jusqu'à validation explicite (`/PRD && /plan` requis avant toute exécution, conformément aux règles du projet).**

---

## Interdictions valables pour tous les items ci-dessous

- Ne pas démarrer l'implémentation sans validation explicite item par item.
- Ne pas mélanger plusieurs lacunes dans un même chantier/PR.
- Ne pas modifier `business_status`/`payment_status` en dehors de `FoodOrderStateMachineService` (state machine déjà verrouillée par les gardes dures existantes).
- Ne pas introduire de package externe sans justification + audit licence.
- Ne pas casser les contrats API existants (mobile driver app, front client) sans versioning.
- Ne pas toucher au workflow paiement différé validé (`docs/validation-workflow-paiement-differe.md`) en dehors du périmètre strict de chaque item.

---

## L1 — `cash_collection_status` jamais mis à jour automatiquement

**Statut : FERMÉ (2026-06-23) — issue GitHub #3 close `completed`**

**Priorité : Haute**

### Résolution

Le mécanisme nominal était déjà livré en production depuis le commit `b31d682` (2026-06-20) : `FoodOrderStateMachineService::transitionOrders()` pose `cash_collection_status=collected` + `cash_collected_at`/`cash_collected_by`/`cash_collection_confirmed_at` à la transition `delivered` pour `payment_method=cash`, et `DeliveryService::disputeCashCollection()`/`resolveCashDispute()` gèrent les cas `disputed`/`collection_failed`.

Audit du 2026-06-23 (avant tout patch) a confirmé ce mécanisme et trouvé une faille résiduelle non couverte : les actions manuelles `admin.deliver_order`/`restaurant.deliver_order` (`force: true`) pouvaient écraser silencieusement un statut `disputed`/`collection_failed` en le repassant à `collected`, faute de vérification du statut courant. Corrigé par le commit `65d4d8f` (1 ligne de garde dans le bloc cash de `transitionOrders()`) + 4 tests de régression dans `tests/Feature/Food/CashCollectionStatusTest.php`. Suite complète : 580/580 tests passés. Pipeline CI/CD vérifié vert de bout en bout sur ce commit, déployé en production.

Reste hors périmètre de L1, suivi séparément dans l'issue GitHub #12 (`feat(admin): interface de suivi des statuts de collecte cash`) : interface admin de supervision (liste/filtres/historique), pas de logique métier manquante.

### Problème constaté (historique, avant correctif — conservé pour traçabilité)
`orders.cash_collection_status` reste figé à `pending_collection` après livraison. Vérifié dans `app/Services/DeliveryService.php::updateStatus()` (branche `DELIVERED`, lignes ~217-256) : seuls `deliveries.cash_collected_at` et `payments.meta.cash_collected_at` sont renseignés via `shouldMarkCashCollected()` (ligne 612) et `markPaymentCompletedIfNeeded()` (ligne 628). Le champ `orders.cash_collection_status` lui-même — ainsi que `cash_collected_by`, `cash_collection_confirmed_at`, `cash_collection_reference` (tous présents dans `$fillable` de `app/Order.php`) — n'est écrit nulle part dans le code applicatif actuel.

### Impact métier
Aucun suivi fiable de l'encaissement cash réel. Impossible de distinguer "livré, cash encaissé" de "livré, cash non remis au restaurant" sans audit manuel base de données. Bloque tout virement automatique restaurant basé sur du cash confirmé.

### Fichiers probablement concernés
- `app/Services/DeliveryService.php` (méthode `updateStatus`, branche `DELIVERED`, et `shouldMarkCashCollected`)
- `app/Order.php` (déjà prêt : champs fillable existants)
- `app/Http/Controllers/Api/DriverDeliveriesController.php` (`updateStatus`, si un endpoint dédié de confirmation séparée est retenu)
- Éventuelle nouvelle migration si un statut `disputed`/`collection_failed` nécessite une contrainte ou un index

### Critères d'acceptation
- À la transition `DELIVERED` d'une commande `payment_method=cash`, `orders.cash_collection_status` passe de `pending_collection` à `collected`, avec `cash_collected_at`, `cash_collected_by` (= driver_id) renseignés dans le même `DB::transaction()` que la transition de livraison.
- Cas `disputed` : le client conteste avoir payé en cash après livraison — déclenchable séparément (ex. via le flux incident déjà existant `reportIncident`), passe `cash_collection_status = disputed`.
- Cas `collection_failed` : le livreur signale ne pas avoir pu encaisser — `cash_collection_status = collection_failed`, ne doit pas bloquer la transition `DELIVERED` elle-même (livraison effectuée ≠ encaissement réussi, ce sont deux faits distincts).
- Aucun impact sur `payment_status` (déjà `paid` dès l'acceptation pour cash — ne pas re-toucher cette règle verrouillée).

### Tests requis
- Feature test : commande cash → `DELIVERED` → assert `cash_collection_status=collected`, `cash_collected_at` non nul, `cash_collected_by=driver_id`.
- Feature test : scénario `collection_failed` ne bloque pas `business_status=delivered`.
- Vérifier non-régression sur `tests/Feature/Food/EndToEndOrderFlowTest.php` (le test E2E cash existant ne vérifie pas aujourd'hui `cash_collection_status` — à étendre, pas à remplacer).

### Dépendances (historique)
Aucune. Référencé par l'issue GitHub #3 (`fix(food): mise à jour cash_collection_status à la livraison`) — **close** depuis le 2026-06-23, voir section Résolution ci-dessus.

### Interdictions spécifiques
- Ne pas déclencher de virement réel/automatique vers le restaurant dans ce chantier — uniquement le tracking d'état. Le virement est un sujet financier séparé, hors scope.
- Ne pas modifier la garde dure `guardInKitchenRequiresConfirmedAndPaid` (sans rapport avec cette lacune).

---

## L2 — Pas de timestamps visibles par étape côté client

**Priorité : Moyenne**

### Problème constaté
Vérifié : `resources/views/frontend/track_order.blade.php` n'affiche aucune donnée issue de `order_status_logs`. La table existe et est déjà alimentée à chaque transition (utilisée pour l'audit, cf. `FoodOrderStateMachineService::transitionOrders()`), mais aucune route/contrôleur ne l'expose à la vue client.

### Impact métier
Le client ne voit pas l'heure de chaque étape (accepté à 14h32, en cuisine à 14h38…) — expérience de tracking moins rassurante que la concurrence (UberEats/Deliveroo affichent systématiquement ces horodatages).

### Fichiers probablement concernés
- `app/Http/Controllers/CustomerOrderController.php` (méthode `trackOrder`, ligne ~282 — déjà charge `$order`, à étendre pour charger les logs)
- `resources/views/frontend/track_order.blade.php` (ajout d'un bloc breadcrumb)
- Table `order_status_logs` (lecture seule, pas de migration nécessaire)

### Critères d'acceptation
- La page `/track-order/{orderNo}` affiche la liste des transitions `business_status` du `order_no` avec horodatage lisible (format local Congo/Brazzaville).
- N'affiche que les transitions pertinentes côté client (exclure les transitions internes/admin type `status_transition_forced_unpaid`).
- Aucune donnée sensible (acteur interne, raison technique brute) exposée au client — reformuler les `reason_code` en libellés client (ex. `payment_confirmed` → "Paiement confirmé").

### Tests requis
- Feature test : visiter `/track-order/{orderNo}` pour une commande ayant traversé plusieurs transitions, vérifier la présence des timestamps attendus dans la réponse HTML/JSON.
- Vérifier qu'un `order_status_logs` avec `actor_type=admin`/`reason_code=status_transition_forced_unpaid` n'apparaît pas dans le rendu client.

### Dépendances
Aucune. Indépendant des autres items.

### Interdictions spécifiques
- Ne pas exposer `order_status_logs.actor_id`/`meta` brut au client (fuite d'information interne).
- Ne pas remplacer le système de polling/Soketi existant — ceci est un ajout d'affichage, pas un changement de mécanisme temps réel.

---

## L3 — Pas de badge "espèces à collecter" dans l'app livreur

**Priorité : Moyenne**

### Problème constaté
Vérifié : `app/Http/Controllers/Api/DriverDeliveriesController.php::index()` (lignes ~41-71) ne retourne ni `payment_method` ni `cash_collection_status` dans le payload JSON consommé par l'app livreur. Impossible pour le client mobile d'afficher un badge "cash à collecter" même si l'UI le souhaitait — le champ n'existe pas dans la réponse API.

### Impact métier
Risque d'oubli de collecte d'espèces par le livreur, aucune alerte visuelle disponible côté app.

### Fichiers probablement concernés
- `app/Http/Controllers/Api/DriverDeliveriesController.php` (ajout de champs dans le `$data->map()`)
- Application mobile livreur (hors de ce repo — à coordonner séparément, consommateur de l'API)

### Critères d'acceptation
- La réponse de `GET /api/driver/deliveries` inclut `payment_method` et `cash_collection_status` par livraison.
- Documentation API (`docs/api-mobile-swagger.yaml` si applicable) mise à jour en conséquence.
- Pas de changement de structure des champs existants (ajout uniquement, non-breaking).

### Tests requis
- Test API : `GET /api/driver/deliveries` sur une commande cash, vérifier présence et valeur de `payment_method=cash` et `cash_collection_status`.
- Coordination avec l'équipe mobile pour confirmer la consommation du nouveau champ (hors scope code backend).

### Dépendances
**Dépendait de L1** — `cash_collection_status` doit être correctement maintenu avant d'avoir un sens à l'exposer au livreur. L1 est fermé depuis le 2026-06-23 (voir section L1) : **L3 est désormais débloqué**, mais reste un item séparé non commencé — toujours `/PRD && /plan` requis avant tout codage.

### Interdictions spécifiques
- Ne pas modifier les champs existants de la réponse (`status`, `business_status`, etc.) — ajout additif uniquement.
- Ne pas coder le rendu visuel mobile dans ce repo si l'app livreur vit dans un autre dépôt — clarifier l'emplacement avant tout chantier.

---

## L4 — Pas de versioning API cohérent pour le module food/driver

**Priorité : Moyenne — correction du constat benchmark**

### Problème constaté (corrigé par rapport au benchmark)
Le benchmark affirme l'absence totale de versioning API. **Vérification du code : faux en l'état actuel** — `routes/api.php` a déjà un groupe `Route::prefix('v1')` (ligne 176) utilisé pour les modules **colis/courier/transport** (`Api\V1\Colis\*`, `Api\V1\Courier\*`, `Api\V1\Admin\AdminShipmentController`). En revanche, les routes du module **food** (checkout, `driver/deliveries`, restaurant order management, `DriverOrderController`, etc.) restent **hors du préfixe `v1`**, sans namespace de version. La lacune réelle est une **incohérence de versioning entre modules**, pas une absence totale.

### Impact métier
Toute évolution future des endpoints food/driver imposera un breaking change direct aux clients (app mobile, front) faute de version dédiée — contrairement à colis/courier déjà protégés.

### Fichiers probablement concernés
- `routes/api.php` (lignes hors du bloc `v1`, notamment les routes `driver_*`, `order_accept_by_driver`, `driver/deliveries/*`)
- Contrôleurs concernés : `DriverOrderController`, `Api\DriverDeliveriesController`, `DriverProfileController`, `DriverAuthController`

### Critères d'acceptation
- Nouvelles routes food/driver exposées sous un préfixe `v1` cohérent avec colis/courier, **sans supprimer les routes legacy non-versionnées tant que les clients existants (app mobile en prod) n'ont pas migré**.
- Stratégie de double exposition (legacy + v1) documentée avec date de dépréciation des routes legacy.
- Aucune route legacy supprimée dans ce chantier — ajout en parallèle uniquement.

### Tests requis
- Test de non-régression : toutes les routes legacy existantes répondent identiquement après ajout du miroir `v1`.
- Test des nouvelles routes `v1` pour chaque endpoint dupliqué.

### Dépendances
Aucune technique, mais **nécessite une décision produit** sur le calendrier de dépréciation des routes legacy (qui consomme l'app mobile actuelle ?) avant tout chantier — à clarifier avec le propriétaire produit, pas une décision technique unilatérale.

### Interdictions spécifiques
- Ne jamais supprimer une route legacy consommée par l'app mobile en production sans confirmation explicite de migration côté client.
- Ne pas verser ce chantier dans le même PR que L1/L3 (incohérent en portée).

---

## L5 — Pas d'horaires d'ouverture restaurant

**Priorité : Haute — correction majeure du constat benchmark**

### Problème constaté (corrigé par rapport au benchmark)
Le benchmark affirme l'absence totale d'un module horaires d'ouverture, en proposant d'ajouter une table `opening_hours`. **Vérification du code : cette affirmation est fausse.** Le modèle de données existe déjà intégralement :
- Table `working_hours` (migration `2020_02_28_043219_create_working_hours_table.php`)
- Relation `Restaurant::working_hours()` (`app/Restaurant.php` ligne 63) et `Restaurant::special_closures()` (ligne 67)
- `app/WorkingHour.php` (modèle)
- `app/Http/Controllers/restaurant/WorkingHourController.php` — CRUD complet déjà fonctionnel côté dashboard restaurant (`index`, `create`, `store`, `edit`, `update`, `destroy`)

**La vraie lacune** : ces horaires configurés ne sont **jamais consultés** au moment du checkout ou de l'affichage du menu. Vérifié par grep : aucune référence à `working_hours`/`special_closures`/`isOpenNow` dans `CartCheckoutController`, `CheckoutService`, ou `OrderAcceptanceService`. Un restaurant peut donc recevoir et faire accepter des commandes en dehors de ses horaires déclarés, malgré une configuration correcte en base.

### Impact métier
Commandes reçues hors horaires → restaurant surpris, refus tardif, mauvaise expérience client. Le module existe mais ne protège personne.

### Fichiers probablement concernés
- `app/Restaurant.php` (ajouter une méthode `isOpenNow(): bool` consultant `working_hours` + `special_closures`)
- `app/Services/CheckoutService.php` (vérification avant `startCheckout()`)
- `app/Http/Controllers/CartCheckoutController.php` (blocage UI/API si fermé)
- Vues menu restaurant (affichage badge "Fermé" si applicable)
- **Aucune nouvelle migration nécessaire** — contrairement à ce que suggère le benchmark.

### Critères d'acceptation
- Une méthode centralisée `Restaurant::isOpenNow()` fait foi pour toute décision d'ouverture (pas de logique dupliquée dans chaque contrôleur).
- Checkout bloqué (message explicite) si le restaurant est fermé au moment de la tentative — pas seulement à l'affichage du menu (cohérence entre lecture et écriture).
- `special_closures` (fermetures exceptionnelles) prioritaires sur `working_hours` (horaires récurrents) dans la logique de décision.
- Pas de changement de la garde dure paiement/livraison déjà validée — ce contrôle se situe strictement **avant** `pending_restaurant_acceptance`, à la création de commande.

### Tests requis
- Feature test : checkout tenté sur un restaurant avec `working_hours` ne couvrant pas l'heure courante → rejeté avec message clair.
- Feature test : `special_closures` actif aujourd'hui → checkout rejeté même si `working_hours` couvrirait l'heure.
- Test de non-régression sur les restaurants sans aucune `working_hours` configurée (comportement par défaut à définir explicitement : ouvert 24/7 par défaut, ou fermé par défaut ? **Décision produit à valider avant codage** — un restaurant existant sans configuration ne doit pas se retrouver bloqué par surprise).

### Dépendances
Aucune dépendance technique avec les autres items. Nécessite une **décision produit explicite sur le comportement par défaut** (cf. ci-dessus) avant tout chantier.

### Interdictions spécifiques
- Ne pas créer de nouvelle table `opening_hours` — le module `working_hours` existe déjà, le dupliquer serait une dette technique immédiate.
- Ne pas modifier le CRUD `WorkingHourController` existant (fonctionnel, hors scope).

---

## L6 — Dashboard admin sans KPIs temps réel

**Priorité : Basse — fortement revue à la baisse après vérification**

### Problème constaté (corrigé par rapport au benchmark)
Le benchmark suggère l'absence de KPIs admin. **Vérification du code : largement faux.** `app/Services/DashboardMetricsService.php` est un service mature exposant déjà : `revenueCurrent`, `revenueToday`, `pendingOrdersCount`, `delayedRestaurantsCount`, `driversUnavailableCount`, `paymentAnomaliesCount`, séries de revenu (`buildRevenueSeries`), top restaurants, activités live (`buildLiveActivities`), carte des livreurs (`buildDriverMarkers`), répartition par service/transport/colis. C'est un niveau de KPI supérieur à ce que propose la référence `BlondelSeumo` citée dans le benchmark.

**Lacune résiduelle potentielle (non confirmée)** : reste à vérifier si la vue admin (Blade consommant ce service) effectue un rafraîchissement automatique (polling/WebSocket) ou nécessite un rechargement manuel de page. Ce point n'a pas été vérifié dans cette passe — à confirmer avant toute priorisation.

### Impact métier
Faible si confirmé que les KPIs existent déjà — impact résiduel limité à l'ergonomie de rafraîchissement, pas à l'absence de données.

### Fichiers probablement concernés (si confirmation du gap résiduel)
- Vue Blade admin consommant `DashboardMetricsService::collect()`
- Éventuel ajout de polling JS ou intégration Soketi déjà utilisé ailleurs dans le projet

### Critères d'acceptation
- **Avant tout chantier** : audit court (lecture de la vue Blade + JS associé) pour confirmer ou infirmer l'absence de rafraîchissement automatique.
- Si confirmé : ajout d'un rafraîchissement périodique (réutiliser le pattern Soketi déjà en place pour le tracking livraison plutôt que du polling, par cohérence technique).

### Tests requis
- Aucun avant l'audit de confirmation ci-dessus.

### Dépendances
Aucune. Item à reclasser en "discovery" plutôt que chantier ferme tant que le gap résiduel n'est pas confirmé.

### Interdictions spécifiques
- Ne pas recréer un système de KPI parallèle — `DashboardMetricsService` est déjà la source de vérité, toute évolution doit l'étendre, pas le contourner.

---

## L7 — Algorithme auto-assign livreur : rayon fixe uniquement

**Priorité : Basse — précision du constat benchmark**

### Problème constaté (précisé)
Le benchmark propose un "rayon progressif" inspiré de `ptduy14/ride-hailing-service-web-app`. Vérification du code : `app/Services/DispatchService.php` dispose déjà d'une pondération par distance (Haversine, lignes ~167-189) avec pénalité de score par tranche (0-5km, 5-10km, 10-20km, 20km+, lignes ~114-126) — ce n'est donc pas un rayon strictement fixe, mais un **score dégressif sur un rayon de recherche unique et statique**. La lacune réelle, plus précise que la formulation du benchmark : **absence de mécanisme de réélargissement automatique du rayon de recherche dans le temps** si aucun livreur n'accepte dans un délai donné (ex. élargir de 5km → 10km après 2 minutes sans acceptation).

### Impact métier
Commandes en zones peu denses en livreurs risquant de ne jamais trouver de livreur si le rayon de recherche initial est trop restrictif, sans mécanisme de repli.

### Fichiers probablement concernés
- `app/Services/DispatchService.php`
- `app/Jobs/AutoAssignDeliveryJob.php` (orchestration du re-essai)
- Configuration : nouveau paramètre `config('food.dispatch_radius_steps')` ou équivalent

### Critères d'acceptation
- Si aucun livreur n'accepte l'offre dans un délai configurable, le rayon de recherche est élargi automatiquement et une nouvelle vague de `BroadcastDeliveryOfferJob` est émise.
- Le garde-fou déjà existant (`AutoAssignDeliveryJob` lignes 63-64, n'agit que sur `in_kitchen`/`ready_for_pickup`) doit rester intact — ce chantier ne touche pas à cette garde.
- Nombre de paliers et durée entre paliers configurables, pas codés en dur.

### Tests requis
- Test unitaire : simulation d'absence d'acceptation à T+0, vérifier l'élargissement à T+seuil.
- Test de non-régression sur le calcul de score existant (`calculateDistance`, pénalités par tranche) — ne pas modifier la formule actuelle, seulement le rayon de recherche initial des candidats.

### Dépendances
Aucune.

### Interdictions spécifiques
- Ne pas toucher à la garde `business_status` de `AutoAssignDeliveryJob` (sans rapport avec cette lacune).
- Ne pas modifier la formule de pénalité par distance déjà en place — uniquement ajouter un mécanisme de re-déclenchement.

---

## L8 — Pas d'export CSV commandes

**Priorité : Basse**

### Problème constaté
Confirmé par grep : aucune fonction d'export (`csv`, `Excel`, `export`) dans `app/Http/Controllers/admin/OrderController.php`. Les méthodes existantes (`all_orders`, `complete_orders`, `pending_orders`, `cancel_orders`) ne retournent que des vues paginées.

### Impact métier
Aucun export possible pour comptabilité/analyse externe sans requête SQL manuelle.

### Fichiers probablement concernés
- `app/Http/Controllers/admin/OrderController.php` (nouvelle méthode `export`)
- `routes/web.php` (groupe admin, ligne ~261)
- Éventuel package CSV (vérifier si `league/csv` ou équivalent est déjà en dépendance avant d'en ajouter un nouveau — `composer.json` à consulter avant tout chantier, conformément à l'interdiction générale d'ajout de package sans justification)

### Critères d'acceptation
- Export CSV filtrable par plage de dates et statut, cohérent avec les filtres déjà utilisés par `all_orders()`.
- Pas de chargement de l'intégralité de la table `orders` en mémoire (utiliser un export streamé/chunké si le volume le justifie).
- Aucune donnée sensible non nécessaire exportée (vérifier RGPD/PII minimal : nom client, téléphone — à valider avec le propriétaire produit avant export).

### Tests requis
- Feature test : export sur une plage de dates connue, vérifier le contenu CSV (en-têtes, nombre de lignes, valeurs).
- Test de performance basique si le volume de commandes est significatif (éviter un export qui timeout).

### Dépendances
Aucune.

### Interdictions spécifiques
- Ne pas ajouter de dépendance Composer sans vérifier l'absence d'équivalent déjà présent et sans justification écrite.
- Ne pas exposer cet export à un rôle autre qu'admin authentifié.

---

## L9 — Tracking public sécurisé

**Statut : PARTIELLEMENT FERMÉ — L9-A corrigé par auth obligatoire ; L9-B ouvert via issue GitHub #9**

**Priorité : Haute — sécurité + UX tracking**

### Clarification 2026-06-23

Ne pas confondre deux sujets distincts :

- **L9-A — Broken Access Control / accès anonyme par `order_no` seul** : corrigé par le hotfix `d39e6be`, qui rend `GET /track-order/{orderNo}` obligatoirement authentifié via `middleware(['auth', 'module:food'])`. Cette mesure coupe l'exposition directe de PII par simple connaissance ou énumération d'un `order_no`.
- **L9-B — Tracking public sécurisé par token signé non devinable** : reste ouvert et suivi par l'issue GitHub #9. Objectif : restaurer une UX de suivi invité sans réintroduire l'accès public par `order_no` brut.

La formulation historique ci-dessous décrivait l'état initial avant hotfix. Elle est conservée pour traçabilité, mais ne doit plus être lue comme une faille active si `track.order` reste bien protégé par `auth + module:food`.

### Problème constaté (historique, avant hotfix L9-A)
Le benchmark présente ceci comme une fonctionnalité à **ajouter** ("rendre la page accessible publiquement avec token URL"). **Vérification du code initial : la page était déjà accessible sans authentification — et c'était un défaut de contrôle d'accès, pas une fonctionnalité manquante.**

Détail historique vérifié avant correction :
- Route `track-order/{orderNo?}` : groupe enveloppant avec middleware `[ResolveSiteContext::class]` uniquement — **aucun middleware `auth`**.
- Contrôleur `CustomerOrderController::trackOrder()` : la vérification de propriété était conditionnelle — `if (auth()->check() && (int) $order->user_id !== (int) auth()->id()) { abort(403); }`. Si l'utilisateur n'était PAS connecté (`auth()->check()` faux), cette vérification était court-circuitée.

### Impact métier
L9-A exposait des données personnelles clients (nom, téléphone, adresse, commande, paiement) à toute personne devinant/énumérant un `order_no`. Ce risque direct est corrigé par auth obligatoire. Le risque restant est désormais UX/produit : le client invité ou la session expirée ne disposent pas encore d'un lien public sécurisé par token signé.

### Fichiers probablement concernés pour L9-B
- `app/Http/Controllers/CustomerOrderController.php` (validation du token avant exposition de données)
- `routes/web.php` (route publique tokenisée additionnelle ou adaptation contrôlée de `track.order`)
- `orders` : éventuel champ `tracking_token` / `tracking_token_expires_at` si l'option colonne DB est retenue
- Notifications SMS/e-mail/page de remerciement : génération et diffusion du lien tokenisé

### Critères d'acceptation L9-B
- Lien de suivi invité fonctionnel sans compte, via token signé/non devinable uniquement.
- `order_no` seul, sans session propriétaire et sans token valide, ne donne jamais accès aux données.
- Utilisateur connecté propriétaire continue d'accéder au tracking via le chemin authentifié.
- Aucune régression sur le hotfix L9-A : `track.order` ne doit pas redevenir public par `order_no` brut.
- Le token doit être révocable ou expirable si le choix produit le demande.

### Tests requis
- Test de sécurité : requête anonyme sur `/track-order/{orderNo}` sans token → 302/401/403, jamais 200 avec PII.
- Test : utilisateur authentifié propriétaire de la commande → accès toujours fonctionnel.
- Test : token valide → accès invité autorisé selon périmètre défini.
- Test : token invalide/expiré → refus.
- Test : `order_no` seul ne suffit jamais.

### Dépendances
Décision produit nécessaire sur la forme du token : URL signée Laravel temporaire, colonne `tracking_token`, route dédiée `/t/{token}`, durée de vie, révocation, et contenu exact visible en mode invité.

### Interdictions spécifiques
- Ne pas retirer le hotfix auth obligatoire tant que L9-B n'est pas entièrement conçu, testé et validé.
- Ne pas exposer `order_no` comme secret de tracking dans SMS/e-mail.
- Ne pas mélanger L9-B avec L2 timestamps ou refactor de la vue `track_order.blade.php`.
- Ne pas réintroduire d'accès anonyme aux PII sans token valide.

---

## Tableau de synthèse priorisée

| # | Lacune | Statut | Priorité réelle (post-vérification) | Écart vs benchmark | Dépendances |
|---|---|---|---|---|---|
| L1 | `cash_collection_status` non mis à jour | **FERMÉ (2026-06-23)** — commits `b31d682` + `65d4d8f`, issue #3 close `completed` | Haute | Confirmé conforme au benchmark | Aucune. Suivi UI résiduel → issue #12 |
| L9 | Tracking public sécurisé | **PARTIELLEMENT FERMÉ** — L9-A auth obligatoire corrigé ; L9-B token signé ouvert via issue #9 | **Haute (sécurité + UX)** | Reclassé : faille directe corrigée ; feature tokenisée encore à concevoir | Décision produit sur token signé |
| L5 | Horaires d'ouverture jamais appliqués au checkout | Ouvert | Haute | Reclassé : le module `working_hours` existe déjà, seule l'application au checkout manque | Décision produit (comportement par défaut) |
| L2 | Pas de timestamps visibles côté client | Ouvert | Moyenne | Confirmé conforme | Aucune |
| L3 | Pas de badge cash dans l'app livreur | Ouvert — débloqué | Moyenne | Confirmé conforme | Dépendait de L1 (fermé) — **débloqué**, non commencé |
| L4 | Versioning API incohérent (food hors `v1`) | Ouvert | Moyenne | Reclassé : versioning partiellement existant (colis/courier), pas absent | Décision produit (calendrier dépréciation) |
| L6 | KPIs admin temps réel | Ouvert | Basse | Fortement revu à la baisse : KPIs déjà riches via `DashboardMetricsService`, gap résiduel non confirmé | Audit préalable requis |
| L7 | Rayon auto-assign fixe | Ouvert | Basse | Précisé : pondération distance déjà existante, seul le réélargissement progressif manque | Aucune |
| L8 | Pas d'export CSV commandes | Ouvert | Basse | Confirmé conforme | Aucune |

**Hors liste benchmark, ouvert en parallèle :** issue #12 (`feat(admin): interface de suivi des statuts de collecte cash`) — créée le 2026-06-23 comme suite UI de L1, pas une lacune du benchmark original.

---

## Rappel final

Ce document est une **roadmap exploitable**, pas un plan d'exécution validé. Conformément aux règles du projet, **aucun item ci-dessus ne doit être codé sans `/PRD && /plan` dédié et validation explicite, item par item**. Les corrections apportées par rapport au benchmark original (L4, L5, L6, L7, L9) reflètent l'état réel du code vérifié le 2026-06-19 et les mises à jour documentaires du 2026-06-23, sans modifier le fichier `docs/benchmark-github-bantudelice.md`, qui reste inchangé.
