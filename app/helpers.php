<?php

/**
 * Helper pour obtenir la clé API Google Maps
 */
if (!function_exists('google_maps_api_key')) {
    function google_maps_api_key(): ?string
    {
        return config('external-services.geolocation.google_maps.api_key')
            ?? config('services.google.maps_key');
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
        
        $params = ['key' => $key, 'loading' => 'async'];
        
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


/**
 * Helper pour obtenir le token public Mapbox
 */
if (!function_exists('mapbox_public_token')) {
    function mapbox_public_token(): ?string
    {
        return config('services.mapbox.public_token') ?: null;
    }
}

/**
 * Helper URL des tuiles Mapbox (style Streets)
 */
if (!function_exists('mapbox_tile_url')) {
    function mapbox_tile_url(): string
    {
        return 'https://api.mapbox.com/styles/v1/mapbox/streets-v12/tiles/{z}/{x}/{y}?access_token=' . mapbox_public_token();
    }
}

if (!function_exists('enqueue_job')) {
    function enqueue_job(string $module, string $jobType, array $payload = [])
    {
        return app(\App\Services\ModuleQueueService::class)->enqueueJob($module, $jobType, $payload);
    }
}
