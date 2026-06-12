# Audit 6.3 - Navigation publique

## Portee

Audit de la navigation publique visible a partir des sources suivantes:

- `resources/views/frontend/layouts/app-modern.blade.php`
- `resources/views/frontend/index-modern.blade.php`
- `resources/views/frontend/partials/site_switcher.blade.php`
- `routes/web.php`

## Point structurel majeur

Deux couches de navigation publique coexistent:

- couche layout globale:
  - `resources/views/frontend/layouts/app-modern.blade.php`
- couche homepage propre:
  - `resources/views/frontend/index-modern.blade.php`

La homepage moderne masque le header/footer du layout et affiche sa propre navigation `nav2` et son propre footer `ft2`.

Conséquence:

- toute modification ou verification navigation publique doit couvrir les deux couches
- une correction appliquee seulement au layout global ne corrige pas la homepage

## 1. Header / menu desktop / menu mobile du layout public global

Source:

- `resources/views/frontend/layouts/app-modern.blade.php`

### Elements visibles

| Emplacement | Libelle affiche | Destination | Source fichier | Role produit actuel | Impact si masque / renomme |
|---|---|---|---|---|---|
| Header | Logo BantuDelice | `route('home')` | `frontend/layouts/app-modern.blade.php` | retour home | casse repere principal et retour accueil |
| Header | `Repas` | `route('restaurants.all')` | `frontend/layouts/app-modern.blade.php` | entree food principale | casse acces commande food |
| Header | `Colis` | `route('colis.landing')` si module actif | `frontend/layouts/app-modern.blade.php` | entree verticale colis | masque acces colis public |
| Header | `Taxi` | `route('transport.taxi')` si module actif | `frontend/layouts/app-modern.blade.php` | entree verticale transport | masque acces transport |
| Header | `Suivi` | dropdown | `frontend/layouts/app-modern.blade.php` | acces suivi food/colis | casse suivi commande ou colis |
| Dropdown suivi | `Suivi commande` | `route('track.order')` | `frontend/layouts/app-modern.blade.php` | suivi food | impact direct parcours post-commande |
| Dropdown suivi | `Suivi colis` | `route('colis.track_public')` | `frontend/layouts/app-modern.blade.php` | suivi colis public | impact vertical colis |
| Dropdown suivi auth | `Mes envois` | `route('colis.mes-envois')` | `frontend/layouts/app-modern.blade.php` | historique colis user | impact compte colis |
| Header | `Aide` | `route('help')` | `frontend/layouts/app-modern.blade.php` | support public | reduit acces support |
| Header actions | switcher site/langue | routes `site.switch` et `site.locale.switch` | `frontend/partials/site_switcher.blade.php` | navigation inter-sites / locale | casse multi-site et i18n |
| Header actions | puce compte | `$accountLink` selon type user | `frontend/layouts/app-modern.blade.php` | entree compte connecte | casse acces espace utilisateur |
| Header actions | panier | `route('cart.detail')` | `frontend/layouts/app-modern.blade.php` | acces panier | casse conversion checkout |
| Header actions | CTA `Commander` | `route('restaurants.all')` | `frontend/layouts/app-modern.blade.php` | CTA food principal | baisse acces commande |
| Mobile menu auth | compte / panier / favoris / logout | routes dynamiques | `frontend/layouts/app-modern.blade.php` | raccourcis compte mobile | degrade navigation mobile connectee |

### Notes de branchement

- Les liens services sont conditionnes par:
  - `config('bantudelice_modules.food.enabled')`
  - `config('bantudelice_modules.colis.enabled')`
  - `config('bantudelice_modules.transport.enabled')`
- Le switcher site/langue depend d'un vrai `siteContext`.
- Le libelle de compte varie selon le type utilisateur:
  - admin
  - restaurant
  - delivery
  - driver
  - user

## 2. Footer du layout public global

Source:

- `resources/views/frontend/layouts/app-modern.blade.php`

### Elements visibles

