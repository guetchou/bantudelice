# Architecture du Module Colis

## Arborescence Proposée

```text
app/
├── Domain/
│   └── Colis/
│       ├── Enums/
│       │   └── ShipmentStatus.php
│       ├── Models/
│       │   ├── Shipment.php
│       │   ├── ShipmentAddress.php
│       │   ├── ShipmentEvent.php
│       │   └── ShipmentProof.php
│       └── Services/
│           ├── ShipmentPricingService.php
│           ├── ShipmentStateMachine.php
│           └── TrackingNumberService.php
├── Http/
│   └── Controllers/
│       └── Api/
│           └── V1/
│               ├── Colis/
│               │   ├── ShipmentController.php
│               │   ├── QuoteController.php
│               │   └── TrackingController.php
│               ├── Courier/
│               │   └── CourierShipmentController.php
│               └── Admin/
│                   └── AdminShipmentController.php
├── Http/
│   └── Requests/
│       └── Colis/
│           ├── CreateShipmentRequest.php
│           └── ...
└── Policies/
    └── ShipmentPolicy.php
```

## Namespaces
- Logiciel métier : `App\Domain\Colis`
- Controllers : `App\Http\Controllers\Api\V1\Colis` (et `Courier`, `Admin`)
- Requests : `App\Http\Requests\Colis`

## Conventions de Communication
- **API Response** : JSON standard Laravel.
- **Exceptions** : Custom Business Exceptions pour les erreurs de transition de statut ou de tarification.
- **Events** : Utilisation des events Eloquent ou dispatch manuel pour le tracking.
