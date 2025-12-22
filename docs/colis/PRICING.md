# Moteur de Tarification - Module Colis

## Formule de Calcul
Le prix total est la somme des composantes suivantes :

1.  **Tarif de Base (Poids)**
    - 0 à 1 kg : 1 500 XAF
    - 1 à 5 kg : 3 000 XAF
    - 5 à 10 kg : 5 000 XAF
    - 10 à 20 kg : 8 000 XAF
    - > 20 kg : 8 000 XAF + 300 XAF par kg supplémentaire.

2.  **Options & Services**
    - **Express** : +50% du tarif de base.
    - **COD (Paiement à la livraison)** : 500 XAF (fixe) + 2% du montant à collecter.
    - **Assurance** : 1% de la valeur déclarée.

3.  **Surcharges Géographiques**
    - > 20 km : 100 XAF par km supplémentaire.

## Exemple de Calcul
- **Colis** : 2.5 kg, Express, Valeur 50 000 XAF, Distance 25 km.
- **Base** : 3 000 XAF
- **Express** : +1 500 XAF
- **Assurance** : +500 XAF
- **Distance** : +500 XAF (5km * 100)
- **TOTAL** : 5 500 XAF

## Implémentation
Le calcul est géré par `App\Domain\Colis\Services\ShipmentPricingService`.
Les résultats sont stockés en JSON dans la colonne `price_breakdown` pour traçabilité en cas de changement de grille tarifaire.