| Emplacement | Libelle affiche | Destination | Source fichier | Role produit actuel | Impact si masque / renomme |
|---|---|---|---|---|---|
| Footer brand | texte marque | aucun lien direct | `frontend/layouts/app-modern.blade.php` | promesse de marque publique | impact branding |
| Liens rapides | `Service colis` | `route('colis.landing')` | `frontend/layouts/app-modern.blade.php` | acces vertical colis | impact colis |
| Liens rapides | `Suivre un colis` | `route('colis.track_public')` | `frontend/layouts/app-modern.blade.php` | suivi colis public | impact SAV / tracking |
| Liens rapides | `À propos de nous` | `route('about.us')` | `frontend/layouts/app-modern.blade.php` | page corporate | impact information |
| Liens rapides | `Devenir livreur` | `route('driver')` | `frontend/layouts/app-modern.blade.php` | recrutement coursiers | impact acquisition |
| Liens rapides | `Devenir partenaire` | `route('partner')` | `frontend/layouts/app-modern.blade.php` | acquisition restos/partenaires | impact B2B |
| Liens rapides | `Nous contacter` | `route('contact.us')` | `frontend/layouts/app-modern.blade.php` | support commercial | impact contact |
| Informations | `Conditions générales` | `route('terms.conditions')` | `frontend/layouts/app-modern.blade.php` | legal | impact conformite |
| Informations | `Politique de confidentialité` | `route('privacy.policy')` | `frontend/layouts/app-modern.blade.php` | legal data | impact conformite |
| Informations | `Politique de remboursement` | `route('refund.policy')` | `frontend/layouts/app-modern.blade.php` | SAV / legal | impact confiance |
| Informations | `FAQ` | `route('faq')` | `frontend/layouts/app-modern.blade.php` | self-help | impact support |
| Informations | `Centre d'aide` | `route('help')` | `frontend/layouts/app-modern.blade.php` | support | impact support |
| Informations | `Offres et promotions` | `route('offers')` | `frontend/layouts/app-modern.blade.php` | marketing / acquisition | impact promo |
| Informations | `Suppression des données` | `route('data.deletion')` | `frontend/layouts/app-modern.blade.php` | conformite | impact legal |
| Informations | `Guidance execution` | `route('guidance.execution')` | `frontend/layouts/app-modern.blade.php` | page interne/execution exposee | sensible, impact faible produit mais fort gouvernance |
| Ressources | `Suivre une commande` | `route('track.order')` | `frontend/layouts/app-modern.blade.php` | post-achat food | impact suivi |
| Ressources | `Voir les offres` | `route('offers')` | `frontend/layouts/app-modern.blade.php` | marketing | impact promo |
| Ressources | `Centre d'aide` | `route('help')` | `frontend/layouts/app-modern.blade.php` | support | impact support |
| Footer bas | `Mentions légales` | `route('legal.notices')` | `frontend/layouts/app-modern.blade.php` | legal | impact conformite |
| Footer bas | `Cookies` | `route('cookies.policy')` | `frontend/layouts/app-modern.blade.php` | legal | impact conformite |
| Footer bas | `Plan du site` | `route('site.map')` | `frontend/layouts/app-modern.blade.php` | SEO / navigation | impact crawl/navigation |

## 3. Homepage: navigation et CTA propres a `index-modern`

Source:

- `resources/views/frontend/index-modern.blade.php`

## 3.1 Ticker

### Elements visibles

| Libelle / contenu | Destination | Source fichier | Role produit actuel | Impact |
|---|---|---|---|---|
| noms de restaurants marquee | aucun lien | `frontend/index-modern.blade.php` | reassurance / animation | faible |
| `Commandez maintenant` | aucun lien | `frontend/index-modern.blade.php` | marketing food | faible |
| `Livraison en 20–40 min` | aucun lien | `frontend/index-modern.blade.php` | promesse service | faible |
| `Brazzaville · Pointe-Noire` | aucun lien | `frontend/index-modern.blade.php` | ancrage geographique | faible |

## 3.2 Nav homepage `nav2`

### Elements visibles

