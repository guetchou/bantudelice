# Guide des Seeds & Données Congo - Module Colis

## 1. Zones Géographiques (Exemples Congo)

Pour le MVP, les zones sont gérées de manière flexible. Voici les quartiers et zones recommandés pour les tests et la mise en production initiale :

### Brazzaville (Z1)
- **Quartiers Centraux** : Poto-Poto, Moungali, Ouenzé.
- **Zone Sud** : Bacongo, Makélékélé.
- **Zone Nord** : Talangaï, Djiri.

### Pointe-Noire (Z2)
- **Centre** : Lumumba, Tié-Tié.
- **Périphérie** : Loandjili, Mongo-Mpoukou.

## 2. Exemple de Seed SQL (Pricing Placeholder)

Bien que le pricing soit actuellement codé en dur dans le service pour la robustesse du MVP, voici comment préparer une table de zones si vous décidez d'externaliser :

```sql
-- Table fictive pricing_zones
INSERT INTO pricing_zones (name, city, base_surcharge) VALUES
('Centre-Brazza', 'Brazzaville', 0),
('Sud-Brazza', 'Brazzaville', 500),
('Nord-Brazza', 'Brazzaville', 700),
('Inter-villes', 'Brazzaville-PointeNoire', 5000);
```

## 3. Données de Test (Factories)
Utiliser les factories fournies pour générer des données de test réalistes au Congo :

```php
// Générer 10 colis avec adresses à Brazzaville
\App\Domain\Colis\Models\Shipment::factory()
    ->count(10)
    ->has(\App\Domain\Colis\Models\ShipmentAddress::factory()->state(['city' => 'Brazzaville']), 'addresses')
    ->create();
```

## 4. Points de Repère (Landmarks)
L'adressage au Congo reposant souvent sur des points de repère, le champ `landmark` est CRITIQUE. Exemples à fournir aux testeurs :
- "En face de la pharmacie de la Paix"
- "À côté de l'école primaire de Moungali"
- "Près du château d'eau"

