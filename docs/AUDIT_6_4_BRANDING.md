# Audit 6.4 - Branding public

## Portee

Audit cible du branding public dans:

- `resources/lang/fr/ui.php`
- `resources/views/frontend/layouts/app-modern.blade.php`
- `resources/views/frontend/index-modern.blade.php`
- `resources/views/frontend/colis/landing.blade.php`
- `resources/views/frontend/transport/taxi.blade.php`
- `app/Http/Controllers/IndexController.php`

## Conclusion rapide

- Le branding public actuel n'est pas food-only.
- BantuDelice est presente comme une plateforme unifiee repas + colis + transport.
- Cette presentation multi-verticale vient a la fois:
  - des traductions
  - des valeurs par defaut CMS/config
  - du layout global
  - de la homepage moderne
  - des landings taxi/colis
- Cette logique apparait dans:
  - les metas globales
  - le sous-titre du site
  - les textes de hero
  - les cartes service
  - les testimonials
  - les footers
  - les pages dediees taxi/colis
- Le code contient deja une base exploitable pour recentrer sans rearchitecture:
  - textes traduits centralises
  - `serviceCards` construites dans `IndexController@home`
  - homepage Blade unique

## Preuves sourcées

### 1. Sous-titre global de marque

Dans `resources/lang/fr/ui.php`:

- `site.subtitle`: `Livraison, mobilité et colis`

Constat:

- ligne 6: positionnement multi-vertical explicite

### 2. Hero home multi-services

Dans `resources/lang/fr/ui.php`:

- `hero_title_line_1`: `Commander un repas.`
- `hero_title_line_2`: `Suivre un colis. Réserver un taxi.`
- `hero_description`: `Choisissez un service et lancez votre action tout de suite.`
- `services_title`: `Trois services, une seule interface`
- `services_subtitle`: `Repas, colis et transport dans une même expérience.`

Constats:

- lignes 47-49
- lignes 62-67
- plusieurs libelles clefs restent orientes "super app" et alimentent encore le CMS/home legacy

### 3. Cartes service construites cote controleur

Dans `app/Http/Controllers/IndexController.php`:

- la home cree 6 cartes:
  - restaurants
  - produits
  - cuisines
  - suivi food
  - parcels
  - transport

Constat:

- lignes 128-171: `serviceCards` inclut encore `parcels` et `transport`

### 3 bis. Defauts CMS/config encore multi-services

Dans `app/Services/ConfigService.php`:

- `hero_badge`: `Brazzaville • repas, colis, transport`
- `hero_description`: `Une interface claire pour commander un repas, envoyer un colis ou réserver un trajet local sans perdre de temps.`
- `services_title`: `Trois services, une seule interface`
- `services_subtitle`: `BantuDelice rassemble la commande de repas, l'envoi de colis et la réservation de transport...`
- `support_description`: `Notre équipe peut vous orienter selon votre besoin: repas, transport ou livraison de colis.`

Constats:

- lignes 96-115: valeurs de fallback `getLegacyHomeContent()`
- lignes 123-141: valeurs de fallback `getHomeContentDefaults()`
- meme si le branding de la home a ete redeploye, la couche de secours reste editorialement multi-services
- risque: reaffichage partiel de vieux textes si le contenu CMS manque ou est invalide

### 4. Layout global multi-services

Dans `resources/views/frontend/layouts/app-modern.blade.php`:

- title par defaut: `BantuDelice - Livraison à domicile`
- meta description par defaut: `Livraison de repas, colis et transport à Brazzaville.`
- keywords: `livraison, restaurant, colis, taxi, Congo, Brazzaville`
- footer brand: `livraison de repas, colis et services urbains`

Constats:

- lignes 7-9: metas globales
- ligne 832: descriptif footer

### 5. Homepage moderne multi-verticale

Dans `resources/views/frontend/index-modern.blade.php`:

- le ticker home inclut la description transport
- testimonials fallback incluent:
  - `Service colis`
  - `Plateforme complete`
  - `Repas, colis et transport dans une seule appli`
- les opportunites parlent de livrer `repas et colis`
- le dropdown `Nos Plateformes` expose:
  - `Transport`
  - `Salisa`
  - `Kosunga`
- le champ `Rechercher…` reste seulement decoratif, sans branchement de navigation
- la home affiche sa propre navigation et son propre footer, en plus du layout global masque

Constats:

- lignes 19-32: ticker et testimonials fallback
- lignes 44-46: opportunites
- lignes 997-1074: nav home propre
- lignes 1083-1151: hero food-first deja deploye

### 5 bis. Branding deja redeploye a preserver

Dans `resources/views/frontend/index-modern.blade.php`:

