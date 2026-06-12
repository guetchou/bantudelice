<?php

namespace App\Http\Controllers;

use App\Category;
use App\Driver;
use App\Http\Controllers\Concerns\RemembersFrontendBrand;
use App\Services\CatalogSearchService;
use App\Services\ConfigService;
use App\Services\DataSyncService;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    use RemembersFrontendBrand;

    public function home()
    {
        $siteDegraded = false;
        $degradedReason = null;
        $siteContext = app(\App\Services\SiteContextService::class)->currentContext(request());
        $homeWorkspace = $this->resolveHomeWorkspace(request(), $siteContext);
        $homeContent = array_replace_recursive(trans('ui.home'), ConfigService::getHomeContent($homeWorkspace));
        $resolveHomeMedia = static function (?string $path, string $fallback): string {
            if (blank($path)) {
                return $fallback;
            }

            $path = (string) $path;

            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                return $path;
            }

            return asset(ltrim($path, '/'));
        };
        $favoriteRestaurantIds = auth()->check()
            ? auth()->user()->favoriteRestaurants()->pluck('restaurants.id')->all()
            : [];

        try {
            $restaurants = DataSyncService::getActiveRestaurants(8, false);
            $products = DataSyncService::getFeaturedProducts(12);
            $dailySpecials = DataSyncService::getDailySpecialProducts(8);
            $cuisines = DataSyncService::getCuisinesWithRestaurants(12);

            $categories = Category::query()
                ->where('is_available', 1)
                ->whereHas('products')
                ->withCount(['products as products_count' => function ($q) {
                    $q->where('is_available', 1);
                }])
                ->with(['products' => function ($q) {
                    $q->where('is_available', 1)
                      ->whereNotNull('image')
                      ->latest('id');
                }])
                ->orderByDesc('products_count')
                ->limit(8)
                ->get();

            $drivers = Driver::query()
                ->where('approved', 1)
                ->whereNotNull('name')
                ->orderByDesc('id')
                ->limit(6)
                ->get();

            $restaurants = $restaurants->map(function ($restaurant) use ($favoriteRestaurantIds) {
                $restaurant->is_favorite = in_array($restaurant->id, $favoriteRestaurantIds, true);
                return $restaurant;
            });

            $catalogSearch = app(CatalogSearchService::class);
            $restaurants = $catalogSearch->rankRestaurants($restaurants, [
                'favorite_restaurant_ids' => $favoriteRestaurantIds,
                'sort' => 'recommended',
                'limit' => 8,
            ]);
            $products = $catalogSearch->rankProducts($products, [
                'sort' => 'featured',
                'limit' => 12,
            ]);
            $dailySpecials = $catalogSearch->rankProducts($dailySpecials, [
                'sort' => 'featured',
                'limit' => 8,
            ]);
            $recommendationProfile = $catalogSearch->recommendationProfile(auth()->user(), []);
            $recommendations = $catalogSearch->recommendRestaurants(auth()->user(), [
                'profile' => $recommendationProfile,
                'sort' => 'recommended',
                'limit' => 6,
            ], 6);
            $productRecommendations = $catalogSearch->recommendProducts(auth()->user(), [
                'profile' => $recommendationProfile,
                'sort' => 'featured',
                'limit' => 8,
            ], 8);
        } catch (\Throwable $e) {
            \Log::warning('Home page running in degraded mode', [
                'message' => $e->getMessage(),
            ]);

            $siteDegraded = true;
            $degradedReason = 'Base de donnees temporairement indisponible';
            $restaurants = collect();
            $products = collect();
            $dailySpecials = collect();
            $cuisines = collect();
            $categories = collect();
            $drivers = collect();
            $recommendations = collect();
            $productRecommendations = collect();
        }

        // Carte principale food — seule mise en avant centrale
        $serviceCards = collect([
            [
                'title' => trans('ui.home.service_cards.restaurants.title'),
                'description' => trans('ui.home.service_cards.restaurants.description'),
                'image' => $resolveHomeMedia($homeContent['service_food_image'] ?? null, asset('images/home/service-restaurant.jpg')),
                'url' => route('restaurants.all'),
                'cta' => trans('ui.home.service_cards.restaurants.cta'),
            ],
        ]);

        // Plateformes sœurs — bloc secondaire écosystème (non affichées au même niveau que food)
        $ecosystemCards = collect([
            [
                'title' => trans('ui.home.service_cards.parcels.title'),
                'description' => trans('ui.home.service_cards.parcels.description'),
                'image' => $resolveHomeMedia($homeContent['service_colis_image'] ?? null, asset('images/home/service-colis.jpg')),
                'url' => route('colis.landing'),
                'cta' => trans('ui.home.service_cards.parcels.cta'),
            ],
            [
                'title' => trans('ui.home.service_cards.transport.title'),
                'description' => trans('ui.home.service_cards.transport.description'),
                'image' => $resolveHomeMedia($homeContent['service_transport_image'] ?? null, asset('images/home/service-transport.jpg')),
                'url' => url('/transport'),
                'cta' => trans('ui.home.service_cards.transport.cta'),
            ],
        ]);

        return response()
            ->view('frontend.index-modern', compact(
                'siteContext',
                'restaurants',
                'products',
                'dailySpecials',
                'cuisines',
                'categories',
                'drivers',
                'serviceCards',
                'ecosystemCards',
                'homeContent',
                'recommendations',
                'productRecommendations',
                'siteDegraded',
                'degradedReason'
            ))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    protected function resolveHomeWorkspace(Request $request, array $siteContext = []): string
    {
        $brand = strtolower(trim((string) $request->query('brand', '')));
        if (in_array($brand, ['bantudelice', 'kende', 'mema'], true)) {
            return $brand;
        }

        $sessionBrand = strtolower(trim((string) $request->session()->get('frontend_brand', '')));
        if (in_array($sessionBrand, ['bantudelice', 'kende', 'mema'], true)) {
            return $sessionBrand;
        }

        $siteKey = strtolower(trim((string) ($siteContext['site_key'] ?? '')));
        return match ($siteKey) {
            'kende' => 'kende',
            'mema' => 'mema',
            default => 'bantudelice',
        };
    }

    /**
     * API : retourne le statut en temps réel d'une commande (pour polling page thanks)
     * GET /api/order/{orderNo}/status
     *
     * Sécurité : deux chemins acceptés, aucun fallback ouvert :
     *  1. Utilisateur authentifié → doit être propriétaire de la commande.
     *  2. Invité → order_no doit correspondre à celui stocké en session lors du checkout.
     */
    public function getOrderStatus(Request $request, string $orderNo)
    {
        $order = \App\Order::select(['id','order_no','user_id','status','business_status','technical_status','payment_status'])
            ->where('order_no', $orderNo)
            ->first();

        if (! $order) {
            return response()->json(['error' => 'not_found'], 404);
        }

        if (auth()->check()) {
            // Utilisateur connecté — doit posséder la commande
            if ((int) $order->user_id !== (int) auth()->id()) {
                return response()->json(['error' => 'forbidden'], 403);
            }
        } else {
            // Invité — vérifie que la session contient cet order_no
            // (mis en session par CustomerOrderController avant redirect vers /thanks)
            $sessionOrderNo = $request->session()->get('order_no');
            if (empty($sessionOrderNo) || $sessionOrderNo !== $orderNo) {
                return response()->json(['error' => 'forbidden'], 403);
            }
        }

        return response()->json([
            'order_no'         => $order->order_no,
            'business_status'  => $order->business_status,
            'technical_status' => $order->technical_status,
            'payment_status'   => $order->payment_status,
            'status'           => $order->status,
        ]);
    }
}
