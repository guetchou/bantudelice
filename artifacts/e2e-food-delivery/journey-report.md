# E2E food delivery journey report

Date: 2026-06-27  
Lab: `/tmp/bantudelice-food-e2e-lab2`  
URL: `http://127.0.0.1:8015`

## Parcours exécuté

| Heure approx. | Acteur | URL | Action | Statut affiché/API | Preuve |
| --- | --- | --- | --- | --- | --- |
| 10:05 | Client | `/checkout` | Commande cash soumise | `pending_restaurant_acceptance` | `13-client-order-submitted.png` |
| 10:13 | Client | `/checkout` | Commande principale soumise | `TD-20260627-5317` | `17-client-second-order-submitted.png` |
| 10:14 | Restaurant | `/restaurant/all_orders` | Commande reçue | Nouvelle | `18-restaurant-second-order-list.png` |
| 10:14 | Restaurant | `/restaurant/all_orders` | Acceptation | `in_kitchen`, cash `pending` | `19-restaurant-second-accepted.png` |
| 10:15 | Restaurant | `/restaurant/kitchen` | Cuisine | En préparation | `20-kitchen-preparing.png` |
| 10:15 | Restaurant | `/restaurant/kitchen` | Marquée prête | `ready_for_pickup` | `21-order-ready.png` |
| 10:16 | Dispatch | Artisan | Assignation | `ASSIGNED`, `driver_assigned` | `22-driver-assigned.png` |
| 10:20 | Livreur | `/driver/deliveries` | Retrait confirmé | `PICKED_UP`, `picked_up` | `25-driver-picked-up.png` |
| 10:24 | Livreur | `/driver/deliveries` | Départ livraison | `ON_THE_WAY`, `out_for_delivery` | `29-driver-onway.png` |
| 10:26 | Livreur | `/driver/location` | GPS publié | Position `-4.27,15.26` | `30-client-live-tracking.png` |
| 10:28 | Livreur | `/driver/deliveries` | OTP correct | `DELIVERED`, cash `collected`, paid | `33-delivery-confirmed.png` |
| 10:30 | Client | `/track-order/.../confirm` | Réception confirmée | `closed` | `35-order-closed.png` |

## Résultat final

`php artisan food:audit-integrity --json`

```json
{
  "status": "clean",
  "violations_count": 0,
  "violations": []
}
```

## Captures

Les captures sont dans `artifacts/e2e-food-delivery/`.

