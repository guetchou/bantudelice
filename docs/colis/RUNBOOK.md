# Runbook de Déploiement - Module Colis

## 1. Pré-requis Infrastructure
- **PHP** : 8.1+ (avec extensions bcmath, gd, json, uuid).
- **Redis** : Recommandé pour les files d'attente (Queues).
- **Stockage** : Configurer un disque `private` dans `config/filesystems.php` pour les preuves de livraison.

## 2. Étapes de Déploiement (Ordre Critique)

### A. Migrations
Exécuter les migrations pour créer les tables du module :
```bash
# Socle Colis
php artisan migrate --force --path=database/migrations/2025_12_18_013955_create_shipments_table.php
php artisan migrate --force --path=database/migrations/2025_12_18_013956_create_shipment_addresses_table.php
php artisan migrate --force --path=database/migrations/2025_12_18_013957_create_shipment_events_table.php
php artisan migrate --force --path=database/migrations/2025_12_18_013958_create_shipment_proofs_table.php

# Points Relais
php artisan migrate --force --path=database/migrations/2025_12_18_053000_create_relay_points_table.php

# Système de Paiement (Dépendances)
php artisan migrate --force --path=database/migrations/2025_12_05_120000_create_payments_table.php
php artisan migrate --force --path=database/migrations/2025_12_18_061410_add_shipment_id_to_payments_table.php
```

### B. Configuration de Stockage
Vérifier que le lien symbolique pour le stockage public est créé :
```bash
php artisan storage:link
```
S'assurer que le dossier `storage/app/shipments/proofs` est créé et possède les droits d'écriture pour l'utilisateur `www-data`. Le disque `private` doit être configuré pour pointer vers ce dossier si nécessaire.

### C. Queues (Background Jobs)
Le module utilise les queues pour les notifications. Lancer un worker dédié ou s'assurer que le worker existant couvre la file par défaut :
```bash
php artisan queue:work --queue=default,notifications
```

## 3. Accès aux Interfaces

### Backoffice Admin
- **Lien** : `/admin/colis` (Menu latéral "Colis (Livraison)")
- **Points Relais** : `/admin/colis/relay-points`
- **Actions** : Attribution manuelle, suivi détaillé, preuve de livraison (OTP/Photo).

### Espace Client (Web)
- **Lien** : `/colis/create` (Bouton "COLIS" dans la barre de navigation)
- **Actions** : Création d'envoi, calcul de devis temps réel, suivi personnel.

### API (Mobile / Courier)
- **Documentation** : `docs/colis/OPENAPI.yaml`
- **Authentification** : Token Sanctum requis pour la plupart des endpoints.

## 4. Maintenance et Observabilité
- **Logs** : Surveiller `storage/logs/laravel.log` pour les erreurs de transition (ShipmentStateMachine).
- **Audit** : La table `shipment_events` sert de journal d'audit métier. En cas de litige, consulter les événements pour un `shipment_id` donné.
- **Nettoyage** : Pas de nettoyage automatique prévu pour le MVP. Les preuves (photos) peuvent saturer le disque à terme ; prévoir une politique de rétention.

## 5. Rollback
En cas de problème majeur, le rollback des tables se fait via :
```bash
php artisan migrate:rollback --step=6
```
*Attention : Cela supprimera toutes les données de livraison de colis.*
