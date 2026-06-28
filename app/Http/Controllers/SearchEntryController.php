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

            if ($request->filled('city')) {
                $parameters['city'] = $request->query('city');
            }

            if ($request->filled('cuisine')) {
                $parameters['cuisine_id'] = $request->query('cuisine');
            }

            return redirect()->route('search', $parameters);
        }

        return app(PublicCatalogController::class)->allRestaurants($request);
    }
}
