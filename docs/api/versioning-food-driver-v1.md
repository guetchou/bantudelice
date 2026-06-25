# Versionnement API Food / Driver — V1

## Principe

Les nouvelles routes sont exposées sous :

- `/api/v1/food/...`
- `/api/v1/driver/...`

Les routes historiques restent actives. Aucun endpoint existant n'est supprimé par ce chantier.

## Correspondances principales

| Route historique | Route V1 |
|---|---|
| `POST /api/driver_register` | `POST /api/v1/driver/register` |
| `POST /api/driver_login` | `POST /api/v1/driver/login` |
| `GET /api/driver/deliveries` | `GET /api/v1/driver/deliveries` |
| `PATCH /api/driver/deliveries/{delivery}/status` | `PATCH /api/v1/driver/deliveries/{delivery}/status` |
| `POST /api/driver/deliveries/{delivery}/incident` | `POST /api/v1/driver/deliveries/{delivery}/incident` |
| `POST /api/driver/{driverId}/location` | `POST /api/v1/driver/{driverId}/location` |
| `POST /api/checkout` | `POST /api/v1/food/checkout` |
| `GET /api/order/{orderNo}/status` | `GET /api/v1/food/orders/{orderNo}/status` |
| `GET /api/orders/{order}/tracking` | `GET /api/v1/food/orders/{order}/tracking` |
| `POST /api/orders/{order}/confirm-delivery` | `POST /api/v1/food/orders/{order}/confirm-delivery` |
| `GET /api/user/orders/active` | `GET /api/v1/food/user/orders/active` |
| `GET /api/user/orders/completed` | `GET /api/v1/food/user/orders/completed` |

Les favoris, le profil utilisateur, les appareils push, les paiements et les évaluations disposent également d'un miroir V1.

## Authentification

- les routes livreur opérationnelles utilisent `auth:driver_api` ;
- les routes food client utilisent `auth.web_or_api` ;
- l'ensemble reste protégé par `module:food` ;
- l'inscription et la connexion livreur conservent leurs limites de débit.

## Exceptions volontaires

Les callbacks externes de paiement ne sont pas déplacés. Leur URL est un contrat avec les fournisseurs et reste stable :

`POST /api/payments/callback/{provider}`

Le bridge Mobile Money reste également hors du préfixe V1 pour ne pas casser les intégrations existantes.

## Migration

1. Publier une version du client mobile capable d'utiliser les routes V1.
2. Mesurer l'utilisation résiduelle des anciennes routes dans les journaux.
3. Corriger les derniers consommateurs.
4. Revoir le retrait des routes historiques le **31 janvier 2027**.

Cette date est une date de revue, pas une suppression automatique. Aucune route historique ne doit être retirée sans preuve qu'elle n'est plus consommée et sans nouvelle PR dédiée.
