# Audit 6.5 - Modules taxi et colis

## Portee

Audit cible des briques existantes taxi/transport et colis dans:

- `routes/web.php`
- `routes/api.php`
- `app/Http/Controllers/TransportController.php`
- `app/Http/Controllers/api/Transport/*`
- `app/Http/Controllers/Api/V1/Colis/*`
- `app/Http/Controllers/admin/Transport/AdminTransportController.php`
- `app/Http/Controllers/admin/ColisController.php`
- `app/Domain/Transport/*`
- `app/Domain/Colis/*`
- `database/migrations/*transport*`
- `database/migrations/*shipment*`
- `resources/views/frontend/transport/*`
- `resources/views/frontend/colis/*`
- `resources/views/driver/transport/index.blade.php`
- `resources/views/layouts/app.blade.php`
- `config/bantudelice_modules.php`
- `app/Services/PaymentExperienceService.php`

## Conclusion rapide

- Le module transport est reel, structure et exploitable.
- Le module colis est reel, structure et exploitable.
- Les deux modules sont deja branches en:
  - routes web
  - routes API
  - vues frontend
  - dashboards/admin
  - paiement partage
  - activation par flags module
- Le couplage principal n'est pas la navigation publique seule.
- Le couplage principal passe par:
  - la table `payments`
  - `PaymentService` / `PaymentExperienceService`
  - le dashboard admin global
  - les badges et menus backoffice
  - les flags `module:colis` et `module:transport`
- Risque cle:
  - on peut isoler le branding plus facilement que la logique metier
  - mais toucher sans precaution a la navigation, aux labels ou aux activations module peut casser des points d'entree reels

## 1. Module transport / taxi

### Routes

Dans `routes/web.php`:

- catalogue public:
  - `GET /transport` -> redirect taxi
  - `GET /transport/taxi`
  - `GET /transport/carpool`
  - `GET /transport/rental`
  - `GET /transport/bus`
- client connecte:
  - `GET /transport/mes-reservations`
  - `GET /transport/booking/{id}`
- endpoints XHR:
  - `POST /transport/xhr/estimate`
  - `GET /transport/xhr/bookings`
  - `POST /transport/xhr/bookings`
  - `GET /transport/xhr/bookings/{id}`
  - `POST /transport/xhr/bookings/{id}/pay`
  - `POST /transport/xhr/driver/bookings/{id}/accept`
  - `POST /transport/xhr/driver/bookings/{id}/status`
  - `POST /transport/xhr/driver/bookings/{id}/location`
- chauffeur:
  - `GET /driver/transport`

Dans `routes/api.php`:

- `POST /api/v1/transport/estimate`
- `GET|POST /api/v1/transport/bookings`
- `GET /api/v1/transport/bookings/{id}`
- `POST /api/v1/transport/bookings/{id}/cancel`
- `POST /api/v1/transport/bookings/{id}/pay`
- driver:
  - `GET /api/v1/transport/driver/nearby`
  - `POST /api/v1/transport/driver/bookings/{id}/accept`
  - `POST /api/v1/transport/driver/bookings/{id}/status`
  - `POST /api/v1/transport/driver/bookings/{id}/location`

Constat:

- transport est branche en front web, API client et API chauffeur
- toutes les routes sont protegees par `module:transport`

### Controleurs

- `app/Http/Controllers/TransportController.php`
  - catalogue public
  - pages taxi/carpool/rental/bus
  - details reservation
  - dashboard chauffeur
- `app/Http/Controllers/api/Transport/TransportBookingController.php`
  - estimation
  - creation reservation
  - lecture reservation
  - paiement
- `app/Http/Controllers/api/Transport/DriverTransportController.php`
  - acceptation
  - mise a jour statut
  - geolocalisation chauffeur
- `app/Http/Controllers/admin/Transport/AdminTransportController.php`
  - dashboard
  - reservations
  - vehicules
  - tarification

### Modeles / domaine

- `app/Domain/Transport/Models/TransportBooking.php`
- `app/Domain/Transport/Models/TransportVehicle.php`
- `app/Domain/Transport/Models/TransportRide.php`
- `app/Domain/Transport/Models/TransportTrackingPoint.php`
- `app/Domain/Transport/Models/TransportPricingRule.php`
- `app/Domain/Transport/Enums/TransportType.php`
- `app/Domain/Transport/Enums/TransportStatus.php`
- `app/Domain/Transport/Services/TransportService.php`
- `app/Domain/Transport/Services/TransportNotificationService.php`
- `app/Domain/Transport/Services/TransportLogger.php`
- events:
  - `TransportRequestCreated`
  - `BookingAssigned`
  - `TransportTrackingUpdated`

Constat:

- transport est organise en domaine dedie
- ce n'est pas un simple bricolage Blade

### Migrations / tables

- `2025_12_18_115349_create_transport_tables.php`
  - `transport_vehicles`
  - `transport_pricing_rules`
  - `transport_bookings`
  - `transport_rides`
  - `transport_tracking_points`
