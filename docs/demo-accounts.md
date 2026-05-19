# Comptes démo BantuDelice

Commande de provisionnement :

```bash
php artisan demo:provision-accounts
```

Mot de passe par défaut :

```text
BantuDemo2026!
```

Comptes créés ou mis à jour :

- `demo.admin@bantudelice.cg`
- `demo.client@bantudelice.cg`
- `demo.restaurant@bantudelice.cg`
- `demo.livreur@bantudelice.cg`
- `demo.taxi@bantudelice.cg`

Notes :

- le compte restaurant est relié au premier restaurant approuvé
- le compte livreur est relié à un livreur avec activité de livraison
- le compte taximan est relié à un chauffeur avec activité transport ou véhicule actif
- le compte taximan est authentifié comme `driver`, puis utilise le dashboard transport via `/driver/transport`
