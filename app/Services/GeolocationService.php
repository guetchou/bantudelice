<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\ConfigService;

/**
 * Service de géolocalisation unifié
 * Supporte: Google Maps API, OpenStreetMap/Nominatim (gratuit)
 */
class GeolocationService
{
    /**
     * Coordonnées par défaut (Centre de Brazzaville, Congo)
     */
    const DEFAULT_LAT = -4.2767;
    const DEFAULT_LNG = 15.2832;
    
    /**
     * Géocoder une adresse (obtenir les coordonnées)
     * 
     * @param string $address
     * @return array ['lat' => float, 'lng' => float, 'formatted_address' => string]
     */
    public static function geocode(string $address): array
    {
        $config = config('external-services.geolocation');
        
        // Ajouter le contexte Congo si pas déjà présent
        if (!str_contains(strtolower($address), 'congo') && !str_contains(strtolower($address), 'brazzaville')) {
            $address .= ', Brazzaville, Congo';
        }
        
        // Essayer Google Maps d'abord
        if ($config['google_maps']['enabled'] && !empty($config['google_maps']['api_key'])) {
            return self::geocodeWithGoogle($address, $config['google_maps']['api_key']);
        }
        
        // Fallback vers OpenStreetMap (gratuit)
        if ($config['openstreetmap']['enabled'] ?? true) {
            return self::geocodeWithNominatim($address);
        }
        
        return [
            'lat' => self::DEFAULT_LAT,
            'lng' => self::DEFAULT_LNG,
            'formatted_address' => $address,
            'error' => 'Aucun service de géolocalisation configuré',
        ];
    }
    
    /**
     * Géocoder inverse (obtenir l'adresse à partir des coordonnées)
     * 
     * @param float $lat
     * @param float $lng
     * @return array
     */
    public static function reverseGeocode(float $lat, float $lng): array
    {
        $config = config('external-services.geolocation');
        
        if ($config['google_maps']['enabled'] && !empty($config['google_maps']['api_key'])) {
            return self::reverseGeocodeWithGoogle($lat, $lng, $config['google_maps']['api_key']);
        }
        
        if ($config['openstreetmap']['enabled'] ?? true) {
            return self::reverseGeocodeWithNominatim($lat, $lng);
        }
        
        return [
            'address' => 'Adresse inconnue',
            'lat' => $lat,
            'lng' => $lng,
        ];
    }
    
