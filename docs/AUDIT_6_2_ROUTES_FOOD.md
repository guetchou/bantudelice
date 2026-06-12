# Audit 6.2 - Routes food critiques

## Portee

Audit cible des routes food detectees dans:

- `routes/web.php`
- `app/Http/Controllers/IndexController.php`
- `app/Http/Controllers/OrderModificationController.php`
- `app/Http/Controllers/OrderChatController.php`
- `app/Http/Controllers/RestaurantFavoriteController.php`
- `app/Http/Controllers/Api/CheckoutController.php`
- `app/Http/Controllers/Api/PaymentController.php`

## Regle de qualification

- `actif`: route branchee, controleur present, vue ou JSON identifies
- `partiellement branche`: route presente mais implementation ou parcours incomplet / fragile
- `legacy`: route ou nom ancien encore present
- `douteux a confirmer`: implementation presente mais dependance ou comportement non garanti sans test runtime

## Synthese rapide

- Le coeur food public est bien branche.
- Les parcours critiques couverts dans le code:
  - restaurants
  - fiche restaurant/menu
  - fiche produit
  - panier
  - checkout
  - paiement
  - commandes
  - suivi
  - favoris
  - edition pre-preparation
  - chat commande
- Les risques principaux viennent de:
  - noms legacy (`resturant`, `serach`)
  - mix routes Blade + routes API checkout/paiement
  - plusieurs points reposent sur auth + `module:food`
  - quelques comportements degradent gracieusement si certaines tables/relations manquent

## Cartographie detaillee

