# Résumé PRD — Workflow Livraison / Dispatch / UX

## 1. Workflow commande et livraison

* Dès que le restaurant accepte la commande :

  * la cuisine démarre automatiquement ;
  * le dispatch livreur démarre en parallèle (simultané) ;
  * les livreurs proches reçoivent déjà la demande pour réduire le temps d’attente.

* Le livreur :

  * accepte la course ;
  * voit immédiatement une carte GPS temps réel ;
  * navigation type Google Maps/Waze intégrée ;
  * trajet client ↔ restaurant ↔ destination affiché.

* Livraison :

  * le livreur confirme “commande remise” ;
  * le client reçoit une notification pour confirmer réception ;
  * si paiement à la livraison :

    * le client paie ;
    * le livreur confirme l’encaissement ;
    * statut commande → “payée/livrée”.

---

## 2. Workflow client — commande

Le formulaire client doit être en multi-étapes plutôt qu’un écran unique mélangé.

### Exemple :

1. Adresse livraison
2. Choix restaurant
3. Produits/panier
4. Paiement
5. Validation finale

Fonctionnalités UX :

* autosave brouillon ;
* champs conditionnels ;
* auto-complétion adresse ;
* upload fichier/image ;
* validation temps réel ;
* détection modifications non enregistrées.

---

## 3. États UI obligatoires

### États système

* loading ;
* skeleton loaders ;
* empty states ;
* offline mode ;
* unauthorized/403 ;
* success ;
* error states.

### Feedback utilisateur

* toast animé ;
* spinner ;
* progress bar ;
* alertes ;
* retry action ;
* confirm dialog.

---

## 4. Interactions UI/UX

### États composants

* hover ;
* active ;
* focus ;
* disabled ;
* loading.

### Animations

* fade ;
* slide ;
* scale ;
* ripple ;
* shimmer ;
* pulse ;
* success checkmark ;
* error shake ;
* chart animation ;
* scroll reveal ;
* parallax.

---

## 5. Problèmes techniques à auditer

* Le code de confirmation/remise ne fonctionne pas correctement ;

* vérifier si :

  * SMS automatique ;
  * notification push ;
  * application native ;
  * websocket ;
  * polling frontend ;
  * API statut commande ;
  * synchronisation livreur/client.

* Malgré confirmation, un écran d’erreur reste affiché :

  * vérifier logique état frontend ;
  * cache ;
  * mutation API ;
  * refresh état commande ;
  * race conditions ;
  * synchronisation temps réel.

---

## 6. Direction produit

Objectif :

* workflow fluide ;
* réduction temps livraison ;
* UX moderne type Uber Eats/Glovo ;
* suivi temps réel ;
* feedback utilisateur constant ;
* états système visibles ;
* réduction des erreurs et abandons.
# PRD — Workflow commande, dispatch livreur et suivi temps réel

**Version** : 1.0  
**Date** : 2026-05-28  
**Projet** : BantuDelice food delivery  
**Périmètre** : client, restaurant, cuisine, livreur, suivi, paiement à la livraison, notifications et états UI.

## Problem Statement

Le workflow actuel donne au client une impression de commande déjà terminée ou trop avancée alors que le restaurant, la cuisine ou le livreur n'ont pas forcément exécuté les étapes correspondantes. L'écran de suivi peut afficher "Livré", "Paiement confirmé" ou un livreur assigné de manière prématurée. Côté restaurant, l'arrivée d'une commande n'est pas assez visible, et côté livreur le flux manque d'étapes claires pour accepter, aller au restaurant, récupérer, livrer, encaisser et confirmer.

Le besoin produit est de rendre le parcours strictement séquentiel sur les décisions critiques, tout en lançant certaines actions en parallèle quand cela accélère l'opération sans mentir au client. Le client doit voir une timeline fiable. Le restaurant doit accepter ou refuser. La cuisine doit préparer. Le dispatch peut chercher un livreur dès l'acceptation restaurant, mais le livreur ne doit pas prétendre avoir récupéré tant que la cuisine/restaurant n'a pas remis la commande. La livraison doit se terminer par confirmation de remise, réception client et, si paiement cash, confirmation d'encaissement.

