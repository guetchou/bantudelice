<?php

namespace App\Http\Controllers\restaurant;

use App\Http\Controllers\Controller;
use App\RestaurantSpecialClosure;
use Illuminate\Http\Request;

class RestaurantSpecialClosureController extends Controller
{
    public function create()
    {
        return view('restaurant.working_hour.special_create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'label' => ['required', 'string', 'max:191'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $restaurant = auth()->user()->restaurant;
        if (! $restaurant) {
            return redirect()->route('restaurant.dashboard')->with('alert', [
                'type' => 'danger',
                'message' => "Aucun restaurant n'est associé à votre compte.",
            ]);
        }

        $restaurant->special_closures()->create($request->only([
            'label',
            'starts_on',
            'ends_on',
            'notes',
        ]));

        return redirect()->route('working_hour.index')->with('alert', [
            'type' => 'success',
            'message' => 'Fermeture spéciale ajoutée avec succès',
        ]);
    }

    public function edit(RestaurantSpecialClosure $specialClosure)
    {
        $restaurant = auth()->user()->restaurant;
        if (! $restaurant || (int) $specialClosure->restaurant_id !== (int) $restaurant->id) {
            abort(403, 'Accès non autorisé');
        }

        return view('restaurant.working_hour.special_edit', [
            'specialClosure' => $specialClosure,
        ]);
    }

    public function update(Request $request, RestaurantSpecialClosure $specialClosure)
    {
        $restaurant = auth()->user()->restaurant;
        if (! $restaurant || (int) $specialClosure->restaurant_id !== (int) $restaurant->id) {
            abort(403, 'Accès non autorisé');
        }

        $request->validate([
            'label' => ['required', 'string', 'max:191'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $specialClosure->update($request->only([
            'label',
            'starts_on',
            'ends_on',
            'notes',
        ]));

        return redirect()->route('working_hour.index')->with('alert', [
            'type' => 'success',
            'message' => 'Fermeture spéciale mise à jour avec succès',
        ]);
    }

    public function destroy(RestaurantSpecialClosure $specialClosure)
    {
        $restaurant = auth()->user()->restaurant;
        if (! $restaurant || (int) $specialClosure->restaurant_id !== (int) $restaurant->id) {
            abort(403, 'Accès non autorisé');
        }

        $specialClosure->delete();

        return redirect()->route('working_hour.index')->with('alert', [
            'type' => 'success',
            'message' => 'Fermeture spéciale supprimée avec succès',
        ]);
    }
}
