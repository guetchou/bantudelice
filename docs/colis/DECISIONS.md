# Décisions d'Architecture - Module Colis

## 1. Identifiants : BigInt vs UUID
**Décision** : `BigInt` (Primary Key) + `UUID` (Public/API identifier).
**Justification** : Performance des index SQL avec BigInt pour les relations internes, mais sécurité et non-prédictibilité via UUID pour les accès API. Le `tracking_number` reste le pivot métier.

## 2. Tracking Number
**Format** : `BD-CG-YYYYMM-XXXXX`
**Justification** : Lisible, identifie la marque (BD), le pays (CG), la période, et un compteur unique. Facilite le tri manuel et la recherche.

## 3. Stockage des Preuves
**Décision** : `Storage::disk('private')`.
**Justification** : Les photos de livraison et signatures contiennent des données sensibles (adresses, visages). L'accès doit se faire via des URLs signées (temporary URLs).

## 4. Machine à États (State Machine)
**Décision** : Implémentation manuelle via `ShipmentStateMachine` service.
**Justification** : Évite d'ajouter une dépendance lourde (Spatie State Machine) alors que les besoins sont spécifiques (log d'événements à chaque changement).

## 5. Files d'Attente (Queues)
**Décision** : Utilisation de `Redis` (déjà présent dans l'infra via `config/queue.php`).
**Justification** : Envoi de notifications, génération de PDFs de bordereau, et calculs lourds de tarification (si API externe) doivent être asynchrones.

## 6. Broadcasting
**Décision** : Polling au lieu de WebSockets pour le MVP.
**Justification** : Bien que `broadcasting.php` existe, l'infrastructure temps réel (Pusher/Soketi) n'est pas confirmée stable sur tous les environnements de prod. Le polling est plus robuste pour un démarrage au Congo.
