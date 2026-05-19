# Guide Développeur BantuDelice

## 1. Objectif

Ce document est le point d'entrée unique pour comprendre, exécuter et modifier le projet sans se perdre.

## 2. Vue d'ensemble

BantuDelice est une application Laravel monolithique qui sert plusieurs flux métier:
- `food`: restaurants, panier, checkout, commandes, retrait et livraison
- `transport`: estimation, réservation taxi, dispatch, tracking live, paiement
- `colis`: création d'envoi, tracking, paiement, transition d'état
- `payments`: MTN MoMo, Airtel Money, PayPal, réconciliation

Répertoire racine en production:
- `/opt/bantudelice`

## 3. Stack technique

- PHP / Laravel
- Blade côté frontend
- MySQL/MariaDB
- Jobs / queues Laravel
- intégrations externes: MTN MoMo, Airtel, PayPal, géolocalisation, SMS

## 4. Répertoires à connaître

- `app/`
  - logique métier, services, contrôleurs, modèles
- `app/Domain/Transport/`
  - domaine transport
- `app/Domain/Colis/`
  - domaine colis
- `app/Services/`
  - services partagés, paiements, réconciliation, notifications
- `resources/views/frontend/`
  - UI publique et pages client
- `resources/views/driver/`
  - UI chauffeur / livreur
- `resources/views/admin/`
  - back-office
- `routes/web.php`
  - routes web
- `routes/api.php`
  - routes API
- `config/`
  - configuration applicative
- `database/migrations/`
  - schéma

## 5. Fichiers critiques

### Paiements

- `app/Services/PaymentService.php`
- `app/Services/MobileMoneyService.php`
- `app/Services/PaymentReconciliationService.php`
- `app/Http/Controllers/api/PaymentController.php`
- `config/external-services.php`

### Food

- `app/Services/CheckoutService.php`
- `app/Http/Controllers/IndexController.php`
- `resources/views/frontend/checkout.blade.php`
- `public/js/checkout.js`

### Transport

- `app/Domain/Transport/Services/TransportService.php`
- `app/Http/Controllers/api/Transport/TransportBookingController.php`
- `app/Http/Controllers/api/Transport/DriverTransportController.php`
- `resources/views/frontend/transport/taxi.blade.php`
- `resources/views/frontend/transport/booking_detail.blade.php`
- `resources/views/driver/transport/index.blade.php`

### Colis

- `app/Domain/Colis/Services/ShipmentPaymentService.php`
- `app/Http/Controllers/Api/V1/Colis/ShipmentController.php`
- `app/Http/Controllers/Api/V1/Colis/TrackingController.php`
- `resources/views/frontend/colis/show.blade.php`
- `resources/views/frontend/colis/track_public.blade.php`

### Header / shell frontend

- `resources/views/frontend/layouts/app-modern.blade.php`
- `resources/views/frontend/layouts/app.blade.php`

## 6. Flux métier à comprendre en priorité

### 6.1 Paiement MoMo

Séquence:
1. création de ligne `payments`
2. appel `PaymentService`
3. appel `MobileMoneyService`
4. `requesttopay`
5. polling ou callback
6. réconciliation provider
7. mise à jour du module métier concerné:
   - commande food
   - réservation transport
   - colis

Point sensible:
- pour MTN, le montant doit partir normalisé pour éviter les erreurs provider

### 6.2 Transport

Séquence:
1. estimation
2. création booking
3. assignation chauffeur
4. tracking live
5. progression de statut
6. paiement si non cash

Statuts typiques:
- `requested`
- `assigned`
- `driver_arriving`
- `picked_up`
- `in_progress`
- `completed`
- `paid`

### 6.3 Colis

Séquence:
1. création shipment
2. adresses pickup/dropoff
3. paiement ou COD
4. transitions du state machine
5. tracking public / privé

## 7. Points d'attention avant toute modification

- ne pas casser les routes historiques encore consommées côté frontend
- ne pas supposer qu'un callback provider arrivera toujours
- toujours prévoir polling + réconciliation
- ne pas introduire de nouvelles conventions de variables `.env` sans passer par `config/`
- faire attention aux transitions d'état: transport et colis ont des workflows métier
- le projet contient encore des couches anciennes et des couches récentes: lire avant de refactorer

## 8. Commandes utiles

Depuis `/opt/bantudelice`:

```bash
php artisan optimize:clear
php artisan route:list
php artisan migrate
php artisan queue:work
php -l path/to/file.php
```

## 9. Déploiement minimal après modification

1. pousser les fichiers
2. vérifier la syntaxe PHP des fichiers modifiés
3. vider les caches:

```bash
php artisan optimize:clear
```

4. vérifier la route ou la page touchée
5. si paiement: tester création + polling + réconciliation

## 10. Où regarder quand quelque chose casse

### Paiement bloqué

- `app/Services/MobileMoneyService.php`
- `app/Services/PaymentReconciliationService.php`
- `app/Http/Controllers/api/PaymentController.php`

### UI de suivi non mise à jour

- vue Blade concernée
- endpoint de polling
- champ `status` ou `payment_status` réellement renvoyé

### Workflow incohérent

- service métier du domaine
- transitions d'état
- jobs / notifications lancés après transition

## 11. Documentation complémentaire conservée

- `docs/colis/`
  - documentation spécifique module colis
- `docs/INCIDENT_2026-03-19_DATABASE_OUTAGE.md`
  - incident base de données et contexte technique

## 12. Règle de travail recommandée

Pour toute intervention:
1. identifier le module touché
2. lire le service métier central
3. lire la vue ou le contrôleur qui l'appelle
4. vérifier les statuts en base
5. corriger d'abord le backend, puis l'affichage
6. finir par un test réel ou un polling réel si le flux implique un provider
