# 01 — Architecture réelle

**Audit :** 2026-07-02  
**Code observé :** `main` ; PR #102 `faae109` analysée séparément, non fusionnée.

## Verdict

BantuDelice est un monolithe Laravel multi-domaine en transition. Food, Colis et Transport partagent `App\Payment`, mais leurs workflows, statuts et traitements cash restent distincts. Trois générations coexistent : contrôleurs historiques, services `Workflow*`/machines à états, puis GePay et registre financier V2.

## Cartographie

| Domaine | Modèles centraux | Services centraux | Événements/jobs | Tables principales |
|---|---|---|---|---|
| Food | `Order`, `Cart`, `Delivery`, `Restaurant`, `Payment` | `PlaceOrderService`, `WorkflowCheckoutService`, `WorkflowFoodOrderStateMachineService` | événements Food, `ProcessOrderJob`, `AutoAssignDeliveryJob`, notifications | `orders`, `carts`, `deliveries`, `payments` |
| Colis | `Shipment`, `ShipmentEvent`, `ShipmentProof`, `ShipmentReconciliation` | `ShipmentStateMachine`, `ShipmentPaymentService`, `ShipmentAssignmentService` | callbacks, OTP, notifications, COD | `shipments`, `shipment_*`, `payments` |
| Transport | `TransportBooking`, `TransportRide`, `TransportVehicle` | `TransportService`, notifications, tracking | demande, affectation, statut, présence, callback | `transport_*`, `payments` |
| Payments | `Payment` | `PaymentService`, `PaymentGatewayFactory`, `PaymentReconciliationService`, `MobileMoneyBridgeService` | `PaymentConfirmed`, jobs callback | `payments`, `financial_events` |
| GePay | `GePayClient`, `GePayTransaction`, `GePayWebhookEvent` | `GePayGateway`, `MtnMomoProvider`, signer, réconciliation | API V1, admin, commandes | `gepay_clients`, `gepay_transactions`, `gepay_webhook_events` |
| Finance V2 | `FinancialAccount`, `FinancialPostingBatch`, `FinancialPosting` | `LedgerPostingService`, `WithdrawalLedgerService`, miroir d’encaissement | listener `PaymentConfirmed` | `financial_accounts`, `financial_posting_batches`, `financial_postings` |

## Couches qui doublonnent un même besoin

- `CheckoutService` et `WorkflowCheckoutService`.
- `OrderAcceptanceService` et `WorkflowOrderAcceptanceService`.
- `FoodOrderStateMachineService` et sa sous-classe workflow.
- `orders.status`, `business_status`, `technical_status` et `delivery.status`.
- `financial_events`, registre V2 et futur ledger GePay.
- Paiement direct MTN/Airtel, `MobileMoneyBridgeService`, GePayAdapter et API GePay.
- Workers historiques et Horizon.

`AppServiceProvider` active les variantes workflow par binding. Toute instanciation directe d’une classe concrète peut donc contourner la variante active.

## Anomalies

### ARCH-001 — Haute — Sources financières multiples

- **Preuves :** `app/Services/FinancialEventService.php`; `app/Domain/Finance/**`; PR #102 `GePayLedgerEntry`.
- **Scénario :** paiement confirmé avec miroir désactivé, puis lecture d’un dashboard historique et du registre V2.
- **Conséquence :** positions différentes selon le module.
- **Correction :** désigner le registre maître et formaliser le rapprochement de chaque sous-ledger.
- **Tests :** invariants inter-registres et rapprochement quotidien.
- **Régression :** élevée.

### ARCH-002 — Haute — Ancien et nouveau code actifs

- **Preuves :** `AppServiceProvider.php:34-55`, services `Workflow*`, contrôleurs API historiques.
- **Scénario :** résolution par conteneur dans un flux, `new PaymentService()` dans un job ancien.
- **Conséquence :** comportement dépendant du chemin d’appel.
- **Correction :** contrats obligatoires et test d’architecture interdisant les instanciations directes des services critiques.
- **Tests :** container bindings et recherche statique.
- **Régression :** moyenne à élevée.

### ARCH-003 — Moyenne — Machines à états hétérogènes

- **Preuves :** `FoodOrderStateMachineService`, `ShipmentStateMachine`, `TransportService::$allowedTransitions`.
- **Scénario :** mise à jour directe d’un statut ou transition concurrente.
- **Conséquence :** effets secondaires manquants ou dupliqués.
- **Correction :** contrat commun de transition, verrouillage, audit et version de workflow.
- **Tests :** matrice exhaustive par module et type de service.
- **Régression :** moyenne.

## Conclusion

Les composants existent, mais la plateforme n’est pas encore une unité transactionnelle unique. Les prochains travaux doivent d’abord fermer les chemins qui contournent l’idempotence, le registre financier et les machines à états.