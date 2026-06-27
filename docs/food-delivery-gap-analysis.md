# Food delivery gap analysis

| Étape | Comportement existant observé | Manque | Risque | Correction proposée/appliquée |
| --- | --- | --- | --- | --- |
| Checkout adresse | Le checkout bloque une adresse trop large. Sans Mapbox, `Placer repère` ne confirmait rien. | Fallback sans carte. | Client bloqué ou coordonnées envoyées null. | Handler `confirmCheckoutManualPin`, confirmation explicite du repère courant, conservation lat/lng si adresse confirmée. |
| Checkout cash | Commande créée en `pending_restaurant_acceptance`, paiement `pending`. | OK après test. | Faible. | Validé par UI. |
| Acceptation restaurant cash | Avant correction, acceptation cash passait `payment_status=paid` avant collecte. | Cash doit rester pending. | Encaissement faux, comptabilité fausse. | `OrderAcceptanceService` conserve `payment_status=pending`, `cash_collection_status=pending_collection`. |
| Cuisine cash | Le garde `in_kitchen` exigeait `payment_status=paid`. | Autoriser cash accepté sans le marquer payé. | Blocage cuisine ou paiement artificiel. | `FoodOrderStateMachineService` accepte cash `pending_collection`. |
| Cuisine prête | Écran cuisine liste une commande logique une fois. | Les clics Agent Browser sur boutons dynamiques nécessitent parfois appel JS. | Test fragile. | Fonction `setStatus()` validée, transition `ready_for_pickup` effective. |
| Dispatch | `dispatch:process-pending` assigne un livreur approved/online/GPS. | Temps réel worker non lancé automatiquement dans le lab. | Mission reste PENDING si worker absent. | Commande planifiée existante + exécution manuelle en lab. |
| Portail livreur | Mission assignée visible. Formulaires pickup/onway/delivered présents. | Pas d’action dédiée “Je suis arrivé au restaurant”. | Statut cible incomplet. | À ajouter en tranche suivante. |
| GPS | Route web `/driver/location` authentifiée fonctionne. | Watch navigateur non déclenché automatiquement dans le lab. | Suivi stale si permission refusée. | Position publiée depuis session navigateur par fetch authentifié, pas SQL. |
| Tracking client | Statut, ETA, distances, OTP, livreur visibles. | Carte affiche “Carte indisponible” sans token Mapbox. | Pas de polyline réelle en lab. | Garder env `MAPBOX_PUBLIC_TOKEN`, ajouter faux provider test ultérieurement. |
| Livraison OTP/cash | OTP incorrect refusé. OTP correct livre, collecte cash et paie. Le service OTP courant hash, expire et limite les tentatives. | À couvrir plus largement côté tests E2E automatisés avec fichiers invalides/positions éloignées. | Contournement preuve si future régression. | Preuve forte requise par `DeliveryProofService`; tests ciblés cash/proof maintenus. |
| Confirmation client | Avant correction, réception confirmée pouvait rester `delivered`. | Passage `closed`. | Commande non clôturée. | Web/API confirmation client déclenchent transition `closed`. |
| Audit intégrité | Commande `food:audit-integrity` existante avec contrôles doublons/paiement cash. | Contradictions livraison ↔ commande non contrôlées. | Audit clean malgré statuts incohérents. | Service d’audit enrichi avec `delivery_order_status_mismatches`; résultat final clean. |