    /**
     * Calculer la distance entre deux points
     * 
     * @param float $lat1 Latitude point 1
     * @param float $lng1 Longitude point 1
     * @param float $lat2 Latitude point 2
     * @param float $lng2 Longitude point 2
     * @return float Distance en kilomètres
     */
    public static function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        // Formule de Haversine
        $earthRadius = 6371; // km
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) * sin($dLng / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return round($earthRadius * $c, 2);
    }
    
    /**
     * Calculer le temps de trajet estimé
     * 
     * @param float $lat1
     * @param float $lng1
     * @param float $lat2
     * @param float $lng2
     * @param string $mode driving|walking|bicycling
     * @return array ['distance' => float, 'duration' => int, 'duration_text' => string]
     */
    public static function calculateRoute(float $lat1, float $lng1, float $lat2, float $lng2, string $mode = 'driving'): array
    {
        $config = config('external-services.geolocation');
        
        // Essayer Google Directions API
        if ($config['google_maps']['enabled'] && !empty($config['google_maps']['directions_api_key'] ?? $config['google_maps']['api_key'])) {
            return self::getGoogleDirections($lat1, $lng1, $lat2, $lng2, $mode, $config['google_maps']['directions_api_key'] ?? $config['google_maps']['api_key']);
        }
        
        // Fallback vers OSRM (gratuit)
        if ($config['openstreetmap']['enabled'] ?? true) {
            return self::getOsrmRoute($lat1, $lng1, $lat2, $lng2, $mode, $config['openstreetmap']['osrm_url']);
        }
        
        // Estimation simple basée sur la distance
        $distance = self::calculateDistance($lat1, $lng1, $lat2, $lng2);
        $avgSpeed = $mode === 'walking' ? 5 : ($mode === 'bicycling' ? 15 : 30); // km/h
        $duration = ($distance / $avgSpeed) * 60; // minutes
        
        return [
            'distance' => $distance,
            'distance_text' => number_format($distance, 1) . ' km',
            'duration' => (int)$duration,
            'duration_text' => self::formatDuration((int)$duration),
            'estimated' => true,
        ];
    }
    
    /**
     * Obtenir la matrice de distances pour plusieurs points
     * 
     * @param array $origins [['lat' => float, 'lng' => float], ...]
     * @param array $destinations [['lat' => float, 'lng' => float], ...]
     * @return array
     */
    public static function getDistanceMatrix(array $origins, array $destinations): array
    {
        $config = config('external-services.geolocation');
        
        if ($config['google_maps']['enabled'] && !empty($config['google_maps']['distance_matrix_api_key'] ?? $config['google_maps']['api_key'])) {
            return self::getGoogleDistanceMatrix($origins, $destinations, $config['google_maps']['distance_matrix_api_key'] ?? $config['google_maps']['api_key']);
        }
        
        // Fallback: calculer chaque paire individuellement
        $matrix = [];
        foreach ($origins as $i => $origin) {
            $matrix[$i] = [];
            foreach ($destinations as $j => $dest) {
                $distance = self::calculateDistance(
                    $origin['lat'], $origin['lng'],
                    $dest['lat'], $dest['lng']
                );
                $duration = ($distance / 30) * 60; // Estimation 30 km/h
                
                $matrix[$i][$j] = [
                    'distance' => $distance,
                    'distance_text' => number_format($distance, 1) . ' km',
                    'duration' => (int)$duration,
                    'duration_text' => self::formatDuration((int)$duration),
                ];
            }
        }
        
        return ['matrix' => $matrix, 'estimated' => true];
    }
    
    /**
     * Trouver le livreur le plus proche
     * 
     * @param float $restaurantLat
     * @param float $restaurantLng
     * @param array $drivers [['id' => int, 'lat' => float, 'lng' => float], ...]
     * @return array|null Livreur le plus proche avec distance
     */
    public static function findNearestDriver(float $restaurantLat, float $restaurantLng, array $drivers): ?array
    {
        if (empty($drivers)) {
            return null;
        }
        
        $nearest = null;
        $minDistance = PHP_FLOAT_MAX;
        
        foreach ($drivers as $driver) {
            if (!isset($driver['lat'], $driver['lng'])) {
                continue;
            }
            
            $distance = self::calculateDistance(
                $restaurantLat, $restaurantLng,
                $driver['lat'], $driver['lng']
            );
            
            if ($distance < $minDistance) {
                $minDistance = $distance;
                $nearest = array_merge($driver, [
                    'distance' => $distance,
                    'distance_text' => number_format($distance, 1) . ' km',
                ]);
            }
        }
        
        return $nearest;
    }
    
    /**
     * Vérifier si un point est dans une zone de livraison
     * 
     * @param float $lat
     * @param float $lng
     * @param float $centerLat Centre de la zone
     * @param float $centerLng
     * @param float $radiusKm Rayon en km
     * @return bool
     */
    public static function isInDeliveryZone(float $lat, float $lng, float $centerLat, float $centerLng, float $radiusKm): bool
    {
        $distance = self::calculateDistance($centerLat, $centerLng, $lat, $lng);
        return $distance <= $radiusKm;
    }
    
    // ================== GOOGLE MAPS IMPLEMENTATIONS ==================
    
    protected static function geocodeWithGoogle(string $address, string $apiKey): array
    {
        $cacheKey = 'geocode_' . md5($address);
        
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        
        try {
            $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
                'address' => $address,
                'key' => $apiKey,
                'region' => 'cg',
                'language' => 'fr',
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['status'] === 'OK' && !empty($data['results'])) {
                    $result = $data['results'][0];
                    $location = $result['geometry']['location'];
                    
                    $geocoded = [
                        'lat' => $location['lat'],
                        'lng' => $location['lng'],
                        'formatted_address' => $result['formatted_address'],
                        'place_id' => $result['place_id'] ?? null,
                        'provider' => 'google',
                    ];
                    
                    Cache::put($cacheKey, $geocoded, now()->addDays(30));
                    return $geocoded;
                }
            }
            
            Log::warning('Google geocoding failed', ['address' => $address, 'response' => $response->json()]);
            
        } catch (\Exception $e) {
            Log::error('Google geocoding exception', ['error' => $e->getMessage()]);
        }
        
        return [
            'lat' => self::DEFAULT_LAT,
            'lng' => self::DEFAULT_LNG,
            'formatted_address' => $address,
            'error' => 'Géocodage échoué',
        ];
    }
    
    protected static function reverseGeocodeWithGoogle(float $lat, float $lng, string $apiKey): array
    {
        $cacheKey = 'reverse_geocode_' . md5("{$lat},{$lng}");
        
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        
        try {
            $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
                'latlng' => "{$lat},{$lng}",
                'key' => $apiKey,
                'language' => 'fr',
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['status'] === 'OK' && !empty($data['results'])) {
                    $result = $data['results'][0];
                    
                    $geocoded = [
                        'address' => $result['formatted_address'],
                        'lat' => $lat,
                        'lng' => $lng,
                        'place_id' => $result['place_id'] ?? null,
                        'provider' => 'google',
                    ];
                    
                    Cache::put($cacheKey, $geocoded, now()->addDays(30));
                    return $geocoded;
                }
            }
            
        } catch (\Exception $e) {
            Log::error('Google reverse geocoding exception', ['error' => $e->getMessage()]);
        }
        
        return [
            'address' => 'Adresse inconnue',
            'lat' => $lat,
            'lng' => $lng,
        ];
    }
    
    protected static function getGoogleDirections(float $lat1, float $lng1, float $lat2, float $lng2, string $mode, string $apiKey): array
    {
        try {
            $response = Http::get('https://maps.googleapis.com/maps/api/directions/json', [
                'origin' => "{$lat1},{$lng1}",
                'destination' => "{$lat2},{$lng2}",
                'mode' => $mode,
                'key' => $apiKey,
                'language' => 'fr',
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['status'] === 'OK' && !empty($data['routes'])) {
                    $route = $data['routes'][0];
                    $leg = $route['legs'][0];
                    
                    return [
                        'distance' => $leg['distance']['value'] / 1000, // km
                        'distance_text' => $leg['distance']['text'],
                        'duration' => (int)($leg['duration']['value'] / 60), // minutes
                        'duration_text' => $leg['duration']['text'],
                        'polyline' => $route['overview_polyline']['points'] ?? null,
                        'provider' => 'google',
                    ];
                }
            }
            
        } catch (\Exception $e) {
            Log::error('Google directions exception', ['error' => $e->getMessage()]);
        }
        
        // Fallback
        $distance = self::calculateDistance($lat1, $lng1, $lat2, $lng2);
        return [
            'distance' => $distance,
            'distance_text' => number_format($distance, 1) . ' km',
            'duration' => (int)(($distance / 30) * 60),
            'duration_text' => self::formatDuration((int)(($distance / 30) * 60)),
            'estimated' => true,
        ];
    }
    
    protected static function getGoogleDistanceMatrix(array $origins, array $destinations, string $apiKey): array
    {
        $originsStr = implode('|', array_map(fn($o) => "{$o['lat']},{$o['lng']}", $origins));
        $destinationsStr = implode('|', array_map(fn($d) => "{$d['lat']},{$d['lng']}", $destinations));
        
        try {
            $response = Http::get('https://maps.googleapis.com/maps/api/distancematrix/json', [
                'origins' => $originsStr,
                'destinations' => $destinationsStr,
                'key' => $apiKey,
                'mode' => 'driving',
                'language' => 'fr',
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['status'] === 'OK') {
                    $matrix = [];
                    foreach ($data['rows'] as $i => $row) {
                        $matrix[$i] = [];
                        foreach ($row['elements'] as $j => $element) {
                            if ($element['status'] === 'OK') {
                                $matrix[$i][$j] = [
                                    'distance' => $element['distance']['value'] / 1000,
                                    'distance_text' => $element['distance']['text'],
                                    'duration' => (int)($element['duration']['value'] / 60),
                                    'duration_text' => $element['duration']['text'],
                                ];
                            }
                        }
                    }
                    
                    return ['matrix' => $matrix, 'provider' => 'google'];
                }
            }
            
        } catch (\Exception $e) {
            Log::error('Google distance matrix exception', ['error' => $e->getMessage()]);
        }
        
        return self::getDistanceMatrix($origins, $destinations); // Fallback
    }
    
    // ================== OPENSTREETMAP IMPLEMENTATIONS ==================
    
    protected static function geocodeWithNominatim(string $address): array
    {
        $cacheKey = 'geocode_osm_' . md5($address);
        
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        
        try {
            $response = Http::withHeaders([
                'User-Agent' => ConfigService::getUserAgent(),
            ])
            ->get('https://nominatim.openstreetmap.org/search', [
                'q' => $address,
                'format' => 'json',
                'limit' => 1,
                'countrycodes' => 'cg',
                'addressdetails' => 1,
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                if (!empty($data)) {
                    $result = $data[0];
                    
                    $geocoded = [
                        'lat' => (float)$result['lat'],
                        'lng' => (float)$result['lon'],
                        'formatted_address' => $result['display_name'],
                        'provider' => 'openstreetmap',
                    ];
                    
                    Cache::put($cacheKey, $geocoded, now()->addDays(30));
                    return $geocoded;
                }
            }
            
        } catch (\Exception $e) {
            Log::error('Nominatim geocoding exception', ['error' => $e->getMessage()]);
        }
        
        return [
            'lat' => self::DEFAULT_LAT,
            'lng' => self::DEFAULT_LNG,
            'formatted_address' => $address,
            'error' => 'Géocodage échoué',
        ];
    }
    
    protected static function reverseGeocodeWithNominatim(float $lat, float $lng): array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => ConfigService::getUserAgent(),
            ])
            ->get('https://nominatim.openstreetmap.org/reverse', [
                'lat' => $lat,
                'lon' => $lng,
                'format' => 'json',
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'address' => $data['display_name'] ?? 'Adresse inconnue',
                    'lat' => $lat,
                    'lng' => $lng,
                    'provider' => 'openstreetmap',
                ];
            }
            
        } catch (\Exception $e) {
            Log::error('Nominatim reverse geocoding exception', ['error' => $e->getMessage()]);
        }
        
        return [
            'address' => 'Adresse inconnue',
            'lat' => $lat,
            'lng' => $lng,
        ];
    }
    
    protected static function getOsrmRoute(float $lat1, float $lng1, float $lat2, float $lng2, string $mode, string $baseUrl): array
    {
        $profile = $mode === 'walking' ? 'foot' : ($mode === 'bicycling' ? 'bike' : 'car');
        
        try {
            $response = Http::get("{$baseUrl}/route/v1/{$profile}/{$lng1},{$lat1};{$lng2},{$lat2}", [
                'overview' => 'simplified',
                'geometries' => 'polyline',
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['code'] === 'Ok' && !empty($data['routes'])) {
                    $route = $data['routes'][0];
                    
                    return [
                        'distance' => $route['distance'] / 1000, // km
                        'distance_text' => number_format($route['distance'] / 1000, 1) . ' km',
                        'duration' => (int)($route['duration'] / 60), // minutes
                        'duration_text' => self::formatDuration((int)($route['duration'] / 60)),
                        'polyline' => $route['geometry'] ?? null,
                        'provider' => 'osrm',
                    ];
                }
            }
            
        } catch (\Exception $e) {
            Log::error('OSRM route exception', ['error' => $e->getMessage()]);
        }
        
        // Fallback
        $distance = self::calculateDistance($lat1, $lng1, $lat2, $lng2);
        return [
            'distance' => $distance,
            'distance_text' => number_format($distance, 1) . ' km',
            'duration' => (int)(($distance / 30) * 60),
            'duration_text' => self::formatDuration((int)(($distance / 30) * 60)),
            'estimated' => true,
        ];
    }
    
    /**
     * Formater une durée en minutes en texte lisible
     */
    protected static function formatDuration(int $minutes): string
    {
        if ($minutes < 60) {
            return $minutes . ' min';
        }
        
        $hours = (int)($minutes / 60);
        $mins = $minutes % 60;
        
        if ($mins === 0) {
            return $hours . ' h';
        }
        
        return $hours . ' h ' . $mins . ' min';
    }
}

