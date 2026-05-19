<?php

namespace App\Http\Controllers\restaurant;

use App\Category;
use App\Http\Controllers\Controller;
use App\Product;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function index()
    {
        $restaurant = auth()->user()->restaurant;
        if (!$restaurant) {
            return redirect()->route('restaurant.dashboard')->with('alert', [
                'type' => 'danger',
                'message' => "Aucun restaurant n'est associé à votre compte."
            ]);
        }

        $categories = $restaurant->categories()
            ->with(['products' => function ($q) use ($restaurant) {
                $q->where('restaurant_id', $restaurant->id)
                    ->orderBy('sort_order')
                    ->orderBy('featured', 'desc')
                    ->orderBy('name');
            }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('restaurant.menu.index', compact('categories', 'restaurant'));
    }

    public function toggleCategoryAvailability(Request $request, Category $category)
    {
        $restaurant = auth()->user()->restaurant;
        if (!$restaurant || (int)$category->restaurant_id !== (int)$restaurant->id) {
            abort(403, 'Accès non autorisé');
        }

        $category->is_available = !$category->is_available;
        $category->save();

        return response()->json([
            'message' => 'Disponibilité mise à jour',
            'is_available' => (bool)$category->is_available,
        ]);
    }

    public function toggleProductAvailability(Request $request, Product $product)
    {
        $restaurant = auth()->user()->restaurant;
        if (!$restaurant || (int)$product->restaurant_id !== (int)$restaurant->id) {
            abort(403, 'Accès non autorisé');
        }

        $product->is_available = !$product->is_available;
        $product->save();

        return response()->json([
            'message' => 'Disponibilité mise à jour',
            'is_available' => (bool)$product->is_available,
        ]);
    }

    public function reorderCategories(Request $request)
    {
        $restaurant = auth()->user()->restaurant;
        if (!$restaurant) {
            return response()->json(['message' => "Aucun restaurant n'est associé à votre compte."], 422);
        }

        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $ids = $request->ids;
        $count = Category::where('restaurant_id', $restaurant->id)->whereIn('id', $ids)->count();
        if ($count !== count($ids)) {
            return response()->json(['message' => 'Liste invalide (catégories hors restaurant).'], 422);
        }

        foreach ($ids as $idx => $id) {
            Category::where('id', $id)->where('restaurant_id', $restaurant->id)->update(['sort_order' => $idx + 1]);
        }

        return response()->json(['message' => 'Ordre des catégories mis à jour']);
    }

    public function reorderProducts(Request $request, Category $category)
    {
        $restaurant = auth()->user()->restaurant;
        if (!$restaurant || (int)$category->restaurant_id !== (int)$restaurant->id) {
            abort(403, 'Accès non autorisé');
        }

        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $ids = $request->ids;
        $count = Product::where('restaurant_id', $restaurant->id)
            ->where('category_id', $category->id)
            ->whereIn('id', $ids)
            ->count();

        if ($count !== count($ids)) {
            return response()->json(['message' => 'Liste invalide (produits hors catégorie).'], 422);
        }

        foreach ($ids as $idx => $id) {
            Product::where('id', $id)
                ->where('restaurant_id', $restaurant->id)
                ->where('category_id', $category->id)
                ->update(['sort_order' => $idx + 1]);
        }

        return response()->json(['message' => 'Ordre des produits mis à jour']);
    }
}


