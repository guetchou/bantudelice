<?php

namespace App\Console\Commands;

use App\Driver;
use App\DriverLocation;
use App\Order;
use App\Delivery;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * Commande Artisan pour tester la géolocalisation
 * 
 * Usage: php artisan test:geolocation [--driver-id=1] [--order-id=123]
 */
class TestGeolocation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:geolocation {--driver-id= : ID du livreur} {--order-id= : ID de la commande}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tester le système de géolocalisation (mise à jour position + récupération)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🧪 Test de Géolocalisation');
        $this->line('');

        // 1. Vérifier qu'il y a des livreurs
        $driverId = $this->option('driver-id');
        if (!$driverId) {
            // Chercher n'importe quel livreur (même sans coordonnées)
            $driver = Driver::first();
            
            if (!$driver) {
                $this->warn('Aucun livreur dans la base de données. Création d\'un livreur de test...');
                // Trouver un restaurant existant
                $restaurant = \App\Restaurant::first();
                if (!$restaurant) {
                    $this->error('Aucun restaurant dans la base de données. Impossible de créer un livreur de test.');
                    return 1;
                }
                // Créer un livreur de test
                $driver = Driver::create([
                    'restaurant_id' => $restaurant->id,
                    'name' => 'Livreur Test GPS',
                    'email' => 'test-driver-' . time() . '@bantudelice.cg',
                    'phone' => '+242' . rand(100000000, 999999999),
                    'password' => bcrypt('password123'),
                    'latitude' => -4.2767,
                    'longitude' => 15.2832,
                    'status' => 'online',
                ]);
                $driverId = $driver->id;
                $this->info("✅ Livreur de test créé (ID: {$driverId})");
            } else {
                $driverId = $driver->id;
                
                // Si le livreur n'a pas de coordonnées, les initialiser
                if (!$driver->latitude || !$driver->longitude) {
                    $this->warn('Le livreur n\'a pas de coordonnées GPS. Initialisation...');
                    $driver->update([
                        'latitude' => -4.2767,
                        'longitude' => 15.2832,
                        'status' => 'online'
                    ]);
                    $this->info("✅ Coordonnées initialisées pour le livreur ID: {$driverId}");
                } else {
                    $this->info("Utilisation du livreur ID: {$driverId}");
                }
            }
        }

        $driver = Driver::find($driverId);
        if (!$driver) {
            $this->error("Livreur ID {$driverId} non trouvé.");
            return 1;
        }

        $this->line("Livreur: {$driver->name} (ID: {$driver->id})");
        $this->line("Position actuelle: {$driver->latitude}, {$driver->longitude}");
        $this->line('');

        // 2. Simuler une mise à jour de position
        $this->info('📍 Test 1: Mise à jour position livreur');
        $newLat = -4.2800 + (rand(-100, 100) / 10000); // Variation de ~100m
        $newLng = 15.2900 + (rand(-100, 100) / 10000);
        
        $this->line("Nouvelle position: {$newLat}, {$newLng}");
        
        // Simuler l'appel API
        $driver->latitude = $newLat;
        $driver->longitude = $newLng;
        $driver->status = 'online';
        $driver->save();
        
        // Enregistrer dans driver_locations
        try {
                DriverLocation::create([
                'driver_id' => $driver->id,
                'latitude' => $newLat,
                'longitude' => $newLng,
                'accuracy' => 10.5,
                'heading' => rand(0, 360),
                'speed' => rand(20, 60),
            ]);
            $this->info('✅ Position enregistrée dans driver_locations');
        } catch (\Exception $e) {
            $this->warn('⚠️  Erreur enregistrement driver_locations: ' . $e->getMessage());
        }
        
        $this->line('');

        // 3. Vérifier l'historique
        $this->info('📜 Test 2: Vérifier historique positions');
        $locations = DriverLocation::where('driver_id', $driver->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        
        if ($locations->isEmpty()) {
            $this->warn('Aucune position dans l\'historique');
        } else {
            $this->line("Dernières positions ({$locations->count()}):");
            foreach ($locations as $loc) {
                $this->line("  - {$loc->created_at->format('H:i:s')}: {$loc->latitude}, {$loc->longitude} (accuracy: {$loc->accuracy}m, speed: {$loc->speed}km/h)");
            }
        }
        $this->line('');

        // 4. Tester récupération via API (simulation)
        $this->info('🔍 Test 3: Récupération position via tracking');
        
        $orderId = $this->option('order-id');
        if ($orderId) {
            $order = Order::find($orderId);
            if ($order) {
                $delivery = $order->delivery;
                if ($delivery && $delivery->driver_id == $driver->id) {
                    // Récupérer la dernière position
                    $latestLocation = DriverLocation::where('driver_id', $driver->id)
                        ->orderBy('created_at', 'desc')
                        ->first();
                    
                    if ($latestLocation) {
                        $this->info('✅ Position récupérée depuis driver_locations:');
                        $this->line("  Latitude: {$latestLocation->latitude}");
                        $this->line("  Longitude: {$latestLocation->longitude}");
                        $this->line("  Accuracy: {$latestLocation->accuracy}m");
                        $this->line("  Heading: {$latestLocation->heading}°");
                        $this->line("  Speed: {$latestLocation->speed}km/h");
                        $this->line("  Timestamp: {$latestLocation->created_at->format('Y-m-d H:i:s')}");
                    } else {
                        $this->warn('⚠️  Aucune position dans driver_locations, fallback sur drivers.latitude/longitude');
                        $this->line("  Latitude: {$driver->latitude}");
                        $this->line("  Longitude: {$driver->longitude}");
                    }
                } else {
                    $this->warn("⚠️  La commande n'est pas assignée à ce livreur");
                }
            } else {
                $this->warn("⚠️  Commande ID {$orderId} non trouvée");
            }
        } else {
            // Vérifier si la table deliveries existe
            if (Schema::hasTable('deliveries')) {
                // Trouver une commande assignée à ce livreur
                $delivery = Delivery::where('driver_id', $driver->id)
                    ->whereIn('status', ['ASSIGNED', 'PICKED_UP', 'ON_THE_WAY'])
                    ->first();
                
                if ($delivery) {
                    $order = $delivery->order;
                    $this->info("Commande trouvée: {$order->order_no}");
                    
                    $latestLocation = DriverLocation::where('driver_id', $driver->id)
                        ->orderBy('created_at', 'desc')
                        ->first();
                    
                    if ($latestLocation) {
                        $this->info('✅ Position récupérée:');
                        $this->line("  Latitude: {$latestLocation->latitude}");
                        $this->line("  Longitude: {$latestLocation->longitude}");
                        $this->line("  Timestamp: {$latestLocation->created_at->format('Y-m-d H:i:s')}");
                    } else {
                        $this->warn('⚠️  Aucune position dans driver_locations');
                        $this->line("  Fallback: {$driver->latitude}, {$driver->longitude}");
                    }
                } else {
                    $this->warn('⚠️  Aucune livraison active pour ce livreur');
                }
            } else {
                $this->warn('⚠️  Table deliveries n\'existe pas. Test de position directe...');
            }
            
            // Test direct de récupération de position
            $latestLocation = DriverLocation::where('driver_id', $driver->id)
                ->orderBy('created_at', 'desc')
                ->first();
            
            if ($latestLocation) {
                $this->info('✅ Position récupérée depuis driver_locations:');
                $this->line("  Latitude: {$latestLocation->latitude}");
                $this->line("  Longitude: {$latestLocation->longitude}");
                $this->line("  Accuracy: {$latestLocation->accuracy}m");
                $this->line("  Heading: {$latestLocation->heading}°");
                $this->line("  Speed: {$latestLocation->speed}km/h");
                $this->line("  Timestamp: {$latestLocation->created_at->format('Y-m-d H:i:s')}");
            } else {
                $this->warn('⚠️  Aucune position dans driver_locations');
                $this->line("  Position actuelle (drivers table): {$driver->latitude}, {$driver->longitude}");
            }
        }
        $this->line('');

        // 5. Test API endpoint (simulation)
        $this->info('🌐 Test 4: Simulation appel API');
        $this->line('Pour tester l\'API réelle, utiliser :');
        $this->line('');
        $this->line('1. Mettre à jour position :');
        $this->line("   curl -X POST 'https://bantudelice.cg/api/driver/{$driver->id}/location' \\");
        $this->line("     -H 'Content-Type: application/json' \\");
        $this->line("     -d '{\"latitude\": {$newLat}, \"longitude\": {$newLng}, \"accuracy\": 10.5}'");
        $this->line('');
        
        if (isset($order) && $order) {
            $this->line('2. Récupérer position via tracking :');
            $this->line("   curl 'https://bantudelice.cg/api/order/{$order->order_no}/status'");
            $this->line('');
        }

        // 6. Statistiques
        $this->info('📊 Statistiques');
        $totalLocations = DriverLocation::where('driver_id', $driver->id)->count();
        $recentLocations = DriverLocation::where('driver_id', $driver->id)
            ->where('created_at', '>=', now()->subHour())
            ->count();
        
        $this->line("Total positions enregistrées: {$totalLocations}");
        $this->line("Positions dernière heure: {$recentLocations}");
        $this->line('');

        $this->info('✅ Tests de géolocalisation terminés');
        
        return 0;
    }
}

