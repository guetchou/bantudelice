<?php

namespace App\Http\Controllers\restaurant;

use App\Http\Controllers\Controller;
use App\Restaurant;
use App\WorkingHour;
use Illuminate\Http\Request;

class WorkingHourController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $restaurant = auth()->user()->restaurant;
        if (!$restaurant) {
            return redirect()->route('restaurant.dashboard')->with('alert', [
                'type' => 'danger',
                'message' => "Aucun restaurant n'est associé à votre compte."
            ]);
        }
        $working_hours = $restaurant->working_hours()->orderBy('Day')->get();
        return view('restaurant.working_hour.index')->with('working_hours', $working_hours);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create()
    {
        return view('restaurant.working_hour.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'day' => 'required|string|max:191',
            'opening_time'=>'required',
            'closing_time'=>'required'
        ]);
        $restaurant = auth()->user()->restaurant;
        if (!$restaurant) {
            return redirect()->route('restaurant.dashboard')->with('alert', [
                'type' => 'danger',
                'message' => "Aucun restaurant n'est associé à votre compte."
            ]);
        }

        // La colonne en base s'appelle "Day" (majuscule)
        $restaurant->working_hours()->create([
            'Day' => $request->day,
            'opening_time' => $request->opening_time,
            'closing_time' => $request->closing_time,
        ]);
//        Category::create($request->all());
        $alert['type'] = 'success';
        $alert['message'] = 'Horaires de travail ajoutés avec succès';
        return redirect()->route('working_hour.index')->with('alert', $alert);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(WorkingHour $workingHour)
    {
        return view('restaurant.working_hour.edit')->with('workingHour', $workingHour);

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, WorkingHour $workingHour)
    {
        $request->validate([
            'day' => 'required|string|max:191',
            'opening_time'=>'required',
            'closing_time'=>'required'
        ]);
        $restaurant = auth()->user()->restaurant;
        if (!$restaurant || (int)$workingHour->restaurant_id !== (int)$restaurant->id) {
            abort(403, 'Accès non autorisé');
        }

        $workingHour->update([
            'Day' => $request->day,
            'opening_time' => $request->opening_time,
            'closing_time' => $request->closing_time,
        ]);
        $alert['type'] = 'success';
        $alert['message'] = 'Horaire de travail mis à jour avec succès';
        return redirect()->route('working_hour.index')->with('alert', $alert);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(WorkingHour $workingHour)
    {
        $restaurant = auth()->user()->restaurant;
        if (!$restaurant || (int)$workingHour->restaurant_id !== (int)$restaurant->id) {
            abort(403, 'Accès non autorisé');
        }
        $workingHour->delete();

        $alert['type'] = 'success';
        $alert['message'] = 'Supprimé avec succès';
        return redirect()->route('working_hour.index')->with('alert', $alert);
    }
}
