<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SearchEntryController extends Controller
{
    public function restaurants(Request $request): mixed
    {
        if ($request->ajax() || $request->wantsJson()) {
            return app(PublicCatalogController::class)->allRestaurants($request);
        }

        $search = trim((string) $request->query('search', ''));
        $latitude = $request->query('lat');
        $longitude = $request->query('lng');
        $hasCoordinates = is_numeric($latitude)
            && is_numeric($longitude)
            && (float) $latitude >= -90
            && (float) $latitude <= 90
            && (float) $longitude >= -180
            && (float) $longitude <= 180;

        if ($search !== '' || $hasCoordinates) {
            $parameters = [];

            if ($hasCoordinates) {
                $parameters['latitude'] = $latitude;
                $parameters['longitude'] = $longitude;
                if ($search !== '') {
                    $parameters['location_label'] = $search;
                }
            } elseif ($search !== '') {
                $parameters['query'] = $search;
            }

            $legacyFilters = [
                'city' => 'city',
                'cuisine' => 'cuisine_id',
                'min_rating' => 'min_rating',
                'max_delivery_fee' => 'max_delivery_fee',
                'featured' => 'featured',
            ];

            foreach ($legacyFilters as $legacyKey => $canonicalKey) {
                if ($request->filled($legacyKey)) {
                    $parameters[$canonicalKey] = $request->query($legacyKey);
                }
            }

            if ($request->filled('sort')) {
                $sort = strtolower(trim((string) $request->query('sort')));
                $parameters['sort'] = $sort === 'popular' ? 'recommended' : $sort;
            }

            return redirect()->route('search', $parameters);
        }

        return app(PublicCatalogController::class)->allRestaurants($request);
    }
}
