<?php
// Script de vérification rapide du système de commandes
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Order;
use App\User;
use App\Restaurant;
use App\Driver;
use App\UserToken;

echo "=== VÉRIFICATION RAPIDE DU SYSTÈME ===\n\n";

// 1. Vérifier les utilisateurs
echo "1. Utilisateurs:\n";
$users = User::where('type', 'user')->count();
$restaurants = User::where('type', 'restaurant')->count();
$drivers = Driver::count();
echo "   - Clients: $users\n";
echo "   - Restaurants: $restaurants\n";
echo "   - Livreurs: $drivers\n\n";

// 2. Vérifier les commandes récentes
echo "2. Commandes récentes (5 dernières):\n";
$orders = Order::latest()->take(5)->get(['order_no', 'status', 'user_id', 'restaurant_id', 'd_lat', 'd_lng', 'created_at']);
foreach ($orders as $order) {
    echo "   - #{$order->order_no} | Statut: {$order->status} | Coordonnées: " . ($order->d_lat ? "Oui" : "Non") . "\n";
}
echo "\n";

// 3. Vérifier les tokens FCM
echo "3. Tokens FCM enregistrés:\n";
$tokens = UserToken::count();
echo "   - Total: $tokens tokens\n\n";

// 4. Vérifier les restaurants avec coordonnées
echo "4. Restaurants avec coordonnées GPS:\n";
$restaurantsWithCoords = Restaurant::whereNotNull('latitude')->whereNotNull('longitude')->count();
echo "   - Total: $restaurantsWithCoords restaurants\n\n";

// 5. Vérifier les commandes par statut
echo "5. Commandes par statut:\n";
$statuses = ['pending', 'prepairing', 'assign', 'completed', 'cancelled'];
foreach ($statuses as $status) {
    $count = Order::where('status', $status)->distinct('order_no')->count('order_no');
    echo "   - $status: $count\n";
}

echo "\n=== FIN DE LA VÉRIFICATION ===\n";