## Solution

Le workflow cible est un parcours multi-acteurs avec une state machine unique et des écrans multi-étapes adaptés à chaque rôle.

Le dispatch livreur doit démarrer dès que le restaurant accepte une commande en livraison. C'est la bonne combinaison opérationnelle : la cuisine prépare pendant que le système cherche un livreur. Le livreur peut accepter et se mettre en route vers le restaurant avant que la commande soit prête, mais son interface doit afficher clairement "aller au restaurant" et "attendre la remise" tant que la cuisine n'a pas terminé.

Le suivi client ne doit jamais inventer un état. Il affiche uniquement les étapes réellement atteintes :

1. Commande envoyée au restaurant
2. Restaurant accepté
3. Préparation cuisine
4. Livreur recherché
5. Livreur assigné
6. Livreur en route vers restaurant
7. Commande prête
8. Commande récupérée
9. Livreur en route vers client
10. Remise au client
11. Réception confirmée
12. Paiement/encaissement confirmé si paiement à la livraison
13. Commande clôturée

La carte GPS doit apparaître dès qu'un livreur est assigné. Avant assignation, l'écran affiche une carte restaurant/client ou un état d'attente. Quand le livreur démarre, la carte doit montrer sa position et les points restaurant/client. Le tracé détaillé type Google Maps peut rester hors périmètre initial, mais la position temps réel et les distances simples doivent être visibles.

Le code de remise n'est pas généré par SMS par défaut. La première version doit le générer côté application, l'afficher au client dans le suivi et le demander au livreur au moment de la remise. Une notification in-app/WebSocket doit prévenir le client. SMS ou application native sont des canaux optionnels futurs, pas une dépendance pour que le flux fonctionne.

## User Stories

