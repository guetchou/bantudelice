# Rapport de Pré-Audit : Module Colis (BantuDelice)

## 1. Conventions Détectées

### Naming & Namespaces
- **Modèles** : Principalement dans `app/` (`User`, `Order`, `Driver`). Le module Colis commence à utiliser `app/Domain/Colis/Models`.
- **Controllers** : 
    - Anciens : `app/Http/Controllers/api` (minuscule).
    - Nouveaux/Organisés : `app/Http/Controllers/Api/V1` (Majuscule).
- **Services** : Situés dans `app/Services/`. Le module Colis propose `app/Domain/Colis/Services`.
- **Database** : Utilisation de Snake Case pour les colonnes. Tables au pluriel.

### Structure
- Le projet migre d'une structure monolithique simple vers une approche par Domaine (`app/Domain`).
- Utilisation intensive de Services pour la logique métier (`app/Services/PaymentService.php`, `GeolocationService.php`).

## 2. Points d'Intégration

### Authentification & Rôles
- **Sanctum & Passport** : Présents dans `composer.json`.
- **Guards** : `web` et `api` configurés dans `config/auth.php`.
- **Modèles** : 
    - `App\User` : Client.
    - `App\Driver` : Coursier.
    - `App\Admin` : Administrateur.
- **Preuve** : `config/auth.php` définit le provider `users` sur `App\User`. `App\Driver` implémente `Authenticatable`.

### Paiement
- Système existant avec `App\Payment`.
- Support Mobile Money via `App\Services\MobileMoneyService`.
- **Intégration** : Le module Colis devra se lier à `payments` via une relation ou un service partagé.

### Notifications
- Utilisation de FCM (Firebase Cloud Messaging).
- **Preuve** : `App\Services\NotificationService` et code CURL direct dans `OrderController`.

### Stockage
- Utilisation du système de fichiers Laravel (`config/filesystems.php`).
- Besoin pour le module Colis : Stockage privé pour les preuves de livraison (signatures/photos).

## 3. Risques de Collision

- **Routes** : Risque de conflit si `/api/v1/colis` n'est pas préfixé correctement ou si des noms de routes génériques sont utilisés.
- **Tables** : Les tables `shipments` et `orders` doivent rester distinctes pour éviter de polluer la logique de livraison de repas (Food) avec celle des colis.
- **Events** : Attention aux listeners globaux sur les commandes qui pourraient se déclencher par erreur sur les colis.

## 4. Décision d'Architecture

- **Namespace** : `App\Domain\Colis` pour la logique métier (Models, Services, Enums).
- **API** : `App\Http\Controllers\Api\V1\Colis` pour respecter la nouvelle convention de versioning.
- **Data Integrity** : Utilisation de UUID pour les `shipments` pour éviter la prédictibilité et faciliter les futurs imports/syncs (justification : besoin de tracking number unique et sécurisé).
- **State Machine** : Centralisation des changements de statut dans un service dédié pour garantir l'intégrité des transitions (interdiction de passer de 'created' à 'delivered' directement).

---
*Généré par Lead Developer AI le 2025-12-18*
