# Inventaire Food Delivery (BantuDelice)

## Modèles Core
- `App\Restaurant`: Gestion des établissements.
- `App\Product`: Articles vendus par les restaurants.
- `App\Order`: Commandes clients.
- `App\Driver`: Livreurs/Chauffeurs.
- `App\Delivery`: Gestion de l'état de livraison.
- `App\Payment`: Transactions financières.
- `App\Rating`: Avis et notes.

## Services Réutilisables
- `App\Services\PaymentService`: Pipeline de paiement.
- `App\Services\NotificationService`: Push, SMS, Email.
- `App\Services\GeolocationService`: Calculs de distance, ETA.
- `App\Services\DispatchService`: Assignation automatique des chauffeurs.
- `App\Services\LoyaltyService`: Points de fidélité.

## Routes Clés
- `api/v1/orders`: CRUD commandes client.
- `api/v1/driver/deliveries`: Gestion côté livreur.
- `admin/orders`: Back-office.

## Tables DB
- `restaurants`, `products`, `orders`, `drivers`, `deliveries`, `payments`, `ratings`, `vouchers`.

## Tracking & Temps Réel
- `App\DriverLocation`: Historique des positions GPS.
- WebSockets (Pusher/Laravel Echo) pour le suivi de commande (canal `order.{id}`).