- hero principal actuellement food-first:
  - `Livraison de repas · Congo`
  - `Tout ce dont vous avez besoin, livré chez vous.`
  - `Explorez les meilleurs restaurants...`
- cette zone ne doit pas etre retouchee sans demande explicite

Constats:

- lignes 1085-1116: hero principal coherent avec le recentrage food
- le risque n'est pas le hero redeploye, mais les couches secondaires encore multi-verticales autour

### 6. Landing colis assume encore la marque BantuDelice

Dans `resources/views/frontend/colis/landing.blade.php`:

- titre SEO: `BantuDelice — Livraison de colis au Congo`
- description SEO: experience colis `branchee sur les routes reelles de BantuDelice`
- nav et hero gardent la marque BantuDelice

Constats:

- lignes 3-4: metas colis
- lignes 69-71: nav colis
- lignes 107-130: hero et quick actions

### 7. Landing taxi encore sous marque BantuDelice

Dans `resources/views/frontend/transport/taxi.blade.php`:

- nav signee BantuDelice
- footer:
  - `Votre partenaire pour la livraison de repas, l'expedition de colis et le transport urbain.`

Constats:

- ligne 613: nav BantuDelice
- lignes 999-1001: footer taxi multi-vertical

## Qualification du branding actuel

| Zone | Qualification |
|---|---|
| `resources/lang/fr/ui.php` | multi-services explicite |
| `app-modern` metas + footer | food + colis + transport |
| `index-modern` | super-app / plateforme unifiee |
| landing colis | verticale interne BantuDelice |
| landing taxi | verticale interne BantuDelice |

## Analyse d'impact minimal

### Ce qui peut etre recentre rapidement

- `resources/lang/fr/ui.php` et equivalent EN
- `IndexController@home` pour les `serviceCards`
- `app/Services/ConfigService.php` pour les fallbacks CMS/home
- `index-modern.blade.php` pour:
  - ticker fallback
  - testimonials fallback
  - dropdown `Nos Plateformes`
  - opportunites / wording multi-vertical restant
- `app-modern.blade.php` pour les metas et le footer global

### Ce qu'il vaut mieux ne pas casser

- le hero food-first deja redeploye dans `index-modern`
- routes colis et transport deja actives
- pages dediees `colis` et `taxi`, tant qu'aucune redirection externe n'est decidee
- wording fonctionnel du suivi, support et dashboards internes

## Zones de preuve a suivre avant implementation

| Zone | Fichier | Nature du risque | Action minimale recommandee |
|---|---|---|---|
| Traductions home | `resources/lang/fr/ui.php` / `resources/lang/en/ui.php` | wording multi-services reutilisable ailleurs | corriger seulement les cles publiques encore actives |
| Fallback CMS home | `app/Services/ConfigService.php` | retour de vieux textes si contenu CMS absent | aligner les valeurs de secours sur le positionnement food-first |
| Cartes services home | `app/Http/Controllers/IndexController.php` | cartes `parcels` et `transport` encore centrales | declasser vers ecosysteme plutot que supprimer brutalement |
| Layout global | `resources/views/frontend/layouts/app-modern.blade.php` | metas et footer encore multi-services | corriger SEO/footer globaux sans casser les liens reels |
| Home moderne | `resources/views/frontend/index-modern.blade.php` | nav secondaire et fallback encore hybrides | ne modifier que les zones non redeployees et non purement visuelles |
| Landings taxi/colis | `resources/views/frontend/transport/taxi.blade.php`, `resources/views/frontend/colis/landing.blade.php` | confusion marque principale vs plateforme soeur | preparer evolution vers ecosysteme sans casser routes ni CTA |

## Recommandation d'impact minimal

1. Repositionner BantuDelice comme food-first dans toutes les metas et textes globaux.
2. Remplacer le discours `une seule interface pour tout` par:
   - food en marque principale
   - ecosysteme / plateformes soeurs pour le reste
3. Sortir `colis` et `transport` des cartes de service centrales de la home, ou les declasser dans une section ecosysteme.
4. Conserver temporairement les landings dediees colis/taxi, mais reduire les affirmations de marque globale si on ne bascule pas encore vers `memela.cg` et `nzila.cg`.
5. Introduire ensuite les marques soeurs reelles:
   - `Nzila` pour taxi
   - `Memela` pour colis
   - `Salisa` pour services
   - `Kosunga` pour sante

## Conclusion

Le branding public est aujourd'hui hybride: la homepage a deja une couche hero food-first, mais le reste de la pile publique continue de presenter BantuDelice comme une plateforme unifiee repas + colis + transport. Le chantier prioritaire reste editorial, navigationnel et de fallback CMS, pas metier. Avant toute etape 2, il faut brancher et aligner les sources reelles encore actives sans injecter de donnees en dur ni casser les routes taxi/colis existantes.
