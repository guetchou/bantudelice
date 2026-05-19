<?php

if (!function_exists('google_maps_api_key')) {
    /**
     * Obtenir la clé API Google Maps depuis la configuration
     */
    function google_maps_api_key(): ?string
    {
        return config('external-services.geolocation.google_maps.api_key') 
            ?? env('GOOGLE_MAPS_API_KEY') 
            ?? null;
    }
}

if (!function_exists('google_maps_js_url')) {
    /**
     * Générer l'URL du script Google Maps JavaScript API
     * 
     * @param array $libraries Libraries à charger (places, directions, etc.)
     * @param string|null $callback Fonction de callback
     * @return string
     */
    function google_maps_js_url(array $libraries = [], ?string $callback = null): string
    {
        $apiKey = google_maps_api_key();
        
        if (!$apiKey) {
            return '';
        }
        
        $params = ['key' => $apiKey];
        
        if (!empty($libraries)) {
            $params['libraries'] = implode(',', $libraries);
        }
        
        if ($callback) {
            $params['callback'] = $callback;
        }
        
        return 'https://maps.googleapis.com/maps/api/js?' . http_build_query($params);
    }
}

if (!function_exists('get_default_location')) {
    /**
     * Obtenir les coordonnées par défaut (Brazzaville, Congo)
     */
    function get_default_location(): array
    {
        return [
            'lat' => -4.2767,
            'lng' => 15.2832,
            'address' => 'Brazzaville, République du Congo',
        ];
    }
}

if (!function_exists('is_google_maps_enabled')) {
    /**
     * Vérifier si Google Maps est activé
     */
    function is_google_maps_enabled(): bool
    {
        return config('external-services.geolocation.google_maps.enabled', true) 
            && !empty(google_maps_api_key());
    }
}