| Emplacement | Libelle affiche | Destination | Source fichier | Role produit actuel | Impact si masque / renomme |
|---|---|---|---|---|---|
| Nav home | logo BantuDelice | `route('home')` | `frontend/index-modern.blade.php` | retour home | repere principal |
| Nav home | `Restaurants` | `route('restaurants.all')` | `frontend/index-modern.blade.php` | entree food principale | casse acquisition food |
| Nav home | `Offres` | `route('offers')` | `frontend/index-modern.blade.php` | marketing | impact promo |
| Nav home dropdown | `Nos Plateformes` | ouvre menu | `frontend/index-modern.blade.php` | orientation ecosysteme | impact navigation ecosysteme |
| Dropdown plateformes | `Transport` | `route('transport.taxi')` si module actif | `frontend/index-modern.blade.php` | transport soeur / verticale connexe | impact transport |
| Dropdown plateformes | `Salisa` | aucun, element inactif | `frontend/index-modern.blade.php` | tease ecosysteme freelance | faible mais branding sensible |
| Dropdown plateformes | `Kosunga` | aucun, element inactif | `frontend/index-modern.blade.php` | tease ecosysteme sante | faible mais branding sensible |
| Nav home | `Partenaires` | `route('partner')` | `frontend/index-modern.blade.php` | acquisition B2B | impact recrutement partenaires |
| Nav home actions | faux champ `Rechercher…` | aucun | `frontend/index-modern.blade.php` | affordance visuelle non branchee | risque UX car non fonctionnel |
| Nav home actions | panier | `route('cart')` | `frontend/index-modern.blade.php` | acces panier | conversion |
| Nav home actions | bouton compte | `$accountLink` | `frontend/index-modern.blade.php` | acces compte | navigation user |
| Nav home actions | burger mobile | JS local | `frontend/index-modern.blade.php` | nav mobile home | impact mobile |

### Point sensible

- Le pseudo champ `Rechercher…` est uniquement visuel, sans lien ni branchement.
- C'est un vrai point a brancher ou a neutraliser plus tard, sinon il donne une fausse affordance.

## 3.3 Hero homepage

### Elements visibles

| Libelle affiche | Destination | Source fichier | Role produit actuel | Impact |
|---|---|---|---|---|
| badge `Livraison de repas · Congo` | aucune | `frontend/index-modern.blade.php` | positionnement food | branding |
| CTA `Commander maintenant` | `route('restaurants.all')` | `frontend/index-modern.blade.php` | CTA principal conversion | critique |
| CTA `Devenir partenaire` | `route('partner')` | `frontend/index-modern.blade.php` | acquisition partenaires | fort |
| `Voir tous les restaurants` | `route('restaurants.all')` | `frontend/index-modern.blade.php` | conversion secondaire | fort |

## 3.4 Sections homepage et CTA internes

### Comment ca marche

- pas de lien interne direct
- role: education parcours

### Restaurants populaires

| Libelle | Destination | Source | Role | Impact |
|---|---|---|---|---|
| `Voir tout` | `route('restaurants.all')` | `frontend/index-modern.blade.php` | listing complet | critique |
| `Commander` sur card resto | `route('resturant.detail', $restaurant->id)` | `frontend/index-modern.blade.php` | entree menu restaurant | critique |

### Plats a decouvrir

| Libelle | Destination | Source | Role | Impact |
|---|---|---|---|---|
| `Explorer` | `route('restaurants.all')` | `frontend/index-modern.blade.php` | retour listing | fort |
| `Voir le plat` | `route('pro.detail', $product->id)` | `frontend/index-modern.blade.php` | fiche produit | critique |

### Journey / parcours livraison

| Libelle | Destination | Source | Role | Impact |
|---|---|---|---|---|
| `Commander maintenant` | `route('restaurants.all')` | `frontend/index-modern.blade.php` | conversion food | critique |

### Food gallery

| Libelle | Destination | Source | Role | Impact |
|---|---|---|---|---|
| `Explorer les restaurants` | `route('restaurants.all')` | `frontend/index-modern.blade.php` | conversion food | critique |

### Impact band

| Libelle | Destination | Source | Role | Impact |
|---|---|---|---|---|
| `Commander maintenant` | `route('restaurants.all')` | `frontend/index-modern.blade.php` | conversion food | critique |

### Ecosysteme hub

| Carte | Destination | Source | Role produit actuel | Impact |
|---|---|---|---|---|
| `BantuDelice` | `route('restaurants.all')` | `frontend/index-modern.blade.php` | verticale active food | critique |
| `Transport` | `route('transport.taxi')` si module actif | `frontend/index-modern.blade.php` | verticale connexe active | impact transport |
| `Salisa` | aucun, disabled | `frontend/index-modern.blade.php` | tease ecosysteme | branding / UX |
| `Kosunga` | aucun, disabled | `frontend/index-modern.blade.php` | tease ecosysteme | branding / UX |

### Opportunites

Les cartes sont branchees soit au CMS `homeContent`, soit aux fallbacks routes:

