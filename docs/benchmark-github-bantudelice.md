# Benchmark GitHub — BantuDelice

**Date :** 2026-06-19  
**Auteur :** Audit Claude (session technique)  
**Objectif :** Comparer l'architecture BantuDelice à des projets open source proches pour identifier les bonnes pratiques, lacunes et idées réutilisables — sans copier de code.

---

## Périmètre et règles d'usage

- Ces dépôts sont des **références de comparaison**, non des bases de code à importer.
- Avant tout réemploi d'un extrait : vérifier la licence (MIT / Apache 2.0 / GPL / propriétaire).
- Ne jamais introduire un package externe sans justification technique + audit de licence.
- Ne jamais conclure qu'un projet est « meilleur » sans preuve technique mesurable.

---

## Module 1 — Food Delivery multi-vendeur (Laravel)

### Référence principale : `MuhammadZulhusni/Multi-Restaurant-Food-Ordering`

- **Lien :** https://github.com/MuhammadZulhusni/Multi-Restaurant-Food-Ordering
- **Stack :** Laravel, MySQL, Blade, Bootstrap
- **Modules présents :** Rôles Client / Restaurant / Super-Admin, catalogue multi-restaurant, panier, commandes
- **Qualité apparente :** Moyenne — structure MVC classique sans architecture DDD
- **Activité :** Active (2024-2025)
- **Idées réutilisables :**
  - Séparation claire des guards middleware par rôle (customer / restaurant / admin)
  - Interface restaurant minimal : accepter / refuser commande avec un seul bouton
- **Risques :** Pas de machine d'états commande documentée, pas de paiement différé, no tests
- **Ne pas copier :** L'absence de state machine — BantuDelice est plus avancé sur ce point

### Référence secondaire : `enatega/food-delivery-multivendor`

- **Lien :** https://github.com/enatega/food-delivery-multivendor
- **Stack :** React Native (Expo), React (admin), GraphQL, Node.js, MongoDB
- **Modules présents :** App client, app livreur, dashboard admin, multi-restaurant
- **Qualité apparente :** Haute — code review actif, CI, tests, architecture modulaire
- **Activité :** Très active (stars 10 k+, PRs régulières)
- **Fichiers intéressants :**
  - Architecture order state machine : `ORDER_STATUSES = [PENDING, ACCEPTED, ASSIGNED, PICKED_UP, DELIVERED, CANCELLED]`
  - Notifications push par événement de transition (pattern Event → Listener → FCM)
- **Idées réutilisables :**
  - Modèle d'état simplifié côté API : chaque transition est un endpoint dédié (`/accept`, `/assign-rider`, `/pickup`, `/deliver`) — évite les transitions arbitraires via un champ générique
  - Push notification granulaire par état : le client reçoit une notification à chaque étape, pas seulement au checkout
- **Risques :** Backend API propriétaire (licence non-libre), stack incompatible avec PHP/Laravel
- **Ne pas copier :** La stack Node/MongoDB — BantuDelice est PHP/MySQL, migration injustifiée

### Référence `lrdgz/Food-Ordering-Multi-Vendor`

- **Lien :** https://github.com/lrdgz/Food-Ordering-Multi-Vendor
- **Stack :** Laravel + Flutter
- **Modules présents :** App client Flutter, backend Laravel, admin panel
- **Qualité apparente :** Basse (code demo, pas de tests)
- **Idées réutilisables :** Aucune — niveau démonstration scolaire

---

## Module 2 — Application livreur (Driver App)

### Référence : `BlondelSeumo/Delivery-Boy-for-Groceries-Foods-Pharmacies-Stores-Flutter-App`

