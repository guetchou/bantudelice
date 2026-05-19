# Audit 6.7 - Dependances croisees

## Portee

Identification des dependances partagees entre:

- food
- transport/taxi
- colis

## Conclusion rapide

- Les verticales ne partagent pas le meme checkout metier.
- Elles partagent en revanche plusieurs couches structurantes:
  - layout public
  - homepage / branding
  - contexte site
  - paiement
  - dashboard admin
  - shell admin
  - flags modules
  - table `payments`
- Ce qui est masquable ou renommable facilement:
  - wording
  - labels
  - ordre des liens
  - blocs ecosysteme
- Ce qui est sensible:
  - paiements
  - callbacks
  - flags modules
  - shell admin
  - compteurs / badges / dashboards relies

## 1. Header commun

### Preuve

- `resources/views/frontend/layouts/app-modern.blade.php`
- `resources/views/frontend/layouts/app.blade.php`
- `resources/views/frontend/index-modern.blade.php`

### Constat

- layout moderne porte un header global
- home moderne embarque aussi sa propre navigation `nav2`
- legacy layout a son propre menu

### Qualification

- header partiellement commun
- risque de double source

## 2. Homepage commune

### Preuve

- `app/Http/Controllers/IndexController.php`
- `resources/views/frontend/index-modern.blade.php`
- `app/Services/ConfigService.php`

### Constat

- la home est unique
- elle porte la couche de branding food + ecosysteme
- elle expose aussi des cartes/liens colis et transport

### Qualification

- oui, homepage commune
- tres sensible au recentrage branding

## 3. Moteur de recherche commun

### Preuve

- `app/Services/CatalogSearchService.php`
- `IndexController@home`
- faux champ `Rechercher…` dans `index-modern`

### Constat

- moteur de recherche reel cote public: centre sur le catalogue food
- pas de moteur commun prouve pour food + taxi + colis
- le champ visuel home moderne n'est pas branche

### Qualification

- non, pas de moteur commun reel multi-vertical

## 4. Composants categories / listings communs

### Preuve

- `IndexController@home`
- `resources/views/frontend/partials/restaurants_list.blade.php`
- cartes/services dans la home

### Constat

- listings food ont leur propre logique
- transport et colis ont leurs propres vues dediees
- la home sert d'agregateur editorial

### Qualification

- partage partiel de presentation, pas de listing metier commun

## 5. Panier commun

### Preuve

- `app/Cart.php`
- `app/Services/CartService.php`
- `app/Services/CartGroupService.php`
- `IndexController` routes `cart` / `checkout`

### Constat

- panier reserve au food
- aucun panier colis/transport commun prouve

### Qualification

- non, panier non partage

## 6. Checkout commun

### Preuve

- food:
  - `app/Services/CheckoutService.php`
  - `app/Http/Controllers/Api/CheckoutController.php`
- transport:
  - `app/Domain/Transport/Services/TransportService.php`
  - `api/Transport/TransportBookingController`
- colis:
  - `app/Domain/Colis/Services/ShipmentPaymentService.php`
  - `Api/V1/Colis/ShipmentController`

### Constat

- food a son checkout dedie
- transport reserve et paie via ses endpoints
- colis cree un shipment puis paie via son propre service

### Qualification

- non, checkout non commun

## 7. Paiement commun

### Preuve

- `app/Payment.php`
- `app/Services/PaymentService.php`
- `app/Services/PaymentExperienceService.php`
- `app/Http/Controllers/Api/PaymentCallbackController.php`
- colonnes:
  - `payments.shipment_id`
  - `payments.transport_booking_id`

### Constat

- couche paiement partagee entre food, transport et colis
- callback commune
- logique d'experience paiement commune

### Qualification

- oui, paiement fortement commun
- zone sensible majeure

## 8. User dashboard commun

### Preuve

- `resources/views/frontend/profile.blade.php`
- `IndexController` expose:
  - commandes food
  - expeditions colis
- transport a aussi ses pages dediees:
  - `frontend/transport/my_bookings.blade.php`

### Constat

- compte utilisateur partage
- vues partiellement separees selon verticale

### Qualification

- oui, compte utilisateur commun
- mais experiences detaillees partiellement segmentees

## 9. Admin commun

### Preuve

- `resources/views/layouts/app.blade.php`
- `app/Http/Controllers/admin/DashboardController.php`
- `admin/ColisController`
- `admin/Transport/AdminTransportController`

### Constat

- shell admin unique
- dashboard global unique
- menus et badges pour food, colis, transport dans la meme navigation

### Qualification

- oui, admin fortement commun

## 10. Tables communes

### Preuve

- communes:
  - `payments`
  - `drivers`
- dediees:
  - `orders`, `deliveries`
  - `shipments`, `shipment_*`
  - `transport_*`

### Constat

- peu de tables metier strictement communes
- mais `payments` et `drivers` servent de points de couplage forts

### Qualification

- partage selectif, critique

## 11. Enums / statuts communs

### Preuve

- food:
  - `config/bantudelice_state_machine.php`
  - `FoodOrderStateMachineService`
- transport:
  - `App/Domain/Transport/Enums/TransportStatus`
- colis:
  - `App/Domain/Colis/Enums/ShipmentStatus`

### Constat

- chaque verticale a son propre cycle principal
- pas d'enum metier unique commun aux trois

### Qualification

- non, statuts non communs

## 12. Composants cards / listing communs

### Preuve

- layout commun
- home moderne commune
- card patterns repetees

### Constat

- proximite visuelle seulement
- pas de composant Blade partage massif entre verticales detecte

### Qualification

- partage faible a moyen, surtout stylistique

## 13. Ce qu'on peut faire sans casser

### Masquer visuellement

- liens taxi/colis de premier rang
- wording multi-services
- cartes ecosysteme ou leur ordre

### Renommer

- labels de navigation
- descriptions marketing
- metas globales

### Deplacer

- exposition taxi/colis vers footer ou bloc ecosysteme

### Garder tel quel provisoirement

- checkout food
- paiements
- dashboard admin
- APIs colis/transport
- shell admin

## 14. Zones a ne pas casser

- `app/Services/PaymentService.php`
- `app/Services/PaymentExperienceService.php`
- `app/Http/Controllers/Api/PaymentCallbackController.php`
- `config/bantudelice_modules.php`
- `resources/views/layouts/app.blade.php`
- `app/Http/Controllers/admin/DashboardController.php`

## Conclusion

Le recentrage branding peut se faire surtout dans la couche publique et editoriale. Les vraies dependances croisees a respecter sont le paiement partage, le shell admin, les flags modules, le compte utilisateur et certains points d'entree communs. Le panier et le checkout ne sont pas communs entre les trois verticales, ce qui reduit le risque si l'on reste strictement sur la navigation et le wording.