- `2025_12_18_115957_add_transport_booking_id_to_payments_table.php`
- `2025_12_18_135240_add_active_transport_vehicle_to_drivers.php`
- `2025_12_18_134643_update_transport_vehicles_status_and_docs.php`
- `2026_03_20_210000_add_ops_fields_to_transport_bookings_table.php`

Constat:

- transport a son schema propre
- mais il est relie a `payments` et `drivers`

### Vues / composants

Frontend:

- `resources/views/frontend/transport/taxi.blade.php`
- `resources/views/frontend/transport/carpool.blade.php`
- `resources/views/frontend/transport/rental.blade.php`
- `resources/views/frontend/transport/bus.blade.php`
- `resources/views/frontend/transport/index.blade.php`
- `resources/views/frontend/transport/my_bookings.blade.php`
- `resources/views/frontend/transport/booking_detail.blade.php`

Chauffeur:

- `resources/views/driver/transport/index.blade.php`

Admin:

- `resources/views/admin/transport/*`

### Formulaires / interactions

- reservation taxi depuis la page publique
- paiement de reservation depuis `booking_detail`
- acceptation chauffeur
- changement de statut chauffeur
- publication de position chauffeur
- gestion vehicules et tarification en admin

### Paiement / checkout

Transport utilise:

- `app/Domain/Transport/Services/TransportService.php`
- `app/Services/PaymentService.php`
- `app/Services/PaymentExperienceService.php`
- `payments.transport_booking_id`
- `app/Jobs/HandleTransportPaymentCallbackJob.php`
- `app/Jobs/SendTransportStatusNotificationJob.php`

Constat:

- pas de checkout food reutilise tel quel
- mais forte dependance a la couche paiement partagee

### Menus visibles / admin

- menu public home moderne vers `transport.taxi`
- menu public layout vers `transport.index`
- menu admin `Trajets` avec:
  - dashboard
  - reservations
  - vehicules
  - tarification
- dashboard admin global compte les trajets actifs

### Qualification transport

| Critere | Qualification |
|---|---|
| Reellement operationnel | oui, fort |
| Partiellement branche | certaines verticales secondaires comme bus sont plus editoriales |
| Dormant | non |
| Couple fortement au food | non sur le coeur, oui via paiements/dashboard/driver |
| Facilement isolable cote branding | oui |
| Risque de regression si on touche la navigation | moyen a fort |

## 2. Module colis

### Routes

Dans `routes/web.php`:

- client connecte:
  - `GET /mes-colis`
  - `GET /mes-colis/{id}`
  - `POST /mes-colis/{id}/cancel`
  - `GET /colis/nouveau`
  - `POST /colis/shipments`
- public:
  - `GET /livraison-colis`
  - `GET /suivi-colis`

Dans `routes/api.php`:

- public/client:
  - `POST /api/v1/colis/quotes`
  - `GET /api/v1/colis/track/{tracking_number}`
  - `GET|POST /api/v1/colis/shipments`
  - `GET /api/v1/colis/shipments/{shipment}`
  - `POST /api/v1/colis/shipments/{shipment}/cancel`
  - `POST /api/v1/colis/shipments/{shipment}/payment`
  - `GET /api/v1/colis/shipments/{shipment}/payment-status`
- courier:
  - `GET /api/v1/courier/shipments/assigned`
  - `POST /api/v1/courier/shipments/{shipment}/events`
  - `POST /api/v1/courier/shipments/{shipment}/proofs`
  - `POST /api/v1/courier/shipments/{shipment}/deliver`
- admin:
  - `GET /api/v1/admin/colis/shipments`
  - `POST /api/v1/admin/colis/shipments/{shipment}/assign`
  - `POST /api/v1/admin/colis/shipments/{shipment}/auto-assign`
  - `POST /api/v1/admin/colis/shipments/{shipment}/status`

Constat:

- colis est branche en parcours public, client, coursier et admin
- toutes les routes sont protegees par `module:colis`

### Controleurs

- front via `IndexController`
  - `myShipments`
  - `showShipment`
  - `cancelShipment`
  - `createShipment`
  - `storeShipment`
  - `colisLanding`
  - `trackShipmentPublic`
- API:
  - `Api/V1/Colis/QuoteController`
  - `Api/V1/Colis/TrackingController`
  - `Api/V1/Colis/ShipmentController`
  - `Api/V1/Courier/CourierShipmentController`
  - `Api/V1/Admin/AdminShipmentController`
- admin web:
  - `app/Http/Controllers/admin/ColisController.php`

### Modeles / domaine

