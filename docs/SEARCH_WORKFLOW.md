# Workflow de recherche BantuDelice

## 1. Objectif

Fournir un moteur unique pour rechercher :

- un restaurant ;
- un plat ;
- une cuisine ;
- une catégorie ;
- une enseigne ou une ville ;
- des restaurants proches d’une position GPS.

La recherche ne doit jamais confondre le mot-clé métier avec le libellé d’une adresse.

## 2. Routes de référence

| Route | Usage |
|---|---|
| `GET /search` | Page publique de recherche |
| `GET /search/ajax` | Réponse JSON compatible avec l’ancien frontend |
| `GET /search/api` | Réponse JSON publique normalisée |
| `GET /restaurants` | Liste historique ; redirige vers le moteur global lorsqu’un mot-clé ou une position est fourni |

Les routes sont déclarées dans `routes/search.php` et chargées après `routes/web.php`. Cette priorité permet de remplacer les anciennes définitions sans supprimer les liens historiques.

## 3. Paramètres canoniques

| Paramètre | Rôle |
|---|---|
| `query` | Restaurant, plat, cuisine, catégorie ou ville recherchée |
| `latitude` | Latitude GPS, entre -90 et 90 |
| `longitude` | Longitude GPS, entre -180 et 180 |
| `location_label` | Adresse lisible affichée à l’utilisateur ; jamais utilisée comme mot-clé métier lorsqu’elle provient du GPS |
| `city` | Filtre de ville |
| `cuisine_id` | Filtre de cuisine |
| `min_rating` | Note minimale entre 0 et 5 |
| `max_delivery_fee` | Frais de livraison maximum |
| `min_price`, `max_price` | Fourchette de prix des plats |
| `featured` | Éléments en vedette uniquement |
| `sort` | Tri des restaurants |
| `product_sort` | Tri des plats |

Compatibilité temporaire : `q`, `qurey` et `searchTerm` sont encore acceptés comme alias de `query`. Les nouveaux développements doivent uniquement envoyer `query`.

## 4. Séquence fonctionnelle

### Recherche textuelle

1. L’utilisateur saisit un mot-clé.
2. Le contrôleur normalise et valide les paramètres.
3. Le service interroge les restaurants, leurs cuisines et leurs plats.
4. Le service interroge les plats, leur restaurant et leur catégorie.
5. Les candidats sont classés par pertinence, note, popularité et filtres demandés.
6. Seuls les restaurants approuvés et les produits disponibles sont retournés.

### Recherche GPS

1. Le navigateur fournit latitude et longitude.
2. L’adresse lisible est enregistrée dans `location_label`.
3. Le mot-clé `query` reste vide, sauf saisie explicite de l’utilisateur.
4. Les restaurants sont classés selon la distance et les autres critères.
5. Une recherche textuelle peut ensuite être ajoutée sans perdre la position.

### Ancienne barre d’accueil

1. L’accueil envoie encore `search`, `lat` et `lng` vers `/restaurants`.
2. `SearchEntryController` convertit ces paramètres.
3. Sans coordonnées, `search` devient `query`.
4. Avec coordonnées, `search` devient `location_label` et ne filtre pas le catalogue.
5. Les anciens filtres sont conservés pendant la redirection.

## 5. Contrôles de sécurité et de qualité

- longueur du mot-clé limitée à 120 caractères ;
- coordonnées bornées et exigées par paire ;
- prix et frais obligatoirement positifs ;
- note comprise entre 0 et 5 ;
- valeurs de tri limitées à une liste blanche ;
- limitation de débit à 60 requêtes par minute ;
- restaurants non approuvés exclus ;
- produits indisponibles exclus ;
- aucune clé externe ni donnée sensible exposée dans la réponse.

## 6. Tests de non-régression

Exécuter :

```bash
php artisan optimize:clear
php artisan route:list --path=search
php artisan route:list --path=restaurants
php artisan test --filter=CatalogSearchWorkflowTest
```

Scénarios obligatoires :

1. `/search` s’ouvre sans boucle de redirection.
2. `/search?query=pondu` affiche les restaurants et plats correspondants.
3. `/search?qurey=pondu` reste compatible.
4. `/restaurants?search=pondu` redirige vers `/search?query=pondu`.
5. Une recherche GPS conserve les coordonnées sans transformer l’adresse en mot-clé.
6. Un restaurant non approuvé n’apparaît jamais.
7. Un produit indisponible n’apparaît jamais.
8. Une coordonnée invalide produit une réponse 422 sur l’API.
9. Une recherche vide sur l’API retourne des tableaux vides et un schéma stable.

## 7. Vérification après déploiement

```bash
php artisan optimize:clear
php artisan route:list --path=search
curl -I 'https://bantudelice.cg/search'
curl -s 'https://bantudelice.cg/search/api?query=pondu'
```

Contrôler ensuite dans le navigateur :

- lien Recherche du menu ;
- barre de recherche de l’accueil ;
- recherche d’un restaurant ;
- recherche d’un plat ;
- sélection d’une cuisine ;
- localisation GPS autorisée et refusée ;
- affichage mobile ;
- absence d’erreur dans `storage/logs/laravel.log`.

## 8. Retour arrière

En cas d’incident :

1. revenir au commit précédent ou annuler la fusion de la pull request ;
2. exécuter `php artisan optimize:clear` ;
3. vérifier que les anciennes routes `/search` et `/restaurants` répondent ;
4. conserver les journaux et la requête ayant provoqué l’incident avant toute nouvelle correction.

Aucune migration de base de données n’est nécessaire pour cette correction.