| Carte | Destination par defaut | Source | Role | Impact |
|---|---|---|---|---|
| `Devenir coursier` | `route('driver')` | `frontend/index-modern.blade.php` | acquisition coursiers | fort |
| `Devenir partenaire` | `route('partner')` | `frontend/index-modern.blade.php` | acquisition restos/commerces | fort |
| `Rejoindre l'equipe` | `route('contact.us')` | `frontend/index-modern.blade.php` | recrutement / contact | moyen |

### Social

Liens externes reels:

- Facebook
- Instagram
- WhatsApp
- TikTok

Role:

- distribution / communaute / support

Impact si masques:

- faible fonctionnel
- moyen marketing

## 3.5 Footer homepage `ft2`

Source:

- `resources/views/frontend/index-modern.blade.php`

### Elements visibles

| Bloc | Libelle | Destination | Role produit actuel | Impact |
|---|---|---|---|---|
| Brand | logo BantuDelice | `route('home')` | repere home | faible |
| Livraison repas | `Explorer les restaurants` | `route('restaurants.all')` | food | critique |
| Livraison repas | `Suivi de commande` | `route('track.order')` | post-achat | critique |
| Livraison repas | `Offres et promotions` | `route('offers')` | marketing | moyen |
| Livraison repas | `Questions fréquentes` | `route('faq')` | support | moyen |
| Partenaires | `Devenir restaurant partenaire` | `route('partner')` | acquisition B2B | fort |
| Partenaires | `Devenir livreur` | `route('driver')` | acquisition coursiers | fort |
| Partenaires | `Contacter l'équipe` | `route('contact.us')` | support / contact | moyen |
| Informations | `Conditions générales` | `route('terms.conditions')` | legal | fort |
| Informations | `Confidentialité` | `route('privacy.policy')` | legal | fort |
| Informations | `Centre d'aide` | `route('help')` | support | moyen |
| Informations | `Nous contacter` | `route('contact.us')` | contact | moyen |
| Informations | `Plan du site` | `route('site.map')` | SEO/nav | moyen |
| Bas footer | `Mentions légales` | `route('legal.notices')` | legal | fort |
| Bas footer | `Cookies` | `route('cookies.policy')` | legal | fort |
| Bas footer | `Plan du site` | `route('site.map')` | SEO/nav | moyen |

## 4. Widgets marketing et cartes de services

### Widgets / patterns marketing detectes

- ticker marquee
- hero principal
- chipset categorie hero
- carte "Restaurants du moment"
- steps "Comment ca marche"
- cards restaurants
- cards produits
- timeline journey
- mockup mobile tracker
- galerie food
- impact band
- testimonials
- ecosysteme hub
- opportunites
- bloc application mobile
- bloc social
- chiffres cles

Sources:

- `resources/views/frontend/index-modern.blade.php`

## 5. Liens internes et externes sensibles

### Liens internes critiques

- `restaurants.all`
- `resturant.detail`
- `pro.detail`
- `cart.detail` / `cart`
- `track.order`
- `partner`
- `driver`
- `help`
- `offers`

### Liens externes critiques

- Facebook
- Instagram
- WhatsApp
- TikTok

### Elements non branches ou semi-branches

- champ `Rechercher…` dans `nav2`: visuel seulement
- cartes `Salisa` et `Kosunga`: affichage disabled, pas de destination
- dropdown ecosysteme homepage: une seule verticale vraiment cliquable selon module (`Transport`)

## 6. Risques de regression

- double source de header/footer:
  - `app-modern`
  - `index-modern`
- homepage contient beaucoup de CTA hardcodes en Blade meme lorsqu'ils pointent vers de vraies routes
- certains blocs ecosysteme sont volontairement non branches:
  - ne pas convertir en liens sans source reelle validee
- le champ de recherche homepage parait interactif alors qu'il ne l'est pas encore
- le footer global et le footer home ne portent pas exactement le meme message ni les memes liens

## Conclusion

La navigation publique est riche mais fragmentee:

- navigation globale du layout moderne
- navigation autonome de la homepage
- footer global
- footer home

Pour l'etape suivante, les points les plus importants a verifier avant tout branchement UI sont:

- coherence des CTA home vs layout
- gestion des faux affordances
- respect du branding deja deploye
- absence de lien ajoute sans branchement reel
