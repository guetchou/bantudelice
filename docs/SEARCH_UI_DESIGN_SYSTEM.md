# Système de design — Recherche BantuDelice

## Objectif

La page de recherche doit rester cohérente avec l’identité BantuDelice tout en étant autonome, rapide à maintenir et utilisable sur ordinateur, tablette et mobile.

## Architecture des fichiers

- `resources/views/frontend/search-v2.blade.php` : structure et données d’affichage ;
- `public/frontend/css/pages/search.css` : styles exclusivement limités à `body.bd-search-page` ;
- `public/frontend/js/pages/search.js` : drawer mobile, synchronisation du tri, focus et états de chargement ;
- `tests/Feature/CatalogSearchWorkflowTest.php` : non-régression du rendu et des contrôles essentiels.

Aucun style spécifique à la recherche ne doit être ajouté dans `modern.css`.

## Rôles des couleurs

| Couleur | Valeur | Usage |
|---|---:|---|
| Orange BantuDelice | `#ff5a1f` | Actions principales, recherche, filtres actifs |
| Orange foncé | `#dd4310` | Hover et contraste |
| Vert Congo | `#009543` | Localisation, proximité, disponibilité |
| Ambre | `#f59e0b` | Notes et promotions |
| Bleu encre | `#122033` | Titres et informations fortes |
| Gris texte | `#526071` | Descriptions et métadonnées |

L’orange et le vert ne doivent pas être utilisés comme deux actions concurrentes. L’orange signifie « agir » ; le vert signifie « disponible ou proche ».

## Typographie

- titres : `Outfit` via `--font-display` ;
- interface et contenu : `Plus Jakarta Sans` via `--font-primary` ;
- aucune nouvelle famille de police ne doit être ajoutée à cette page.

## Composants

### Hero

- espace réservé au header fixe : 132 px sur ordinateur, 104–112 px sur mobile ;
- titre fluide avec `clamp()` ;
- recherche principale visible immédiatement ;
- recherches populaires disponibles sans saisie ;
- visuel CSS sans dépendance à une image externe.

### Filtres

- colonne sticky de 292 px sur ordinateur ;
- drawer latéral sur écrans inférieurs à 992 px ;
- focus enfermé dans le drawer ;
- fermeture avec Échap, backdrop ou bouton ;
- restitution du focus au bouton d’ouverture ;
- compteur des filtres actifs.

### Cartes

- trois colonnes sur grand écran ;
- deux colonnes sur écran intermédiaire ;
- carte horizontale sur mobile ;
- ratio d’image stable pour éviter les sauts de mise en page ;
- fallback automatique si l’image échoue ;
- titre et description limités pour conserver des hauteurs homogènes.

### États vides

Deux états sont distincts :

1. aucune recherche lancée : suggestions populaires ;
2. recherche sans résultat : suppression des filtres et accès à tous les restaurants.

## Accessibilité

Obligatoire :

- lien d’évitement vers les résultats ;
- labels explicites pour tous les champs ;
- `aria-live` pour annoncer le chargement ;
- `aria-expanded`, `aria-controls`, `aria-hidden` pour les filtres mobiles ;
- `role="dialog"` et `aria-modal` appliqués dynamiquement au drawer ;
- focus visible renforcé ;
- support de `prefers-reduced-motion` ;
- support de `prefers-contrast: more` ;
- images informatives avec texte alternatif ;
- images décoratives avec texte alternatif vide.

## Responsive

| Largeur | Comportement |
|---|---|
| `> 1180 px` | sidebar + grille 3 colonnes |
| `992–1180 px` | sidebar + grille 2 colonnes |
| `681–991 px` | drawer mobile + grille 2 colonnes |
| `≤ 680 px` | drawer mobile + cartes horizontales |
| `≤ 420 px` | toolbar empilée et médias plus compacts |

## Contrôle avant fusion

```bash
php artisan optimize:clear
php artisan test --filter=CatalogSearchWorkflowTest
php artisan view:cache
php artisan view:clear
```

Vérifications navigateur :

1. `/search` sans requête ;
2. `/search?query=pondu` avec résultats ;
3. recherche sans résultat ;
4. recherche avec position GPS ;
5. filtres actifs et réinitialisation ;
6. drawer à 375 px et 768 px ;
7. grille à 1024 px, 1280 px et 1440 px ;
8. navigation clavier complète ;
9. fermeture du drawer avec Échap ;
10. images absentes ou URL cassée ;
11. mode réduction des animations ;
12. zoom navigateur à 200 %.

## Critères d’acceptation

- aucun chevauchement avec le header ;
- aucun défilement horizontal à partir de 320 px ;
- aucun champ inaccessible au clavier ;
- aucune image déformée ;
- aucun composant de recherche ajouté au CSS global ;
- aucun texte essentiel dépendant uniquement d’une couleur ;
- aucun bouton principal vert ;
- aucun `autofocus` imposé sur mobile ;
- aucun élément interactif inférieur à environ 40 px de hauteur ;
- aucun changement fonctionnel du moteur de recherche.