- **Lien :** https://github.com/BlondelSeumo/Delivery-Boy-for-Groceries-Foods-Pharmacies-Stores-Flutter-App
- **Stack :** Flutter (Android/iOS) + PHP/Laravel backend
- **Modules présents :** Acceptation livraison, mise à jour statut, tracking GPS, historique
- **Qualité apparente :** Moyenne — code structuré, quelques patterns MVC
- **Idées réutilisables :**
  - Écran livreur : liste des livraisons pending → tap pour accepter → navigation GPS → swipe pour confirmer livraison
  - Statuts livreur : `IDLE → ON_THE_WAY → PICKED_UP → DELIVERED` + refus possible avec raison
  - Badge "cash à collecter" affiché sur la livraison quand `payment_method=cash` — bonne UX pour éviter oublis
- **Ce que BantuDelice devrait implémenter :** Badge visuel "Espèces à collecter" sur l'interface livreur (actuellement, `cash_collection_status=pending_collection` n'est pas mis en évidence dans le dashboard livreur)
- **Risques :** Licence incertaine (vérifier avant toute réutilisation)
- **Ne pas copier :** Le framework Flutter — si une app mobile BantuDelice est prévue, React Native ou PWA serait plus cohérent avec l'écosystème existant

### Référence : `BlondelSeumo/Flutter-App-PHP-Laravel-Admin-Panel`

- **Lien :** https://github.com/BlondelSeumo/Flutter-App-PHP-Laravel-Admin-Panel
- **Stack :** Flutter + Laravel admin panel
- **Modules présents :** App client, app livreur, dashboard restaurant, admin central
- **Idées réutilisables :**
  - Pattern "commission par commande" : calcul restaurant_commission + admin_commission automatisé via hook order-completed — BantuDelice a ces champs mais le calcul est manuel
  - Rapport de performance livreur : temps moyen de livraison, taux d'acceptation, revenus

---

## Module 3 — Tracking commande / livraison temps réel

### Référence : `gkalmoukis/delivery-tracking-app`

- **Lien :** https://github.com/gkalmoukis/delivery-tracking-app
- **Stack :** Laravel, Bootstrap, Leaflet.js
- **Modules présents :** Tracking livraison par numéro de commande, carte Leaflet, statuts
- **Qualité apparente :** Bonne — clean, bien commenté, Leaflet intégré comme BantuDelice
- **Idées réutilisables :**
  - Rafraîchissement de la position livreur par polling côté client (setInterval 15 s) — simple, sans WebSocket
  - Breadcrumb de statuts visuels : chaque étape cochée avec timestamp ("Acceptée 14h32 · En cuisine 14h38 · En route 15:01")
- **Ce que BantuDelice devrait améliorer :** La page `/track-order/{orderNo}` n'affiche pas les timestamps de chaque transition — les ajouter dans `order_status_logs` et les rendre visibles au client
- **Risques :** Pas de WebSocket temps réel — pour BantuDelice avec Soketi/Pusher déjà en place, le polling serait une régression

### Référence : `jj15asmr/laravel-shipment-tracker-example`

- **Lien :** https://github.com/jj15asmr/laravel-shipment-tracker-example
- **Stack :** Laravel, Livewire
- **Idées réutilisables :** Progress bar linéaire (4 étapes) avec mise en évidence de l'étape courante — peut remplacer les icons statiques actuels de BantuDelice par un composant plus lisible

---

## Module 4 — Paiement différé / Mobile Money / Cash collection

### Contexte BantuDelice (acquis dans ce projet)

BantuDelice a implémenté un paiement différé à l'acceptation restaurant (Plan validé 2026-06-19) :
- `pending_restaurant_acceptance → accepted_awaiting_payment → confirmed → in_kitchen`
- MoMo MTN Congo (production, environnement réel)
- Cash : `payment_status=paid` posé dès l'acceptation, `cash_collection_status=pending_collection`

**Lacune identifiée :** `cash_collection_status` ne passe jamais automatiquement à `collected` après livraison.

### Référence : `HPWebdeveloper/laravel-pay-pocket`

