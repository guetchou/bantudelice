<?php

/**
 * Helper pour obtenir la clé API Google Maps
 */
if (!function_exists('google_maps_api_key')) {
    function google_maps_api_key(): ?string
    {
        return config('external-services.geolocation.google_maps.api_key') 
            ?? env('GOOGLE_MAPS_API_KEY') 
            ?? 'AIzaSyCkXFIvxvN0M1Chg644bLwAnXEQUG_RKUI'; // Fallback temporaire
    }
}

/**
 * Helper pour générer l'URL Google Maps JS
 */
if (!function_exists('google_maps_js_url')) {
    function google_maps_js_url($libraries = [], $callback = null): string
    {
        $key = google_maps_api_key();
        if (!$key) {
            return '';
        }
        
        $params = ['key' => $key];
        
        if (!empty($libraries)) {
            if (is_array($libraries)) {
                $params['libraries'] = implode(',', $libraries);
            } else {
                $params['libraries'] = $libraries;
            }
        }
        
        if ($callback) {
            $params['callback'] = $callback;
        }
        
        return 'https://maps.googleapis.com/maps/api/js?' . http_build_query($params);
    }
}
