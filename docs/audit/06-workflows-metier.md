# 06 — Workflows métier

## Food

| Source | Action | Cible | Conditions | Effets | Tests manquants |
|---|---|---|---|---|---|
| pending | restaurant accepte | accepted/awaiting payment | restaurant autorisé | audit, notification | concurrence accept/refuse |
| confirmed | démarrer cuisine | in_kitchen | payé ou cash à collecter | horodatage, realtime | double transition |
| in_kitchen | prêt | ready_for_pickup | préparation active | notification, auto-affectation | job dupliqué |
| livraison en cours | livré | delivered | preuve/acteur | cash auto-collecté actuellement | preuve cash et concurrence |
| tout état autorisé | annuler | cancelled | matrice/config | paiement à traiter | annulation après encaissement |

### WF-FOOD-001 — Critique — Cash marqué encaissé par transition logistique

- **Fichiers :** `FoodOrderStateMachineService.php:101-121`; `WorkflowFoodOrderStateMachineService.php:34-55`.
- **Fait :** atteindre un état terminal peut positionner la collecte cash et les paiements à `PAID` sans preuve financière indépendante.
- **Conséquence :** chiffre d’affaires et solde partenaire créés à partir d’un statut de livraison.
- **Correction :** preuve de collecte distincte, acteur, montant, rapprochement caisse et événement financier.
- **Tests :** livré sans cash, échec collecte, collecte partielle et double confirmation.
- **Régression :** élevée.

### WF-FOOD-002 — Haute — Transition identique rejouée

- **Fichier :** `FoodOrderStateMachineService::canTransition`.
- **Fait :** `from===to` est accepté, puis audit/notifications/realtime peuvent être répétés.
- **Correction :** no-op sans effets ou idempotency key de transition.
- **Tests :** même statut deux fois.

### WF-FOOD-003 — Haute — Concurrence sans verrou

- **Fait :** groupe chargé avant transaction, pas de `lockForUpdate` sur les commandes.
- **Conséquence :** deux acteurs partent du même état et exécutent deux effets incompatibles.
- **Correction :** relecture verrouillée dans la transaction.
- **Tests :** acceptation/annulation simultanées.

## Colis

| Source | Action | Cible | Conditions | Effets | Tests manquants |
|---|---|---|---|---|---|
| créé | paiement confirmé | paid | Payment PAID | événement/audit | replay callback |
| affectable | assigner | assigned | coursier disponible | notification | double affectation |
| en livraison | livrer | delivered | OTP/preuve | COD possible | preuve absente |
| COD pending | collecte | paid | coursier/admin | réconciliation | lot concurrent |

### WF-COLIS-001 — Critique — Réconciliation COD non bornée

- **Fichier :** `ShipmentPaymentService::reconcileCourier`.
- **Fait :** création d’une réconciliation à partir de `shipmentIds` et montant fournis, puis mise à jour en masse sans vérifier propriétaire, état, absence de réconciliation antérieure ni égalité du total.
- **Conséquence :** colis arbitraires marqués payés et montant incohérent.
- **Correction :** sélectionner sous verrou les colis COD du coursier, calcul serveur, clé d’idempotence et preuve de versement.
- **Tests :** ID d’un autre coursier, doublon, total incorrect, concurrence.
- **Régression :** élevée.

### WF-COLIS-002 — Haute — Statut PAID utilisé pour COD non collecté

- **Fichier :** `ShipmentPaymentService::handleCOD`.
- **Fait :** le choix COD peut faire transiter le shipment vers `PAID` alors que `payment_status=cod_pending`.
- **Conséquence :** ambiguïté entre autorisation logistique et paiement réel.
- **Correction :** état `payment_due/cod_pending` distinct.
- **Tests :** COD sélectionné puis annulé/non collecté.

## Transport

| Source | Action | Cible | Conditions | Effets | Tests manquants |
|---|---|---|---|---|---|
| requested | chauffeur accepte | assigned | autorisation/disponibilité | présence, notification | deux chauffeurs |
| assigned | arrivée | driver_arriving | chauffeur affecté | horodatage | requête concurrente |
| picked_up | démarrer | in_progress | prise en charge | tracking | transition stale |
| in_progress | terminer | completed | trajet actif | finance cash | preuve montant |
| completed | payé | paid | paiement confirmé | clôture | callback concurrent |

### WF-TRANSPORT-001 — Critique — Cash payé automatiquement à la fin du trajet

- **Fichier :** `TransportService::completeFinancialSideIfNeeded`.
- **Fait :** `COMPLETED` positionne paiement et booking comme payés sans preuve de collecte distincte.
- **Correction :** workflow collecte cash, litige et rapprochement chauffeur.
- **Tests :** trajet terminé non payé, paiement partiel, contestation.

### WF-TRANSPORT-002 — Haute — Transitions sans verrouillage

- **Fichier :** `TransportService::updateStatus`.
- **Fait :** contrôle sur modèle potentiellement ancien puis update sans verrou SQL.
- **Correction :** transaction, relecture verrouillée et comparaison de version.
- **Tests :** deux chauffeurs et annulation/prise en charge simultanées.

## Règles transverses

- Les statuts logistiques ne prouvent jamais un mouvement d’argent.
- Toute transition financière possède une clé d’idempotence.
- Les transitions sont décidées sous verrou sur l’état courant.
- Tout override admin exige motif, acteur et audit.
- Les effets secondaires sont publiés après commit via outbox.