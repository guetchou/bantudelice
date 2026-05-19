# Scénario de Recette E2E - Module Colis

## Scénario : Cycle de vie complet d'un colis

### 1. Préparation (Auth)
- Se connecter en tant que client (User ID 1).
- Se connecter en tant que coursier (Driver ID 1).
- Se connecter en tant qu'admin (Admin ID 1).

### 2. Création & Tarification
**Requête** : `POST /api/v1/colis/shipments`
**Payload** :
```json
{
    "weight_kg": 2,
    "service_level": "standard",
    "pickup_address": {
        "full_name": "Expéditeur Test",
        "phone": "060000000",
        "city": "Brazzaville",
        "district": "Poto-Poto",
        "address_line": "Avenue de la Paix"
    },
    "dropoff_address": {
        "full_name": "Destinataire Test",
        "phone": "050000000",
        "city": "Brazzaville",
        "district": "Bacongo",
        "address_line": "Rue Mbochi"
    }
}
```
**Résultat attendu** : Status 201, Colis créé avec `tracking_number`.

### 3. Assignation (Admin)
**Requête** : `POST /api/v1/admin/colis/shipments/{id}/assign`
**Payload** : `{"courier_id": 1}`
**Résultat attendu** : `assigned_courier_id` mis à jour, événement loggé.

### 4. Ramassage (Coursier)
**Requête** : `POST /api/v1/courier/shipments/{id}/events`
**Payload** : `{"status": "picked_up", "notes": "Colis récupéré chez l'expéditeur"}`
**Résultat attendu** : Statut passe à `picked_up`.

### 5. Transit & Livraison (Coursier)
- Upload preuve : `POST /api/v1/courier/shipments/{id}/proofs` (Type: photo).
- Livraison finale : `POST /api/v1/courier/shipments/{id}/deliver` (OTP: 123456).
**Résultat attendu** : Statut passe à `delivered`, `delivered_at` est renseigné.

### 6. Vérification Tracking Public
**Requête** : `GET /api/v1/colis/track/{tracking_number}`
**Résultat attendu** : Voir toute la timeline des événements.

