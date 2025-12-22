# Documentation des Services

Ce document décrit tous les services disponibles dans `/app/Services` et leur utilisation.

## 📋 Liste des Services

### 1. DataSyncService
**Fichier**: `app/Services/DataSyncService.php`

**Description**: Service de synchronisation et de cache pour les données (restaurants, produits, cuisines).

**Méthodes principales**:
- `getActiveRestaurants($limit, $featured, $filters)` - Récupérer les restaurants actifs
- `getRestaurantWithData($id)` - Récupérer un restaurant avec toutes ses données
- `getRestaurantProducts($restaurantId, $featured)` - Récupérer les produits d'un restaurant
- `getFeaturedProducts($limit)` - Récupérer les produits en vedette
- `getCuisinesWithRestaurants($limit)` - Récupérer les cuisines avec restaurants
- `getRestaurantsByCuisine($cuisineId)` - Récupérer les restaurants par cuisine
- `searchRestaurants($query, $filters, $limit)` - Recherche avancée de restaurants
- `getRecommendedRestaurants($userId, $limit)` - Restaurants recommandés
- `getCacheStats()` - Statistiques du cache
- `warmupCache()` - Précharger le cache
- `invalidateRestaurantCache($restaurantId)` - Invalider le cache d'un restaurant
- `invalidateProductCache($productId)` - Invalider le cache d'un produit
- `invalidateCuisineCache($cuisineId)` - Invalider le cache d'une cuisine
- `invalidateAllCache()` - Invalider tout le cache

**Utilisation**:
```php
use App\Services\DataSyncService;

$restaurants = DataSyncService::getActiveRestaurants(8, false);
$restaurant = DataSyncService::getRestaurantWithData($id);
```

**Contrôleurs utilisant ce service**:
- `IndexController`
- `admin/RestaurantController`
- `admin/CuisineController`
- `restaurant/ProductController`

---

### 2. NotificationService
**Fichier**: `app/Services/NotificationService.php`

**Description**: Service pour envoyer des notifications FCM (Firebase Cloud Messaging) aux utilisateurs et restaurants.

**Méthodes principales**:
- `sendToDevice($deviceToken, $title, $body, $key, $userId, $type)` - Envoyer à un appareil
- `sendToMultipleDevices($deviceTokens, $title, $body, $key, $userId, $type)` - Envoyer à plusieurs appareils
- `sendWithAction($deviceToken, $title, $body, $key, $clickAction)` - Envoyer avec action personnalisée

**Utilisation**:
```php
use App\Services\NotificationService;

// Notification simple
NotificationService::sendToDevice(
    $deviceToken,
    'Nouvelle commande',
    'Vous avez reçu une nouvelle commande',
    'order',
    $userId,
    'restaurant'
);

// Notification multiple
NotificationService::sendToMultipleDevices(
    $deviceTokens,
    'Promotion',
    'Nouvelle promotion disponible',
    'promotion',
    null,
    'user'
);
```

**Contrôleurs à moderniser**:
- `admin/OrderController` (méthodes `notification` et `userNotification`)
- `restaurant/OrderController` (méthodes `notification` et `userNotification`)
- `api/OrderController` (méthode `notification`)

---

### 3. PaymentService
**Fichier**: `app/Services/PaymentService.php`

**Description**: Service pour gérer les paiements et la création de commandes.

**Méthodes principales**:
- `createOrderFromCart($orderData)` - Créer une commande depuis le panier
- `calculateTotals($cartItems, $options)` - Calculer les totaux d'une commande

**Utilisation**:
```php
use App\Services\PaymentService;

// Créer une commande
$orderNo = PaymentService::createOrderFromCart([
    'delivery_address' => '123 Rue Example',
    'd_lat' => '-4.2634',
    'd_lng' => '15.2429',
    'payment_method' => 'cash',
    'driver_tip' => 500,
    'voucher_code' => 'PROMO10'
]);

// Calculer les totaux
$totals = PaymentService::calculateTotals($cartItems, [
    'driver_tip' => 500
]);
```

