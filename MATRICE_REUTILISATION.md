# Matrice de Réutilisation pour le Module Transport

## Ce qui est Copié/Adapté (Clone & Rename)
- **Modèle de Commande -> TransportRequest/Booking**: La structure de `Order` sera adaptée pour `TransportRequest` (taxi/covoiturage/location).
- **Modèle de Restaurant -> TransportVehicle (pour Rental)**: Les véhicules de location se comportent un peu comme des restaurants (inventaire, disponibilité).
- **StateMachine (de Colis)**: Réutiliser le pattern FSM de `app/Domain/Colis/Services/ShipmentStateMachine.php`.

## Ce qui est Factorisé en "Shared"
- **Paiements**: Utilisation directe de `App\Services\PaymentService`.
- **Notifications**: Utilisation de `App\Services\NotificationService`.
- **Tracking GPS**: Utilisation de `App\DriverLocation` et `App\Services\GeolocationService`.
- **Fidélité**: Intégration avec `App\Services\LoyaltyService`.

## Ce qui est Nouveau (Spécifique Transport)
- **Modèle de Covoiturage**: Gestion des trajets (Itinéraire, Places disponibles).
- **Règles de Tarification Transport**: Tarification au km/minute, surge pricing pour taxi.
- **Calendrier de Disponibilité**: Pour la location de voitures.

## Tables à Créer
- `transport_requests`
- `transport_bookings`
- `transport_rides` (pour covoiturage)
- `transport_vehicles`
- `transport_pricing_rules`