| Verticale | URI | Nom de route | Controleur/action | Middleware | Vue / composant rendu | Statut |
|---|---|---|---|---|---|---|
| Restaurants | `/restaurants` | `restaurants.all` | `IndexController@allRestaurants` | `ResolveSiteContext`, `module:food` | `frontend.restaurants` | actif |
| Restaurants par cuisine | `/restaurants/cuisine/{id}` | `restaurant.cuisine` | `IndexController@restaurantByCuisine` | `ResolveSiteContext`, `module:food` | `frontend.restaurant_by_cuisines` | actif |
| Fiche restaurant / menu | `/resturant/view/{id}` | `resturant.detail` | `IndexController@resturantDetail` | `ResolveSiteContext`, `module:food` | `frontend.menu` | actif avec nom legacy |
| Produit public SEO | `/plat/{id}/{slug?}` | `frontend.product.show` | `IndexController@proDetail` | `ResolveSiteContext` | `frontend.product_detail` | actif |
| Produit public legacy | `/product/view/{id}` | `pro.detail` | `IndexController@proDetail` | `ResolveSiteContext` | `frontend.product_detail` | legacy encore branche |
| Panier vue | `/cart` | `cart.detail` | `IndexController@cartDeatil` | `ResolveSiteContext` | `frontend.cart` | actif |
| Ajout panier | `POST /cart` | `cart` | `IndexController@addToCart` | `ResolveSiteContext` | redirect / JSON | actif |
| Suppression article panier | `POST /cart/deleteItem/{id}` | `cart.item` | `IndexController@deleteItem` | `ResolveSiteContext` | redirect / JSON | actif |
| Maj article panier | `PUT /cart/update/{cart}` | `cart.update` | `IndexController@updateItem` | `ResolveSiteContext` | redirect / JSON | actif |
| Compteur panier | `/cart/count` | `cart.count` | `IndexController@getCartCount` | `ResolveSiteContext` | JSON | actif |
| Checkout page | `/checkout` | `checkout.detail` | `IndexController@Checkout` | `ResolveSiteContext`, `module:food` | `frontend.checkout` | actif |
| Validation commande | `POST /checkout/order` | `place.order` | `IndexController@getOrders` | `ResolveSiteContext`, `module:food` | redirect vers `thanks` | actif |
| Checkout API | `POST /checkout/api` | `checkout.api` | `Api\\CheckoutController::__invoke` | `ResolveSiteContext`, `module:food` | JSON | actif |
| Paiement show | `GET /checkout/payments/{payment}` | `checkout.payments.show` | `Api\\PaymentController@show` | `ResolveSiteContext`, `module:food` | JSON | actif |
| Paiement confirm | `POST /checkout/payments/{payment}/confirm` | `checkout.payments.confirm` | `Api\\PaymentController@confirm` | `ResolveSiteContext`, `module:food` | JSON | actif |
| Stripe form | `GET /cart/checkout/stripe` | `stripe` | `IndexController@stripe` | `ResolveSiteContext`, `module:food` | flux paiement | partiellement branche a confirmer |
| Stripe submit | `POST /cart/checkout/stripe` | `stripe.post` | `IndexController@stripePost` | `ResolveSiteContext`, `module:food` | redirect / paiement | partiellement branche a confirmer |
| Thank you | `GET /cart/checkout/thankyou` | `thanks` | `IndexController@thanks` | `ResolveSiteContext` | `frontend.thanks` | actif |
| Suivi commande | `GET /track-order/{orderNo?}` | `track.order` | `IndexController@trackOrder` | `ResolveSiteContext`, `module:food` | `frontend.track_order` | actif |
| Confirmation reception | `POST /track-order/{orderNo}/confirm` | `track.order.confirm` | `IndexController@confirmOrderReceipt` | `ResolveSiteContext`, `module:food` | redirect / action | actif |
| Reopen pickup | `POST /track-order/{orderNo}/reopen-pickup` | `track.order.reopen_pickup` | `IndexController@reopenPickupOrder` | `ResolveSiteContext`, `module:food` | redirect / action | actif |
| Incident commande | `POST /track-order/{orderNo}/incident` | `track.order.incident` | `IndexController@reportOrderIncident` | `ResolveSiteContext`, `module:food` | redirect / action | actif |
| Redelivery | `POST /track-order/{orderNo}/redelivery` | `track.order.redelivery` | `IndexController@requestOrderRedelivery` | `ResolveSiteContext`, `module:food` | redirect / action | actif |
| Recu commande | `GET /order-receipt/{orderNo}` | `order.receipt` | `IndexController@orderReceipt` | `ResolveSiteContext`, `module:food` | `frontend.order_receipt` | actif |
| Edition commande | `GET /orders/{orderNo}/edit` | `orders.edit` | `OrderModificationController@edit` | `ResolveSiteContext`, `auth`, `module:food` | `frontend.order_edit` | actif |
| Update commande | `PATCH /orders/{orderNo}` | `orders.update` | `OrderModificationController@update` | `ResolveSiteContext`, `auth`, `module:food` | redirect | actif |
| Chat messages | `GET /orders/{orderNo}/chat/messages` | `orders.chat.messages` | `OrderChatController@messages` | `ResolveSiteContext`, `auth`, `module:food` | `frontend.partials.order_chat_messages` via JSON HTML | actif |
| Chat post | `POST /orders/{orderNo}/chat` | `orders.chat.store` | `OrderChatController@store` | `ResolveSiteContext`, `auth`, `module:food` | redirect / JSON | actif |
| Favoris liste | `GET /favorite-restaurants` | `restaurants.favorites` | `RestaurantFavoriteController@index` | `ResolveSiteContext`, `auth`, `module:food` | `frontend.favorite_restaurants` | actif |
| Favori toggle | `POST /restaurants/{restaurant}/favorite` | `restaurants.favorite.toggle` | `RestaurantFavoriteController@toggle` | `ResolveSiteContext`, `auth`, `module:food` | redirect / JSON | actif |
| Recherche page | `GET /search/` | `serach` | `IndexController@searchResult` | `ResolveSiteContext` | `frontend.search` | actif avec nom legacy |
| Recherche AJAX | `GET /search/ajax` | `search.ajax` | `IndexController@searchAjax` | `ResolveSiteContext` | JSON / AJAX | actif |
| Recherche API | `GET /search/api` | `search.api` | `IndexController@searchApi` | `ResolveSiteContext` | JSON | actif |

## Notes par zone

### Restaurants

- `IndexController@allRestaurants` rend `frontend.restaurants`.
- Supporte filtres et pagination AJAX.
- Statut `actif`.

### Menus / fiches restaurants

- `IndexController@resturantDetail` charge:
  - restaurant approuve
  - cuisines
  - ratings
  - categories + products ordonnes
  - promos
  - statut ouvert/ferme si service dispo