**Contrôleurs à moderniser**:
- `PaypalController` (méthode `store`)
- `IndexController` (méthode `getOrders`)

---

### 4. CartService
**Fichier**: `app/Services/CartService.php`

**Description**: Service pour gérer le panier (ajout, suppression, mise à jour).

**Méthodes principales**:
- `addToCart($cartData)` - Ajouter un produit au panier
- `migrateSessionCartToDb($userId)` - Migrer le panier de session vers DB
- `getCartCount($userId)` - Obtenir le nombre d'articles
- `removeFromCart($itemId, $userId)` - Supprimer un article
- `updateQuantity($itemId, $qty, $userId)` - Mettre à jour la quantité

**Utilisation**:
```php
use App\Services\CartService;

// Ajouter au panier
$result = CartService::addToCart([
    'product_id' => 1,
    'restaurant_id' => 1,
    'qty' => 2
]);

// Obtenir le nombre d'articles
$count = CartService::getCartCount();
```

**Contrôleurs à moderniser**:
- `IndexController` (méthodes `addToCart`, `deleteItem`, `updateItem`, `getCartCount`)

---

### 5. ImageUploadService
**Fichier**: `app/Services/ImageUploadService.php`

**Description**: Service pour gérer l'upload et la suppression d'images.

**Méthodes principales**:
- `uploadImage($image, $destination, $oldImage)` - Uploader une image
- `uploadRestaurantImage($image, $type, $oldImage)` - Uploader image restaurant
- `uploadProductImage($image, $oldImage)` - Uploader image produit
- `uploadCuisineImage($image, $oldImage)` - Uploader image cuisine
- `uploadProfileImage($image, $oldImage)` - Uploader image profil
- `deleteImage($imagePath)` - Supprimer une image
- `validateImage($image, $allowedMimes, $maxSize)` - Valider une image

**Utilisation**:
```php
use App\Services\ImageUploadService;

// Uploader une image de restaurant
$filename = ImageUploadService::uploadRestaurantImage(
    $request->file('logo'),
    'logo',
    $restaurant->logo
);

// Valider une image
$validation = ImageUploadService::validateImage(
    $request->file('image'),
    ['jpeg', 'png', 'jpg'],
    2048
);
```

**Contrôleurs à moderniser**:
- `admin/RestaurantController` (upload logo/cover_image)
- `admin/CuisineController` (upload image)
- `restaurant/ProductController` (upload image)
- `IndexController` (upload avatar)
- `PartnerController` (upload logo/cover_image)

---

## 🔧 État d'Activation

### ✅ Services Actifs et Branchés
1. **DataSyncService** - ✅ Actif, utilisé dans 4 contrôleurs
2. **NotificationService** - ✅ Créé, prêt à être utilisé
3. **PaymentService** - ✅ Créé, prêt à être utilisé
4. **CartService** - ✅ Créé, prêt à être utilisé
5. **ImageUploadService** - ✅ Créé, prêt à être utilisé

### 📝 Services à Intégrer
Les services suivants sont créés mais doivent être intégrés dans les contrôleurs existants pour remplacer la logique métier actuelle.

---

## 🚀 Prochaines Étapes

1. **Intégrer NotificationService** dans les contrôleurs de commandes
2. **Intégrer PaymentService** dans PaypalController et IndexController
3. **Intégrer CartService** dans IndexController
4. **Intégrer ImageUploadService** dans tous les contrôleurs avec upload d'images
5. **Tester** tous les services après intégration

---

## 📊 Statistiques

- **Total de services**: 5
- **Services actifs**: 1 (DataSyncService)
- **Services créés**: 4 (NotificationService, PaymentService, CartService, ImageUploadService)
- **Services à intégrer**: 4

---

## 🔒 Sécurité

Tous les services incluent:
- Validation des données
- Gestion des erreurs avec logs
- Transactions de base de données
- Nettoyage des fichiers temporaires

---

**Dernière mise à jour**: {{ date('Y-m-d H:i:s') }}

