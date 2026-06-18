<?php

namespace App\Http\Controllers\restaurant;

use App\Rating;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RatingsController extends Controller
{
    public function index(Request $request)
    {
        $restaurant = auth()->user()->restaurant;
        if (!$restaurant) {
            return redirect()->route('restaurant.dashboard');
        }

        $restaurantId = $restaurant->id;
        $filter       = $request->get('filter', 'all');

        $query = Rating::where('restaurant_id', $restaurantId)->with('user', 'order');

        if ($filter === 'good')   { $query->where('rating', '>=', 4); }
        elseif ($filter === 'ok') { $query->where('rating', 3); }
        elseif ($filter === 'bad'){ $query->where('rating', '<=', 2); }

        $ratings = $query->orderByDesc('created_at')->paginate(15)->appends(['filter' => $filter]);

        $distribution = Rating::where('restaurant_id', $restaurantId)
            ->selectRaw('rating, COUNT(*) as cnt')
            ->groupBy('rating')
            ->orderByDesc('rating')
            ->get()
            ->keyBy('rating');

        // Toujours calculer depuis la table ratings (les colonnes dénormalisées peuvent être obsolètes)
        $stats      = Rating::where('restaurant_id', $restaurantId)
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as rating_count')
            ->first();
        $totalCount = (int) ($stats->rating_count ?? 0);
        $avgRating  = round((float) ($stats->avg_rating ?? 0), 1);

        return view('restaurant.ratings.index', compact(
            'restaurant', 'ratings', 'distribution', 'totalCount', 'avgRating', 'filter'
        ));
    }
}