- Vue rendue: `frontend.menu`
- Le nom de route et segment `resturant` est fautif mais branche.
- Statut `actif avec nom legacy`.

### Produits

- `IndexController@proDetail` rend `frontend.product_detail`.
- Deux routes pointent dessus:
  - une route plus propre SEO `/plat/{id}/{slug?}`
  - une route legacy `/product/view/{id}`
- Statut:
  - route SEO `actif`
  - route legacy `legacy encore branche`

### Panier

- Vue principale `frontend.cart` depuis `IndexController@cartDeatil`.
- Gere utilisateur connecte et invite.
- Ajout/suppression/update exposes en web.
- Statut `actif`.

### Checkout

- Page Blade `frontend.checkout` via `IndexController@Checkout`.
- Forcage login avant paiement.
- Groupement panier par restaurant via `CartGroupService`.
- Adresses sauvegardees, loyalty, stock issues, taxes/frais.
- Statut `actif`.

### Paiement

- Deux couches:
  - flux web classique `getOrders`
  - flux API `Api\\CheckoutController` + `Api\\PaymentController`
- `Api\\CheckoutController` renvoie JSON `payment`, `payment_experience`, `requires_external_payment`.
- `Api\\PaymentController` gere polling / confirmation manuelle.
- Les routes Stripe existent mais sans validation runtime ici.
- Statut:
  - API checkout/paiement `actif`
  - routes Stripe `partiellement branche a confirmer`

### Commandes

- Creation commande via `IndexController@getOrders`.
- La methode:
  - valide mode reception / paiement
  - calcule totaux
  - applique vouchers / loyalty
  - insere lignes `orders`
  - vide le panier
  - cree livraisons si delivery
  - declenche auto assign
  - journalise finance / risk / notifications
- Statut `actif`.

### Suivi

- `IndexController@trackOrder` rend `frontend.track_order`.
- Charge:
  - order
  - delivery.driver
  - payment
  - order items
  - ETA
  - chat data
  - payment experience
- Actions associees:
  - confirm receipt
  - reopen pickup
  - incident
  - redelivery
- Statut `actif`.

### Edition avant preparation

- `OrderModificationController`
- Autorise seulement le proprietaire ou admin.
- Refuse si commande deja trop avancee.
- Vue `frontend.order_edit`.
- Peut reinitialiser la livraison et relancer l'auto-assign.
- Statut `actif`.

### Chat commande

- `OrderChatController@messages` rend HTML partiel `frontend.partials.order_chat_messages` dans JSON.
- `OrderChatController@store` poste le message.
- Depend de `OrderChatService`.
- Statut `actif`.

### Favoris restaurants

- `RestaurantFavoriteController@index` -> `frontend.favorite_restaurants`
- `toggle` supporte HTML ou JSON.
- Statut `actif`.

### Recherche food

- `searchResult`, `searchAjax`, `searchApi` existent.
- Le nom de route `serach` est legacy/fautif.
- Statut `actif avec naming legacy`.

## Points sensibles / risque de regression

- Noms legacy exposes dans des routes encore publiques:
  - `resturant.detail`
  - `serach`
- La creation commande `getOrders` est dense et critique:
  - pricing
  - vouchers
  - loyalty
  - creation `orders`
  - creation `deliveries`
  - notifications
- Le checkout existe en double logique:
  - Blade web
  - API JSON
- Certaines vues/flux degradent si tables ou services manquent:
  - ratings
  - vouchers
  - user_address
  - RestaurantStatusService
- Toute modification UI future doit eviter de casser:
  - `track.order`
  - `checkout.detail`
  - `place.order`
  - `checkout.api`
  - `checkout.payments.*`

## Conclusion

Le vertical food est reellement branche et couvre le cycle public principal de bout en bout. Le risque n'est pas l'absence de routes, mais plutot:

- la coexistence de conventions legacy
- la densite du checkout / order placement
- le mix Blade + endpoints JSON

Pour l'etape suivante, les points a ne pas casser en priorite sont:

- listing restaurants
- fiche restaurant / menu
- panier
- checkout
- suivi commande
- edition commande
- chat commande