1. As a client, I want checkout to be split into clear steps, so that I understand delivery, address, payment and confirmation before submitting.
2. As a client, I want required fields to block progression, so that I do not create an incomplete order.
3. As a client, I want delivery fields to appear only when I choose delivery, so that pickup orders stay simple.
4. As a client, I want pickup fields to appear only when I choose pickup, so that the form does not mix unrelated choices.
5. As a client, I want mobile money fields to appear only when I choose mobile money, so that I enter the correct phone number.
6. As a client, I want cash-on-delivery information to be explicit, so that I know I will pay after delivery confirmation.
7. As a client, I want a loading state when submitting, so that I do not click twice.
8. As a client, I want a clear success state after order creation, so that I know the order was sent to the restaurant.
9. As a client, I want an error message when order creation fails, so that I know what to correct.
10. As a client, I want the app to keep a draft while I edit checkout, so that accidental navigation does not lose my data.
11. As a client, I want a dirty-state warning when leaving checkout with unsaved changes, so that I do not lose the order draft.
12. As a client, I want address autocomplete, so that delivery address entry is faster.
13. As a client, I want the map to confirm my exact location, so that the driver can find me.
14. As a client, I want an offline state, so that I know the app cannot submit or refresh.
15. As a client, I want the tracking page to start with "waiting for restaurant", so that I do not believe the order is accepted before it is.
16. As a client, I want to see restaurant acceptance in real time, so that I know preparation has started.
17. As a client, I want to see when a driver is being searched, so that I understand the dispatch step.
18. As a client, I want to see the assigned driver name and phone, so that I know who will deliver.
19. As a client, I want to call the restaurant, so that I can clarify preparation issues.
20. As a client, I want to call the driver after assignment, so that I can guide the delivery.
21. As a client, I want chat linked to the order, so that client, restaurant, driver and support share context.
22. As a client, I want to see the driver moving on a map, so that I can follow pickup and delivery.
23. As a client, I want to receive a reception-confirmation prompt at delivery time, so that I confirm only when I have the order.
24. As a client, I want to see a delivery code, so that the driver can verify the right recipient.
25. As a client, I want the code to remain hidden from non-participants, so that reception cannot be spoofed.
26. As a client, I want to confirm reception, so that the order can move to payment/closing.
27. As a client paying cash, I want the app to show payment still due until the driver confirms collection, so that "paid" is not shown too early.
28. As a client paying mobile money, I want the app to show paid only after payment confirmation, so that the restaurant does not prepare unpaid orders.
29. As a client, I want a receipt after completion, so that I can keep proof.
30. As a client, I want rating to appear only after delivery completion, so that I rate the real experience.
31. As a restaurant, I want a strong visual alert for a new order, so that staff cannot miss it.
32. As a restaurant, I want accept/refuse buttons on the order detail, so that the decision is immediate.
33. As a restaurant, I want accepting to send the order to cuisine, so that preparation starts.
34. As a restaurant, I want accepting a delivery order to start dispatch in parallel, so that a driver can arrive near preparation time.
35. As a restaurant, I want pickup orders not to start driver dispatch, so that no driver is wrongly notified.
36. As a restaurant, I want unpaid mobile money orders hidden from active preparation, so that staff do not prepare unpaid orders.
37. As a restaurant, I want cash orders to be allowed after restaurant acceptance, so that cash-on-delivery remains possible.
38. As a restaurant, I want to mark the order ready, so that the driver knows pickup can happen.
39. As a restaurant, I want to confirm handoff to driver, so that responsibility moves from restaurant to driver.
40. As a restaurant, I want cancellation to require a reason, so that the client and support understand the incident.
41. As kitchen staff, I want a kitchen board grouped by order, so that one customer order does not appear as several clients.
42. As kitchen staff, I want new orders, preparing, ready and handed-off columns, so that workflow is visible.
43. As kitchen staff, I want a loading/skeleton state, so that polling does not make the screen look broken.
44. As kitchen staff, I want an empty state, so that "no command" is explicit.
45. As kitchen staff, I want a toast when a new order arrives, so that I notice it even if I am not staring at the list.
46. As a driver, I want to receive a delivery offer after restaurant acceptance, so that I can start moving early.
47. As a driver, I want the offer to show restaurant, client zone, distance and estimated payout, so that I can decide.
48. As a driver, I want to accept or decline within a timeout, so that dispatch can continue if I do not answer.
49. As a driver, I want the app to show "go to restaurant" after accepting, so that my next action is clear.
50. As a driver, I want GPS map guidance to the restaurant, so that I can find pickup.
51. As a driver, I want to see "waiting for kitchen" if I arrive early, so that I do not mark pickup too soon.
52. As a driver, I want pickup to be disabled until the order is ready, so that the state cannot lie.
53. As a driver, I want to confirm pickup only after restaurant handoff, so that responsibility is clear.
54. As a driver, I want the app to switch to "go to client" after pickup, so that the next destination is clear.
55. As a driver, I want GPS map guidance to the client, so that delivery is easier.
56. As a driver, I want to enter the delivery code at handoff, so that the correct client receives the order.
57. As a driver, I want client reception confirmation to be visible, so that delivery can be closed.
58. As a driver, I want cash collection confirmation after client reception for cash orders, so that payment is recorded.
59. As a driver, I want a clear success state after cash collection, so that I know the mission is closed.
60. As a driver, I want a retry action when GPS update fails, so that temporary network issues do not block the flow.
61. As support, I want every state transition recorded in order events, so that disputes can be audited.
62. As support, I want notifications persisted, so that missed WebSocket events are not lost.
63. As support, I want a realtime health page, so that I can diagnose Soketi/browser connectivity.
64. As support, I want unauthorized users blocked from private channels, so that order data stays private.
65. As admin, I want dashboards to distinguish active, ready, delivered, paid and disputed orders, so that operations are accurate.

## Implementation Decisions