- `app/Domain/Colis/Models/Shipment.php`
- `ShipmentAddress`
- `ShipmentEvent`
- `ShipmentProof`
- `ShipmentIncident`
- `ShipmentAuditLog`
- `ShipmentReconciliation`
- `RelayPoint`
- `app/Domain/Colis/Enums/ShipmentStatus.php`
- services:
  - `ShipmentPricingService`
  - `ShipmentPaymentService`
  - `ShipmentStateMachine`
  - `ShipmentAssignmentService`
  - `ShipmentProofService`
  - `ShipmentNotificationService`

Constat:

- colis a lui aussi un domaine propre et mature

### Migrations / tables

- `2025_12_18_013955_create_shipments_table.php`
- `2025_12_18_013956_create_shipment_addresses_table.php`
- `2025_12_18_013957_create_shipment_events_table.php`
- `2025_12_18_013958_create_shipment_proofs_table.php`
- `2025_12_18_053000_create_relay_points_table.php`
- `2025_12_18_061410_add_shipment_id_to_payments_table.php`
- `2025_12_18_100000_create_shipment_audit_logs_table.php`
- `2025_12_18_150000_create_shipment_reconciliations_table.php`
- `2025_12_18_160000_create_shipment_incidents_table.php`
- `2026_03_20_203000_add_ops_fields_to_shipments_table.php`

Constat:

- colis a un schema complet:
  - vie du colis
  - adresses
  - preuves
  - incidents
  - reconciliation COD
  - lien paiement

### Vues / composants

Frontend:

- `resources/views/frontend/colis/landing.blade.php`
- `resources/views/frontend/colis/create.blade.php`
- `resources/views/frontend/colis/index.blade.php`
- `resources/views/frontend/colis/show.blade.php`
- `resources/views/frontend/colis/track_public.blade.php`

Admin:

- `resources/views/admin/colis/*`

### Formulaires / interactions

- devis / estimation
- creation d'envoi
- annulation
- suivi public
- paiement en ligne
- upload de preuves
- livraison + preuve de remise
- incident et reconciliation COD en admin

### Paiement / checkout

Colis utilise:

- `app/Domain/Colis/Services/ShipmentPaymentService.php`
- `payments.shipment_id`
- `PaymentService`
- `PaymentExperienceService`
- callback paiement partagee

Constat:

- colis n'utilise pas le checkout food
- mais partage integralement la couche paiement et la table `payments`

### Menus visibles / admin

- menu public home moderne et layout global
- landing publique dediee
- suivi public dedie
- menu admin `Colis` avec:
  - tous les colis
  - nouveaux colis
  - points relais
  - finance & COD
- dashboard admin global compte les colis actifs

### Qualification colis

| Critere | Qualification |
|---|---|
| Reellement operationnel | oui, fort |
| Partiellement branche | certains flux de contenu/marketing restent marques BantuDelice |
| Dormant | non |
| Couple fortement au food | non sur le coeur, oui via paiements/dashboard/driver/admin shell |
| Facilement isolable cote branding | oui |
| Risque de regression si on touche la navigation | moyen a fort |

## 3. Couplages critiques a ne pas casser

### Couplages transverses

- `config/bantudelice_modules.php`
  - active/desactive les verticales
- middlewares `module:colis` et `module:transport`
- `payments`
  - `shipment_id`
  - `transport_booking_id`
- `app/Services/PaymentExperienceService.php`
  - resolve `food` / `colis` / `transport`
- callback paiement partagee
- dashboard admin global
- shell admin unique `resources/views/layouts/app.blade.php`
- table `drivers`
  - chauffeurs transport
  - coursiers colis

### Impact produit

- isoler le branding public est faisable
- couper brutalement les liens ou menus sans reroutage clair peut rendre les modules invisibles alors qu'ils restent actifs
- toucher aux paiements, badges admin ou flags modules est plus risqué que toucher au wording

## 4. Statut global recommande

| Module | Statut reel | Commentaire |
|---|---|---|
| Transport/taxi | operationnel avec domaine propre | actif en public, XHR, API, chauffeur et admin |
| Colis | operationnel avec domaine propre | actif en public, API, coursier, admin et COD |
| Branding transport | facilement isolable | mais sans casser les routes actives |
| Branding colis | facilement isolable | mais sans casser landing, suivi et finance |

## 5. Risque de regression

### Faible

- retouche editoriale pure
- renommage de labels hors code metier

### Moyen

- masquer des entrees publiques sans verifier les redirections
- toucher au header/footer sans couvrir home + layout + landings dediees

### Fort

- toucher aux flags modules
- toucher a `payments`
- toucher aux callbacks paiement
- toucher au shell admin ou aux compteurs globaux
- toucher aux workflows chauffeur/coursier

## Conclusion

Taxi/transport et colis ne sont pas des restes legacy superficiels. Ce sont deux verticales reelles, branchees et soutenues par un domaine, des tables, des APIs, des vues et des ecrans admin. La bonne strategie reste donc:

1. isoler le branding public en priorite,
2. laisser les flux metier intacts,
3. ne pas casser les points d'entree reels,
4. traiter les couplages paiements/admin comme zones sensibles.
