<?php

namespace App\Http\Controllers\restaurant;

use App\Category;
use App\Http\Controllers\Controller;
use App\Restaurant;
use App\User;
use DemeterChain\C;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;

class CategoryController extends Controller
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

        $categories = $restaurant->categories;
        return view('restaurant.category.index')->with('categories', $categories);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create()
    {

        return view('restaurant.category.create');
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
        ]);
        $restaurant = auth()->user()->restaurant;
        if (!$restaurant) {
            return redirect()->route('restaurant.dashboard')->with('alert', [
                'type' => 'danger',
                'message' => "Aucun restaurant n'est associé à votre compte."
            ]);
        }

        // Relation = set automatiquement restaurant_id
        $restaurant->categories()->create([
            'name' => $request->name,
        ]);
        $alert['type'] = 'success';
        $alert['message'] = 'Catégorie créée avec succès';
        return redirect()->route('category.index')->with('alert', $alert);
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
    public function edit(Category $category)
    {
        $restaurant = auth()->user()->restaurant;
        if (!$restaurant) {
            return redirect()->route('restaurant.dashboard')->with('alert', [
                'type' => 'danger',
                'message' => "Aucun restaurant n'est associé à votre compte."
            ]);
        }

        // Sécurité: empêcher l'édition d'une catégorie d'un autre restaurant
        if ((int)$category->restaurant_id !== (int)$restaurant->id) {
            abort(403, 'Accès non autorisé');
        }

        $categories = $restaurant->categories;
        return view('restaurant.category.index', compact('category','categories'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name' => 'required|string|max:191',
        ]);
        $category->update($request->all());
        $alert['type'] = 'success';
        $alert['message'] = 'Catégorie mise à jour avec succès';
        return redirect()->route('category.index')->with('alert', $alert);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Category $category)
    {
        $category->delete();

        $alert['type'] = 'success';
        $alert['message'] = 'Catégorie supprimée avec succès';
        return redirect()->route('category.index')->with('alert', $alert);
    }
    public function search(Request $request,Category $category)
    {
        $search = $request->input($category->id);
        $categories = Category::search($search)->get();
        return redirect()->route('category.index')->with('categories',$categories);
    }
}