- Use the existing Laravel/Blade/MySQL/Soketi stack. Do not add a new technology for the workflow.
- Keep one authoritative order state machine for food orders.
- Centralize state changes in a single order status service. Controllers must request transitions, not mutate order fields directly.
- Centralize broadcasting in a dedicated broadcast service. Controllers and jobs should not manually trigger channels in different ways.
- Persist every transition in `order_events`.
- Persist every user-facing alert in `notifications_center`.
- Start driver dispatch when the restaurant accepts a delivery order, not when the kitchen marks ready.
- Do not dispatch for pickup orders.
- Do not expose unpaid mobile money orders to restaurant preparation, kitchen, driver dispatch or active driver lists.
- Allow cash orders into restaurant workflow, but keep payment state unpaid/due until delivery cash collection is confirmed.
- Split operational statuses from payment statuses.
- Split business status from technical status.
- Driver assignment means "driver accepted mission", not "driver has picked up".
- Pickup confirmation requires order ready/handoff.
- Delivery completion requires handoff/reception confirmation.
- Cash payment completion requires driver cash collection confirmation.
- The client tracking page may be accessible immediately, but the visible status must be "waiting for restaurant" until acceptance.
- The tracking page must not show assigned driver before a real driver assignment exists.
- The tracking page must not show delivered before reception/handoff confirmation exists.
- The tracking page must not show cash as paid before cash collection confirmation exists.
- The delivery code is generated by the application and displayed in the client tracking page. SMS/native app delivery can be added later.
- The driver interface should be a multi-step workflow: offer, accepted, to restaurant, waiting/ready, pickup, to client, handoff code, cash collection, closed.
- The client checkout should remain a multi-step form: cart review, delivery/pickup, payment, confirmation.
- The restaurant order detail should always show primary actions: accept, refuse/cancel, send/ready/handoff according to current state.
- The kitchen board should group order lines by `order_no`.
- The map must use existing mapping capabilities already present in the project. Initial requirement is markers and live position, not full turn-by-turn routing.
- The fallback polling remains required even with WebSocket, so the UI recovers after missed events.
- The UI must include required field states, loading states, disabled states, success states, error states, empty states, offline states and unauthorized states.
- Toasts are for temporary feedback only; important alerts must also be persisted.
- Animations must be restrained and purposeful: pulse for live/new, spinner for short loading, skeleton for list loading, success checkmark for completed actions.

## Workflow Rules

### Delivery Order

1. Client submits checkout.
2. If mobile money: payment must be confirmed before restaurant sees active preparation.
3. If cash: order can be sent to restaurant as payment due.
4. Restaurant receives notification and accepts or refuses.
5. On acceptance: cuisine receives the order and dispatch starts in parallel.
6. Driver receives an offer and may accept.
7. If driver accepts before kitchen ready: driver goes to restaurant and waits.
8. Kitchen marks ready.
9. Restaurant confirms handoff to driver.
10. Driver confirms pickup.
11. Driver goes to client.
12. Driver requests/enters delivery code or client confirms reception.
13. If mobile money already paid: order closes after reception confirmation.
14. If cash: client confirms reception, then driver confirms cash collected.
15. Order closes after cash collection confirmation.

### Pickup Order

1. Client submits checkout.
2. Payment rule is applied.
3. Restaurant accepts.
4. Cuisine prepares.
5. Restaurant marks ready for pickup.
6. Client receives pickup notification/code.
7. Restaurant confirms client pickup.
8. Payment is confirmed according to selected payment method.
9. Order closes.

### Refusal or Cancellation

1. Restaurant refusal requires a reason.
2. Client receives the refusal reason.
3. Driver dispatch must not start if restaurant refuses.
4. If dispatch already started and order is cancelled, driver mission is cancelled and notified.
5. All cancellation/refusal events are written to the timeline.

## UI Requirements

### Client Checkout Multi-Step Form

- Step 1: panier and restaurant summary.
- Step 2: delivery mode, address, map, pickup option and schedule if needed.
- Step 3: payment method, mobile money phone, cash-on-delivery notice, voucher and tip.
- Step 4: final review and submit.
- Required fields must be marked and validated before progression.
- Conditional fields must depend on delivery mode and payment method.
- Autosave should preserve a draft in local storage or existing server-side session.
- Dirty state should warn before leaving with unsaved changes.
- Submit button must show loading spinner and be disabled during submission.
- Success must redirect to tracking/waiting page.
- Error must explain the blocking issue.
- Offline must block submit and show "Vous êtes hors connexion".

