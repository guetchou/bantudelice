<?php

namespace App\Http\Controllers;

use App\Restaurant;
use Illuminate\Http\Request;

class RestaurantFavoriteController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $restaurants = $user
            ? $user->favoriteRestaurants()
                ->with(['cuisines', 'ratings'])
                ->orderByDesc('restaurant_favorites.created_at')
                ->get()
            : collect();

        return view('frontend.favorite_restaurants', compact('restaurants'));
    }

    public function toggle(Request $request, Restaurant $restaurant)
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('user.login')->with('message', 'Veuillez vous connecter pour ajouter un restaurant aux favoris.');
        }

        $favorite = $user->favoriteRestaurants()->where('restaurants.id', $restaurant->id)->first();

        if ($favorite) {
            $user->favoriteRestaurants()->detach($restaurant->id);
            $message = 'Restaurant retiré des favoris.';
        } else {
            $user->favoriteRestaurants()->syncWithoutDetaching([$restaurant->id]);
            $message = 'Restaurant ajouté aux favoris.';
        }

        if ($request->expectsJson()) {
            return response()->json([
                'status' => true,
                'is_favorite' => !$favorite,
                'message' => $message,
            ]);
        }

        return back()->with('message', $message);
    }
}
