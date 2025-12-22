<?php

namespace App\Http\Controllers\restaurant;

use App\Category;
use App\Http\Controllers\Controller;
use App\Product;
use App\Restaurant;
use App\Optional;
use App\AddOnsTitle;
use App\Services\DataSyncService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        // dd(auth()->user()->restaurant()->first()->services);
        $products = Product::where('restaurant_id',auth()->user()->restaurant->id)->get();
        return view('restaurant.product.index')->with('products', $products);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create()
    {
        return view('restaurant.product.create');

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'category_id'=>'required|integer',
            'name'=>'required|string|max:191',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:4096|required_without:image_url',
            'image_url' => 'nullable|url|max:2048|required_without:image',
            'price'=>'required',
            'discount_price'=>'nullable',
            'description'=>'nullable|string|max:191',
            'size'=>'nullable'
        ]);
        $restaurant = auth()->user()->restaurant;
        if (!$restaurant) {
            return redirect()->route('product.index')->with('alert', [
                'type' => 'danger',
                'message' => "Aucun restaurant n'est associé à votre compte."
            ]);
        }

        // Sécurité: la catégorie doit appartenir au restaurant
        $category = Category::where('id', $request->category_id)
            ->where('restaurant_id', $restaurant->id)
            ->firstOrFail();

        $product = $category->products()->create([
            'restaurant_id' => $restaurant->id,
            'category_id' => $category->id,
            'name' => $request->name,
            'price' => $request->price,
            'discount_price' => $request->discount_price,
            'description' => $request->description,
            'size' => $request->size,
        ]);

        // Image: upload ou URL
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $destination = public_path('images/product_images');
            if (!is_dir($destination)) {
                @mkdir($destination, 0775, true);
            }

            $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                . '-' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move($destination, $filename);
            $product->image = $filename;
            $product->save();
        } elseif ($request->filled('image_url')) {
            $product->image = $request->image_url;
            $product->save();
        }

        // Invalider le cache pour synchroniser les données
        DataSyncService::invalidateProductCache($product->id);
        
        $alert['type'] = 'success';
        $alert['message'] = 'Produit créé avec succès';
        return redirect()->route('product.index')->with('alert', $alert);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $prod = Db::table('products')->find($id);
        $optional = DB::table('optionals')->where('product_id', $id)->get();
        $required = DB::table('requireds')->where('product_id', $id)->get();
        $addons = DB::table('add_ons_titles')->where('product_id', $id)->get();
        foreach ($optional as $key => $value) {
            foreach ($addons as $key1 => $value1) 
                if($value->add_on_title_id == $value1->id)
                    $value->add_on_title_id = $value1->title;
        }
        foreach ($required as $key => $value) {
            foreach ($addons as $key1 => $value1) 
                if($value->add_on_title_id == $value1->id)
                    $value->add_on_title_id = $value1->title;
        }
        // dd($required);
        return view('restaurant.product.addon',compact('optional','addons','prod','required'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(Product $product)
    {
        return view('restaurant.product.edit')->with('product', $product);

    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Product $product)
    {
        $request->validate([
            'category_id'=>'required|integer',
            'name'=>'required|string|max:191',
            'price'=>'required',
            'discount_price'=>'nullable',
            'description'=>'nullable|string|max:191',
            'size'=>'nullable',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:4096',
            'image_url' => 'nullable|url|max:2048',
        ]);
        $restaurant = auth()->user()->restaurant;
        if (!$restaurant || (int)$product->restaurant_id !== (int)$restaurant->id) {
            abort(403, 'Accès non autorisé');
        }

        // Sécurité: la catégorie doit appartenir au restaurant
        $category = Category::where('id', $request->category_id)
            ->where('restaurant_id', $restaurant->id)
            ->firstOrFail();

        $oldImage = $product->image;

        $product->update([
            'category_id' => $category->id,
            'name' => $request->name,
            'price' => $request->price,
            'discount_price' => $request->discount_price,
            'description' => $request->description,
            'size' => $request->size,
        ]);

        // Si upload -> remplacer (et supprimer l'ancien fichier local)
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $destination = public_path('images/product_images');
            if (!is_dir($destination)) {
                @mkdir($destination, 0775, true);
            }

            $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                . '-' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move($destination, $filename);
            $product->image = $filename;
            $product->save();

            // Supprimer ancien fichier local si besoin
            if ($oldImage && strpos($oldImage, 'http') !== 0) {
                $oldPath = public_path('images/product_images/' . $oldImage);
                if (File::exists($oldPath)) {
                    @unlink($oldPath);
                }
            }
        } elseif ($request->filled('image_url')) {
            // URL externe: remplacer (supprimer l'ancien fichier local)
            $product->image = $request->image_url;
            $product->save();

            if ($oldImage && strpos($oldImage, 'http') !== 0) {
                $oldPath = public_path('images/product_images/' . $oldImage);
                if (File::exists($oldPath)) {
                    @unlink($oldPath);
                }
            }
        }

        // Invalider le cache pour synchroniser les données
        DataSyncService::invalidateProductCache($product->id);
        
        $alert['type'] = 'success';
        $alert['message'] = 'Produit mis à jour avec succès';
        return redirect()->route('product.index')->with('alert', $alert);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Product $product)
    {
        $restaurant = auth()->user()->restaurant;
        if (!$restaurant || (int)$product->restaurant_id !== (int)$restaurant->id) {
            abort(403, 'Accès non autorisé');
        }

        $restaurantId = $product->restaurant_id;

        if ($product->image && strpos($product->image, 'http') !== 0) {
            $path = public_path('images/product_images/' . $product->image);
            if (File::exists($path)) {
                @unlink($path);
            }
        }
        $product->delete();
        
        // Invalider le cache pour synchroniser les données
        DataSyncService::invalidateRestaurantCache($restaurantId);
        
        $alert = [];
        $alert['type'] = 'success';
        $alert['message'] = 'Produit supprimé avec succès';

        return redirect()->route('product.index')->with('alert', $alert);
    }
    public function change_product_featured_status(Product $product)
    {
        if ($product->featured)
            $product->featured = false;
        else
            $product->featured = true;
        $product->save();
        
        // Invalider le cache pour synchroniser les données
        DataSyncService::invalidateProductCache($product->id);
        
        $alert = [];
        $alert['type'] = 'success';
        $alert['message'] = 'Statut du produit mis à jour avec succès';
        return redirect()->back()->with('alert', $alert);
    }
}
