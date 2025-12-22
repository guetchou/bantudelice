<?php

namespace App\Http\Controllers\restaurant;

use App\Http\Controllers\Controller;
use App\RestaurantMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class RestaurantMediaController extends Controller
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

        $media = $restaurant->media()->get();

        return view('restaurant.media.index', compact('media', 'restaurant'));
    }

    public function store(Request $request)
    {
        $restaurant = auth()->user()->restaurant;
        if (!$restaurant) {
            return response()->json(['message' => "Aucun restaurant n'est associé à votre compte."], 422);
        }

        $request->validate([
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'external_url' => ['nullable', 'url', 'max:2048'],
            'alt_text' => ['nullable', 'string', 'max:191'],
        ]);

        $nextSort = (int)($restaurant->media()->max('sort_order') ?? 0);

        $created = [];

        // 1) URL externe
        if ($request->filled('external_url')) {
            $nextSort++;
            $m = RestaurantMedia::create([
                'restaurant_id' => $restaurant->id,
                'source' => 'external',
                'external_url' => $request->external_url,
                'alt_text' => $request->alt_text,
                'sort_order' => $nextSort,
            ]);
            $created[] = $m;
        }

        // 2) Upload multi
        if ($request->hasFile('images')) {
            $destination = public_path('images/restaurant_gallery');
            if (!is_dir($destination)) {
                @mkdir($destination, 0775, true);
            }

            foreach ($request->file('images') as $file) {
                $nextSort++;
                $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                    . '-' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move($destination, $filename);

                $m = RestaurantMedia::create([
                    'restaurant_id' => $restaurant->id,
                    'source' => 'upload',
                    'file_name' => $filename,
                    'alt_text' => $request->alt_text,
                    'sort_order' => $nextSort,
                ]);
                $created[] = $m;
            }
        }

        if (empty($created)) {
            return response()->json(['message' => 'Veuillez fournir au moins une image ou une URL.'], 422);
        }

        return response()->json([
            'message' => 'Média ajouté avec succès',
            'data' => collect($created)->map(function (RestaurantMedia $m) {
                return [
                    'id' => $m->id,
                    'source' => $m->source,
                    'file_name' => $m->file_name,
                    'external_url' => $m->external_url,
                    'alt_text' => $m->alt_text,
                    'sort_order' => $m->sort_order,
                    'public_url' => $m->public_url,
                ];
            }),
        ]);
    }

    public function destroy(RestaurantMedia $media)
    {
        $restaurant = auth()->user()->restaurant;
        if (!$restaurant || (int)$media->restaurant_id !== (int)$restaurant->id) {
            abort(403, 'Accès non autorisé');
        }

        if ($media->source === 'upload' && $media->file_name) {
            $path = public_path('images/restaurant_gallery/' . $media->file_name);
            if (File::exists($path)) {
                @unlink($path);
            }
        }

        $media->delete();

        return response()->json(['message' => 'Média supprimé avec succès']);
    }

    public function reorder(Request $request)
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

        // Sécurité: tous les ids doivent appartenir au restaurant
        $count = RestaurantMedia::where('restaurant_id', $restaurant->id)
            ->whereIn('id', $ids)
            ->count();

        if ($count !== count($ids)) {
            return response()->json(['message' => 'Liste invalide (médias hors restaurant).'], 422);
        }

        foreach ($ids as $idx => $id) {
            RestaurantMedia::where('id', $id)
                ->where('restaurant_id', $restaurant->id)
                ->update(['sort_order' => $idx + 1]);
        }

        return response()->json(['message' => 'Ordre mis à jour']);
    }
}