- **Lien :** https://github.com/HPWebdeveloper/laravel-pay-pocket
- **Stack :** Laravel package (Composer)
- **Modules présents :** Wallets multiples, logs de transactions, débit/crédit
- **Idées réutilisables :**
  - Modèle "wallet restaurant" pour le suivi des commissions dues (au lieu de calculer à la volée)
  - Table `wallets_logs` pour audit financier — similaire à ce que BantuDelice devrait faire pour `cash_collection_status`
- **Risques :** Introduce une dépendance externe — à évaluer si BantuDelice a besoin d'un vrai système wallet

### Architecture UberEats (standard industriel — pas de code disponible)

Source : recherche technique (https://medium.com/@aliaftabk/how-uber-eats-architecture-works)

- **Order state machine :** `CREATED → CONFIRMED → PREPARING → PICKED_UP → DELIVERED`
- **Clé :** toute transition est **loggée avec timestamp** — BantuDelice fait cela via `order_status_logs`
- **Paiement :** capturé au moment de `CONFIRMED` (post-acceptation restaurant) — exactement le modèle implémenté dans BantuDelice ✓
- **Cash :** opérateurs terrain utilisent une app dédiée pour confirmer la collecte en espèces → met à jour `cash_collection_status` → déclenche virement restaurant
- **Idée concrète pour BantuDelice :** Ajouter un endpoint driver `POST /api/driver/deliveries/{id}/cash-collected` qui passe `cash_collection_status = collected` et horodate `cash_collected_at` — comble la lacune identifiée au CP13

---

## Module 5 — Taxi / Ride hailing

### Référence : `ptduy14/ride-hailing-service-web-app`

- **Lien :** https://github.com/ptduy14/ride-hailing-service-web-app
- **Stack :** Laravel, Pusher (temps réel)
- **Modules présents :** Mise en relation chauffeur/passager, notifications temps réel, calcul distance
- **Idées réutilisables :**
  - Algorithme de matching chauffeur : rayon progressif (1 km → 3 km → 5 km si aucun chauffeur accepte dans les 2 min) — applicable à `AutoAssignDeliveryJob` pour améliorer le taux d'acceptation
  - Écran "Recherche de chauffeur" animé côté client pendant la phase `accepted_awaiting_payment`
- **Risques :** Codebase principalement pédagogique, pas de tests de charge

### Référence : `SumanMCAMR/Ride-Sharing-App-Vue.js-Laravel-MySQL`

- **Lien :** https://github.com/SumanMCAMR/Ride-Sharing-App-Vue.js-Laravel-MySQL
- **Stack :** Vue.js, Laravel, MySQL, Pusher
- **Idées réutilisables :**
  - Gestion des annulations avec fenêtre de grâce (client peut annuler gratuitement dans les 2 premières minutes)
  - Calcul de tarif dynamique selon distance + heure de pointe — applicable au `delivery_charges` de BantuDelice

---

## Module 6 — Colis / Courier / Shipment tracking

### Référence : `mustafa-kamel/shipping-tracker`

- **Lien :** https://github.com/mustafa-kamel/shipping-tracker
- **Stack :** PHP/Laravel, MySQL
- **Modules présents :** CRUD produits, couriers, shipments, tracking public par numéro
- **Idées réutilisables :**
  - Tracking public sans authentification via numéro de suivi — BantuDelice expose déjà `/track-order/{orderNo}` mais uniquement pour le client connecté ; rendre cette page accessible publiquement (avec token URL) améliorerait l'expérience
  - Événements de tracking : chaque scan physique → `shipment_events` table (lieu, statut, timestamp) — applicable aux livraisons colis BantuDelice

### Référence : `masini4ka/logistics`

- **Lien :** https://github.com/masini4ka/logistics
- **Stack :** Laravel
- **Idées réutilisables :** Modèle de tarification colis par tranche de poids/volume — utile si BantuDelice développe la verticale colis (en cours selon l'écosystème)

---

## Module 7 — Marketplace services (type Fiverr / Thumbtack)

### Référence : `bagisto/bagisto`

- **Lien :** https://github.com/bagisto/bagisto
- **Stack :** Laravel, Vue.js — 14 000+ stars, très actif
- **Modules présents :** Multi-vendor, catalogue, panier, paiements (Stripe/PayPal), API complète
- **Qualité apparente :** Très haute — architecture modulaire, CI/CD, documentation complète, tests
- **Idées réutilisables :**
  - Système de commissions vendeur configurable (fixe + % par catégorie)
  - API versionnée (`/api/v1/`) avec documentation OpenAPI — BantuDelice n'a pas de versioning d'API
  - Gestion des retours/remboursements avec workflow d'approbation
- **Risques :** Codebase très large — réutiliser uniquement des patterns, pas le code
- **Licence :** MIT ✓

### Référence : `m-elewa/freelancers-market`

- **Lien :** https://github.com/m-elewa/freelancers-market
- **Stack :** Laravel
- **Idées réutilisables :**
  - Système de réputation (notes 1-5) avec modération admin — applicable aux avis restaurant BantuDelice
  - Middleware "profil complété" : bloquer les offres si le profil vendeur est incomplet

### Référence : `laraship/laravel-marketplace`

- **Lien :** https://github.com/laraship/laravel-marketplace
- **Idées réutilisables :** Payout automatique vendeur (virement restaurant) déclenché après confirmation livraison — comble la lacune `cash_collection_status`

---

## Module 8 — Dashboard admin / vendor

### Référence : `BlondelSeumo/Ecommerce-Solution-using-Laravel-Android-Apps`

- **Lien :** https://github.com/BlondelSeumo/Ecommerce-Solution-using-Laravel-Android-Apps
- **Stack :** Laravel (web) + Android (customer + driver)
- **Modules présents :** Dashboard admin central, app vendeur, app livreur, analytics
- **Idées réutilisables :**
  - Widget KPI dashboard : commandes du jour / CA / livreurs actifs / restaurants en ligne — BantuDelice a une page admin mais sans KPIs temps réel
  - Exportation CSV des commandes par plage de dates — fonctionnalité manquante côté admin BantuDelice
  - Alertes automatiques : restaurant sans acceptation depuis > 10 min → push admin

### Référence : `AhmedYahyaE/laravel-multi-vendor-e-commerce-application`

- **Lien :** https://github.com/AhmedYahyaE/laravel-multi-vendor-e-commerce-application
- **Stack :** Laravel + Passport
- **Idées réutilisables :**
  - Tableau de bord restaurant avec graphique CA hebdomadaire (Chart.js) — vendeur peut voir ses propres analytics sans accès admin
  - Gestion des horaires d'ouverture restaurant (`opening_hours` table) — BantuDelice n'a pas ce module, ce qui signifie qu'un restaurant peut recevoir des commandes même s'il est fermé

---

## Synthèse — Lacunes BantuDelice identifiées par comparaison

| # | Lacune | Priorité | Module concerné | Idée issue du benchmark |
|---|---|---|---|---|
| L1 | `cash_collection_status` jamais mis à jour automatiquement | Haute | Livraison | Endpoint driver `POST /cash-collected` (modèle UberEats) |
| L2 | Pas de timestamps visibles par étape côté client | Moyenne | Tracking | `order_status_logs` → affichage breadcrumb avec horaires |
| L3 | Pas de badge "espèces à collecter" dans l'app livreur | Moyenne | Driver | Badge BlondelSeumo driver app |
| L4 | Pas de versioning API (`/api/v1/`) | Moyenne | API | Pattern Bagisto |
| L5 | Pas d'horaires d'ouverture restaurant | Haute | Restaurant | Table `opening_hours` — commandes reçues même restaurant fermé |
| L6 | Dashboard admin sans KPIs temps réel | Basse | Admin | Pattern BlondelSeumo admin panel |
| L7 | Algo auto-assign livreur : rayon fixe uniquement | Basse | Dispatch | Rayon progressif (ptduy14 pattern) |
| L8 | Pas d'export CSV commandes | Basse | Admin | Pattern AhmedYahyaE |
| L9 | Tracking public sans auth (lien partageable) | Basse | Tracking | Pattern mustafa-kamel |

---

## Ce que BantuDelice fait MIEUX que les références comparées

| Point fort | Justification technique |
|---|---|
| Machine d'états commande | `FoodOrderStateMachineService` + gardes dures `guardInKitchenRequiresConfirmedAndPaid` — aucun repo PHP comparable n'a ce niveau de rigueur |
| Paiement différé à l'acceptation | Implémentation validée en production (2026-06-19) — aucun des repos Laravel comparés ne gère cela |
| Audit log de transitions | `order_status_logs` + `AuditLogService` — traçabilité complète des transitions |
| Séparation `business_status` / `payment_status` | Évite les couplages qui créent des bugs de state (fréquents dans les projets comparés) |
| Tests Feature end-to-end | `EndToEndOrderFlowTest` — couvre le flow complet checkout → delivered en DB de test |
| Checkout snapshot | `checkout_snapshot` persiste le panier pour pouvoir relancer le paiement post-acceptation |

---

## Sources

- [MuhammadZulhusni/Multi-Restaurant-Food-Ordering](https://github.com/MuhammadZulhusni/Multi-Restaurant-Food-Ordering)
- [enatega/food-delivery-multivendor](https://github.com/enatega/food-delivery-multivendor)
- [lrdgz/Food-Ordering-Multi-Vendor](https://github.com/lrdgz/Food-Ordering-Multi-Vendor)
- [BlondelSeumo/Flutter-App-PHP-Laravel-Admin-Panel](https://github.com/BlondelSeumo/Flutter-App-PHP-Laravel-Admin-Panel)
- [BlondelSeumo/Delivery-Boy-for-Groceries-Foods-Pharmacies-Stores-Flutter-App](https://github.com/BlondelSeumo/Delivery-Boy-for-Groceries-Foods-Pharmacies-Stores-Flutter-App)
- [BlondelSeumo/Ecommerce-Solution-using-Laravel-Android-Apps](https://github.com/BlondelSeumo/Ecommerce-Solution-using-Laravel-Android-Apps)
- [BlondelSeumo/Store-Delivery-Mobile-App-with-Admin-Panel](https://github.com/BlondelSeumo/Store-Delivery-Mobile-App-with-Admin-Panel)
- [gkalmoukis/delivery-tracking-app](https://github.com/gkalmoukis/delivery-tracking-app)
- [jj15asmr/laravel-shipment-tracker-example](https://github.com/jj15asmr/laravel-shipment-tracker-example)
- [masini4ka/logistics](https://github.com/masini4ka/logistics)
- [mustafa-kamel/shipping-tracker](https://github.com/mustafa-kamel/shipping-tracker)
- [HPWebdeveloper/laravel-pay-pocket](https://github.com/HPWebdeveloper/laravel-pay-pocket)
- [ptduy14/ride-hailing-service-web-app](https://github.com/ptduy14/ride-hailing-service-web-app)
- [SumanMCAMR/Ride-Sharing-App-Vue.js-Laravel-MySQL](https://github.com/SumanMCAMR/Ride-Sharing-App-Vue.js-Laravel-MySQL)
- [bagisto/bagisto](https://github.com/bagisto/bagisto)
- [m-elewa/freelancers-market](https://github.com/m-elewa/freelancers-market)
- [laraship/laravel-marketplace](https://github.com/laraship/laravel-marketplace)
- [AhmedYahyaE/laravel-multi-vendor-e-commerce-application](https://github.com/AhmedYahyaE/laravel-multi-vendor-e-commerce-application)
- [UberEats Architecture — Medium](https://medium.com/@aliaftabk/how-uber-eats-architecture-works-a-deep-dive-into-building-a-global-real-time-food-delivery-222dde27666f)
- [enatega.com architecture guide](https://enatega.com/food-delivery-app-architecture/)