### Client Tracking

- Timeline must show only real reached steps.
- Current step should be visually distinct.
- Completed steps should show checkmarks.
- Future steps should stay inactive.
- Delivery code appears only when delivery is in progress/ready for handoff.
- Reception confirmation prompt appears at handoff time.
- Payment card must show exact payment status: pending, paid, due cash, cash collected, failed.
- Rating appears only after order closure.
- Chat is contextual to the order.
- Map appears when useful and must not imply a route if no route is calculated.

### Restaurant

- New command alert must be visible, persistent until read, and not remain stuck after read.
- Order detail must include a back action.
- Order detail must include accept/refuse/cancel according to status.
- Kitchen board must not empty itself during polling.
- Kitchen board must group items by order, not show one customer as multiple clients.
- "Commandes" should lead to the operational kitchen/orders view directly.

### Driver

- Driver workflow must be multi-step.
- Offer screen: accept/decline.
- Active mission screen: map, next action, restaurant/client contact, order summary.
- Pickup action disabled until order ready/handoff.
- Delivery action requires code or client confirmation.
- Cash collection action appears only for cash orders after reception confirmation.
- Offline/GPS error states must be explicit with retry.

## Testing Decisions

- Tests must verify external behavior, not private implementation details.
- Add feature tests for state transitions: submit, accept, dispatch, ready, pickup, handoff, reception, cash collection, close.
- Add tests ensuring mobile money pending orders are hidden from restaurant/cuisine/driver active lists.
- Add tests ensuring cash orders are visible but payment remains due until collection.
- Add tests ensuring pickup orders never start driver dispatch.
- Add tests ensuring dispatch starts on restaurant acceptance for delivery orders.
- Add tests for private channel authorization: client, restaurant, assigned driver and admin only.
- Add browser/E2E test for client checkout multi-step validation.
- Add browser/E2E test for restaurant accept -> kitchen -> dispatch visibility.
- Add browser/E2E test for driver mission multi-step flow.
- Add browser/E2E test for client tracking timeline not showing future states prematurely.
- Add regression tests for notification badge read/unread behavior.
- Reuse existing realtime tests as prior art for Soketi/broadcast verification.

## Acceptance Criteria

- A delivery order cannot appear as delivered before restaurant handoff, driver pickup and reception confirmation.
- A driver cannot be assigned before restaurant acceptance.
- Driver search starts immediately after restaurant acceptance for delivery orders.
- Driver search does not start for pickup orders.
- Unpaid mobile money orders do not enter active restaurant/cuisine/driver workflows.
- Cash orders enter workflow but remain payment due until cash collection confirmation.
- Client tracking shows "waiting for restaurant" before restaurant acceptance.
- Client tracking shows assigned driver only after real assignment.
- Client tracking shows live map once driver is assigned.
- Restaurant order detail always exposes the correct next action.
- Kitchen board remains stable during polling.
- Notifications are persisted and not lost if WebSocket is missed.
- Fallback polling updates client, restaurant and driver screens.
- Private channels reject unauthorized users.
- Checkout form is multi-step with conditional fields and validation.
- Driver mission flow is multi-step with map and explicit next action.

## Out of Scope

- Native mobile application.
- SMS/WhatsApp delivery-code generation.
- Full turn-by-turn routing engine.
- New realtime technology.
- New payment provider.
- Destructive database migration.
- Redesign of the full public website outside checkout/tracking/order workflow.

## Further Notes

- The delivery code is an application-generated proof. SMS can be added later as a notification channel, but the workflow must work without it.
- Dispatch on restaurant acceptance is operationally better than dispatch on kitchen ready because it reduces delivery wait time. The UI must compensate by showing "waiting at restaurant" instead of allowing premature pickup.
- The screenshot showing "Livré" while the workflow is still semantically confused is a symptom of state/status conflation. The implementation must separate restaurant acceptance, kitchen readiness, driver assignment, pickup, delivery, reception and payment collection.
- Every future UI screen in this workflow should include normal, hover, active, focus, disabled, loading, success, error, empty, offline and unauthorized states where applicable.
