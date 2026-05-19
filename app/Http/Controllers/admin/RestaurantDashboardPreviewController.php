<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Restaurant;
use App\Services\RestaurantDashboardDataService;

class RestaurantDashboardPreviewController extends Controller
{
    public function show(Restaurant $restaurant)
    {
        $dashboardContext = [
            'mode' => 'admin_preview',
            'read_only' => true,
            'restaurant_name' => $restaurant->name,
            'banner_title' => 'Aperçu du dashboard',
            'banner_message' => 'Consultation du dashboard restaurant sans changer de compte.',
            'back_url' => route('restaurant.index'),
            'back_label' => 'Retour à la liste restaurants',
        ];

        return view('restaurant.home', app(RestaurantDashboardDataService::class)->build($restaurant) + compact('dashboardContext'));
    }
}
