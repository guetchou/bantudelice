# Food delivery complete workflow

Workflow validé dans le lab local `http://127.0.0.1:8015` avec trois sessions Agent Browser isolées :

- Client : `bd-client`
- Restaurant : `bd-restaurant`
- Livreur : `bd-driver`

Commande principale validée : `TD-20260627-5317`.

États observés :

1. Client crée la commande depuis le menu restaurant.
2. Checkout livraison + adresse + repère confirmé + paiement cash.
3. Commande créée : `pending_restaurant_acceptance`, paiement `pending`, aucune livraison.
4. Restaurant reçoit et accepte.
5. Cash reste `payment_status=pending`, `cash_collection_status=pending_collection`.
6. Cuisine passe `in_kitchen`, puis `ready_for_pickup`.
7. Dispatch assigne un seul livreur : livraison `ASSIGNED`, commande `driver_assigned`.
8. Livreur récupère : livraison `PICKED_UP`, commande `picked_up`.
9. Livreur démarre : livraison `ON_THE_WAY`, commande `out_for_delivery`.
10. GPS publié depuis la session livreur via `/driver/location`; tracking client affiche “En route”, livreur, distances et ETA.
11. OTP incorrect refusé.
12. OTP correct accepté : livraison `DELIVERED`, commande `delivered`, cash `collected`, paiement `paid`.
13. Confirmation client : commande `closed`.
14. `php artisan food:audit-integrity --json` : clean.

Limites restantes :

- Carte Mapbox indisponible dans le lab sans `MAPBOX_PUBLIC_TOKEN`.
- Pas encore de statut dédié “driver_arrived_at_restaurant” dans l’UI livreur.
- Le statut dédié “arrivé au restaurant” et la proximité GPS correspondante restent à finaliser.
- Certains boutons dynamiques nécessitent une invocation JS dans Agent Browser alors que les formulaires/routes réels fonctionnent.
