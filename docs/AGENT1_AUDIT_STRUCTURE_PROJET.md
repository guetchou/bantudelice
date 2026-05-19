# Audit 6.1 - Structure projet front public

## Portee

Lot Agent 1 uniquement:

- `6.1 Audit structure projet`

Workspace audite:

- `/opt/bantudelice/projects/bantudelice-prod-audit`

## Conclusion rapide

- Framework: Laravel `^10.10`.
- Rendu front public principal: Blade serveur.
- Pipeline front: Laravel Mix (`webpack.mix.js`) avec entree JS minimale `resources/js/app.js` et Sass `resources/sass/app.scss`.
- UI publique active: layout moderne Blade `resources/views/frontend/layouts/app-modern.blade.php`.
- Homepage publique active: `IndexController@home` -> `resources/views/frontend/index-modern.blade.php`.
- Legacy encore present: `resources/views/frontend/layouts/app.blade.php`, `resources/views/frontend/index.blade.php`, et `resources/views/frontend/transport/index.blade.php`.
- Pas de preuve d'usage Livewire, Inertia, React ou Vue cote front public actif. Seul `vue-template-compiler` reste dans `package.json`, sans point d'entree Vue detecte.

## Preuves principales

- Framework Laravel 10: `composer.json`
- Build front Mix: `package.json`, `webpack.mix.js`, `resources/js/app.js`
- Route home publique: `routes/web.php`
- Controleur home publique: `app/Http/Controllers/IndexController.php`
- Layout public moderne: `resources/views/frontend/layouts/app-modern.blade.php`
- Homepage moderne: `resources/views/frontend/index-modern.blade.php`
- Site switcher reutilise: `resources/views/frontend/partials/site_switcher.blade.php`
- Traductions front: `resources/lang/fr/ui.php`, `resources/lang/en/ui.php`
- Branding/contenu home configurable: `app/Services/ConfigService.php`, `app/Http/Controllers/admin/HomeContentController.php`

## 1. Framework et structure

- `composer.json` declare `laravel/framework: ^10.10`.
- Projet web Laravel classique:
  - `app/` pour controleurs, services, middleware.
  - `routes/` avec `web.php`, `api.php`, `channels.php`, `console.php`.
  - `resources/views/` pour Blade.
  - `resources/lang/` pour traductions.
  - `public/` pour assets compiles et images servies.
- Le front public actuel vit surtout sous `resources/views/frontend/`.

## 2. Type de rendu front

- Blade: oui, c'est le moteur principal.
  - Les vues publiques et layouts sont en `.blade.php`.
  - Les pages publiques rendent via `return view(...)` dans `IndexController`.
- Livewire: aucune preuve d'usage detectee.
- Inertia: aucune preuve d'usage detectee.
- React: aucune preuve d'usage detectee.
- Vue:
  - `vue-template-compiler` est present en dependance legacy.
  - Aucun `new Vue(`, `createApp(`, composant Vue monte, ni entree Vue n'a ete trouve dans le front public actif.
- Mix hybride:
  - Oui au sens Blade serveur + assets compiles via Laravel Mix.
  - Non au sens SPA front moderne type React/Inertia.

## 3. Routes et pages publiques reellement utilisees

- Le groupe public principal est dans `routes/web.php` sous middleware `ResolveSiteContext`.
- Route home active:
  - `/` -> `IndexController@home` -> `frontend.index-modern`
- Autres pages publiques structurelles detectees:
  - `restaurants` -> `frontend.restaurants`
  - `about-us` -> `frontend.about`
  - `contact-us` -> `frontend.contact`
  - `terms-and-conditions` -> `frontend.terms`
  - `privacy-policy` -> `frontend.privacy_policy`
  - `faq` -> `frontend.faq`
  - `help` -> `frontend.help`
  - `offers` -> `frontend.offers`
  - `politique-cookies` -> `frontend.cookies`
  - `plan-du-site` -> `frontend.sitemap`
  - `livraison-colis` -> `frontend.colis.landing`
  - `transport/*` -> plusieurs vues transport

## 4. Layouts principaux

### Layout public principal actif

- `resources/views/frontend/layouts/app-modern.blade.php`
- Contient:
  - meta `title`, `description`, `keywords`
  - header public moderne
  - menu desktop/mobile
  - include `frontend.partials.site_switcher`
  - footer public moderne
  - chargement `frontend/css/modern.css`

### Layout public legacy encore present

- `resources/views/frontend/layouts/app.blade.php`
- Ancien header/footer public encore dans le repo.
- Utilise encore par au moins:
  - `resources/views/frontend/index.blade.php`
  - `resources/views/frontend/transport/index.blade.php`

## 5. Composants UI reutilises

Partials front publics detectes et reellement referencables:

- `resources/views/frontend/partials/site_switcher.blade.php`
  - switch site/langue selon `SiteContextService`
- `resources/views/frontend/partials/restaurants_list.blade.php`
  - reutilise dans `frontend.restaurants`
- `resources/views/frontend/partials/order_chat.blade.php`
  - reutilise dans `frontend.track_order`
- `resources/views/frontend/partials/order_chat_messages.blade.php`
  - rendu HTML de messages chat commande

En plus, `app-modern.blade.php` agit lui-meme comme composant structural central pour header/footer/navigation.

## 6. Header, menu et footer actifs

### Header/menu public actif

Source:

- `resources/views/frontend/layouts/app-modern.blade.php`

Elements constates:

- logo `frontend/images/BuntuDelice.png`
- lien home
- liens services conditionnes par modules:
  - repas
  - colis
  - taxi
- dropdown suivi:
  - suivi commande food
  - suivi colis
  - mes envois si authentifie
