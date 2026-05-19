# 10. Rapport avant modification

Date: 2026-03-30
Depot audite: `vps-ovh:/opt/bantudelice`

## 10.1 Cartographie

### Fichiers cles du front public

- `resources/views/frontend/index-modern.blade.php`
- `resources/views/frontend/layouts/app-modern.blade.php`
- `resources/views/frontend/layouts/app.blade.php`
- `resources/lang/fr/ui.php`
- `app/Services/ConfigService.php`
- `app/Services/CmsStaticPageService.php`
- `routes/web.php`
- `app/Http/Controllers/IndexController.php`

### Routes cles

- Food: `/`, `/restaurants`, `/cart`, `/checkout`, `/track-order`
- Colis: `/livraison-colis`, `/suivi-colis`
- Transport: `/transport/taxi`
- Guidance: `/guidance/execution`

### Composants cles

- Header moderne dans `frontend/layouts/app-modern.blade.php`
- Header/footer legacy dans `frontend/layouts/app.blade.php`
- Navigation home `nav2` et footer home dans `frontend/index-modern.blade.php`
- Traductions marketing dans `resources/lang/fr/ui.php`

### Pages cles

- Home publique moderne
- Listing restaurants
- Panier
- Checkout
- Tracking food
- Landing colis
- Tracking colis
- Landing taxi

## 10.2 Existant par verticale

- Food:
  - verticale principale pleinement branchee
  - routes web + API, restaurants, menu, panier, checkout, suivi
- Taxi:
  - verticale reelle, avec routes, domaine metier, vues frontend/admin/driver
- Colis:
  - verticale reelle, avec routes, vues frontend/admin et flux de suivi
- Services:
  - aucune plateforme metier dediee prouvee
  - existe seulement comme wording ou idee de plateforme
- Sante / Kosunga:
  - aucune route, aucun controleur, aucun modele, aucune migration prouvee
  - seulement des references ecosysteme/branding dans la home

## 10.3 Recommandation

- Changements minimums recommandes:
  - recentrer les points d'entree publics sur le food
  - declasser taxi/colis en acces secondaires sans casser leurs routes
  - brancher l'ecosysteme seulement si les URLs externes sont vraiment assumees
- Risques identifies:
  - duplication header/footer entre layout moderne et home moderne
  - retours de wording legacy via `ui.php`, `ConfigService`, `CmsStaticPageService`
  - forte sensibilite des permissions Laravel sur le VPS
- Points a ne pas toucher:
  - paiements
  - shell admin
  - flags modules
  - flux taxi/colis existants
- Strategie d'implementation minimale:
  - modifier seulement les entrees publiques et libelles de premier rang
  - ne pas ajouter de donnees en dur non branchees
  - verifier en live chaque lot avant de conclure
