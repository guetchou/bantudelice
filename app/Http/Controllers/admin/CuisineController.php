<?php

namespace App\Http\Controllers\admin;

use App\Cuisine;
use App\Http\Controllers\Controller;
use App\Services\DataSyncService;
use Illuminate\Http\Request;

class CuisineController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $cuisines = Cuisine::all();
        return view('admin.cuisine.index')->with('cuisines', $cuisines);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create()
    {
        return view('admin.cuisine.create');
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
            'name' => 'required|string|max:191',
            'image'=>'required|image|mimes:png,jpg'
        ]);
        $cuisine=Cuisine::create($request->all());
        $image = $request->image;
                    $destination = 'images/cuisine';
                    if ($request->hasFile('image')) {
                        $filename = strtolower(
                            pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME)
                            . '-'
                            . uniqid()
                            . '.'
                            . $image->getClientOriginalExtension()
                        );
                        $image->move($destination, $filename);
                        str_replace(" ", "-", $filename);
                        $cuisine->image = $filename;
                        $cuisine->save();
                    }
        
        // Invalider le cache pour synchroniser les données
        DataSyncService::invalidateCuisineCache($cuisine->id);
        
        $alert['type'] = 'success';
        $alert['message'] = 'Cuisine créée avec succès';
        return redirect()->route('cuisine.index')->with('alert', $alert);
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
    public function edit(Cuisine $cuisine)
    {
        return view('admin.cuisine.edit')->with('cuisine', $cuisine);

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Cuisine $cuisine)
    {
        $request->validate([
            'name' => 'required|string|max:191',
            'image' => 'nullable|image|mimes:png,jpg,jpeg'
        ]);
        
        // Mettre à jour le nom
        $cuisine->name = $request->input('name');
        
        // Gérer l'image seulement si une nouvelle image est fournie
        $destination = 'images/cuisine';
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = strtolower(
                pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME)
                . '-'
                . uniqid()
                . '.'
                . $image->getClientOriginalExtension()
            );
            $image->move($destination, $filename);
            $filename = str_replace(" ", "-", $filename);
            $cuisine->image = $filename;
        }
        // Si pas d'image, on garde l'ancienne
        
        $cuisine->save();
        
        // Invalider le cache pour synchroniser les données
        DataSyncService::invalidateCuisineCache($cuisine->id);
        
        $alert['type'] = 'success';
        $alert['message'] = 'Cuisine mise à jour avec succès';
        return redirect()->route('cuisine.index')->with('alert', $alert);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Cuisine $cuisine)
    {
        $cuisineId = $cuisine->id;
        $cuisine->delete();
        
        // Invalider le cache pour synchroniser les données
        DataSyncService::invalidateCuisineCache($cuisineId);

        $alert['type'] = 'success';
        $alert['message'] = 'Cuisine supprimée avec succès';
        return redirect()->route('cuisine.index')->with('alert', $alert);
    }
}