- aide
- switcher site/langue via partial
- panier
- CTA commande
- variante mobile incluse dans le meme layout

### Footer public actif

Source:

- `resources/views/frontend/layouts/app-modern.blade.php`

Contenu:

- bloc marque
- liens rapides
- informations legales / aide / offres
- ressources
- liens bas de page: mentions legales, cookies, plan du site

### Exception notable

- `resources/views/frontend/colis/landing.blade.php`
  - page colis avec hero/footer specifiques
  - masque explicitement `.modern-header` et `.modern-footer`
  - donc layout moderne entoure la page, mais la navigation/footer visibles sont remplaces visuellement par une experience dediee.

## 7. Homepage et sections marketing

### Controleur

- `app/Http/Controllers/IndexController.php`

La methode `home()`:

- charge restaurants, produits, cuisines, categories, drivers, recommandations
- construit `serviceCards`
- fusionne `trans('ui.home')` avec `ConfigService::getHomeContent()`
- rend `frontend.index-modern`

### Vue home active

- `resources/views/frontend/index-modern.blade.php`

Sections marketing visibles dans ce fichier:

- ticker annonce
- nav custom interne `nav2`
- hero principal
- quick actions / recherche
- restaurants populaires
- recommandations
- services / cartes service
- plats populaires
- bande CTA / support
- testimonials
- opportunites
- footer custom interne `ft2`

Important:

- `index-modern.blade.php` masque le header/footer du layout avec CSS et embarque sa propre navigation `nav2` et son propre footer `ft2`.
- Donc:
  - layout principal global actif = `app-modern.blade.php`
  - homepage a aussi sa propre navigation/footer internes, dominants a l'ecran.

## 8. Branding global

Sources principales:

- `resources/views/frontend/layouts/app-modern.blade.php`
- `resources/views/frontend/index-modern.blade.php`
- `resources/lang/fr/ui.php`
- `resources/lang/en/ui.php`
- `app/Services/ConfigService.php`
- `app/Http/Controllers/admin/HomeContentController.php`

Constat:

- Branding courant encore multi-verticale:
  - `site.subtitle`: "Livraison, mobilite et colis"
  - hero/home et footer mentionnent repas + colis + taxi/transport
  - meta description par defaut du layout moderne mentionne repas, colis et transport
- Le branding home peut etre surcharge via CMS/config:
  - hero
  - services
  - support
  - opportunites
  - images home

## 9. Traductions

Fichiers detectes:

- `resources/lang/fr/ui.php`
- `resources/lang/en/ui.php`

Usage:

- navigation
- libelles home
- CTA
- wording support / services / checkout / profil

Le switch langue est branche via:

- `routes/web.php`
- `resources/views/frontend/partials/site_switcher.blade.php`

## 10. SEO / meta

### SEO public global simple

Dans `resources/views/frontend/layouts/app-modern.blade.php`:

- `title` via `@yield`
- `description` via `@yield`
- `keywords` hardcode: livraison, restaurant, colis, taxi, Congo, Brazzaville

### SEO page home

Dans `resources/views/frontend/index-modern.blade.php`:

- titre: `trans('ui.site.name') . ' — ' . trans('ui.site.subtitle')`
- description: `trans('ui.home.hero_description')`

### SEO CMS partiel

Preuves detectees:

- `app/CmsContent.php` contient `seo_title`, `seo_description`
- `app/Services/CmsContentService.php`
- `app/Services/CmsStaticPageService.php`

Conclusion:

- SEO global public existe mais reste majoritairement Blade simple.
- SEO CMS existe pour contenus/pages CMS, pas comme couche transversale unique du front public.

## 11. Fichiers reellement utilises pour le livrable 6.1

### Homepage

- `routes/web.php`
- `app/Http/Controllers/IndexController.php`
- `resources/views/frontend/index-modern.blade.php`
- `resources/lang/fr/ui.php`
- `resources/lang/en/ui.php`
- `app/Services/ConfigService.php`

### Navigation publique

- `resources/views/frontend/layouts/app-modern.blade.php`
- `resources/views/frontend/partials/site_switcher.blade.php`
- `resources/views/frontend/index-modern.blade.php`
- `routes/web.php`

### Footer

- `resources/views/frontend/layouts/app-modern.blade.php`
- `resources/views/frontend/index-modern.blade.php`
- `resources/views/frontend/colis/landing.blade.php`

### Layout principal

- `resources/views/frontend/layouts/app-modern.blade.php`

Legacy encore present mais non principal:

- `resources/views/frontend/layouts/app.blade.php`

### Sections marketing / hero / landing

- `resources/views/frontend/index-modern.blade.php`
- `resources/views/frontend/restaurants.blade.php`
- `resources/views/frontend/offers.blade.php`
- `resources/views/frontend/colis/landing.blade.php`
- `resources/views/frontend/transport/taxi.blade.php`
- `resources/views/frontend/transport/carpool.blade.php`
- `resources/views/frontend/transport/rental.blade.php`
- `resources/views/frontend/transport/bus.blade.php`

## 12. Risques de lecture pour la suite

- Double couche de navigation/footer:
  - `app-modern.blade.php`
  - `index-modern.blade.php`
  - impact fort pour toute modif branding home/header/footer
- Branding multi-services encore diffuse:
  - traductions
  - layout moderne
  - ConfigService
  - home CMS
- Legacy public encore dans le repo:
  - `frontend/layouts/app.blade.php`
  - `frontend/index.blade.php`
  - `frontend/transport/index.blade.php`
  - attention a ne pas conclure trop vite que tout passe deja par la meme coque UI.
