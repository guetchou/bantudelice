# Contrat API — collecte cash livreur

## Endpoint

`GET /api/driver/deliveries`

Authentification : guard `driver_api`.

Les champs existants du payload sont conservés. Les champs suivants sont ajoutés à chaque livraison :

```json
{
  "payment_method": "cash",
  "payment_status": "paid",
  "cash_collection_status": "pending_collection",
  "cash_collection": {
    "required": true,
    "status": "pending_collection",
    "amount": 6500,
    "badge": "Espèces à collecter",
    "attention_required": true
  }
}
```

## Règles d'affichage recommandées

| Statut | Badge | Attention |
|---|---|---|
| `pending_collection` | Espèces à collecter | Oui |
| `collected` | Espèces collectées | Non |
| `collection_failed` | Collecte échouée | Oui |
| `disputed` | Collecte contestée | Oui |
| paiement non cash | aucun badge | Non |

L'application livreur doit rendre le badge fourni dans `cash_collection.badge` et afficher le montant `cash_collection.amount` avant la confirmation de livraison.

## Mise à jour du statut

`PATCH /api/driver/deliveries/{delivery}/status`

Pour une livraison cash, la requête peut préciser :

```json
{
  "status": "DELIVERED",
  "customer_confirmed": true,
  "cash_collection_outcome": "collected"
}
```

Valeurs autorisées pour `cash_collection_outcome` :

- `collected`
- `collection_failed`

La réponse de mise à jour renvoie également `payment_method`, `cash_collection_status` et l'objet `cash_collection` actualisé.

## Compatibilité

Le changement est additif. Aucun champ historique n'est supprimé ou renommé. Les consommateurs qui ne connaissent pas les nouveaux champs continuent de fonctionner.
