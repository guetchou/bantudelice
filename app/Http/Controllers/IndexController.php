<?php

namespace App\Http\Controllers;

use App\Cuisine;
use App\Restaurant;
use App\Rating;
use App\Order;
use App\Product;
use App\Category;
use App\Cart;
use App\User;
use App\Charge;
use App\Voucher;
use App\Services\DataSyncService;
use App\Services\LoyaltyService;
use App\Services\NotificationService;
use App\Services\ConfigService;
use App\UserToken;
use Illuminate\Support\Facades\Mail;
use App\Mail\RegisterEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;

class IndexController extends Controller
{

    public function home()
    {
        // Utiliser le service de synchronisation pour garantir la cohérence
        $restaurants = DataSyncService::getActiveRestaurants(8, false);
        
        // Produits populaires/en vedette
        $products = DataSyncService::getFeaturedProducts(12);

        // Plat du jour (rotation quotidienne)
        $dailySpecials = DataSyncService::getDailySpecialProducts(8);
        
        // Cuisines pour le menu
        $cuisines = DataSyncService::getCuisinesWithRestaurants(12);
        
        return view('frontend.index-modern', compact('restaurants', 'products', 'dailySpecials', 'cuisines'));
    }
    
    /**
     * Afficher tous les restaurants (page "Voir tout")
     * Supporte les filtres et la pagination via AJAX
     */
    public function allRestaurants(Request $request)
    {
        // Récupérer toutes les cuisines pour les filtres
        $cuisines = DataSyncService::getCuisinesWithRestaurants(null);
        
        // Récupérer les valeurs par défaut pour les filtres
        $defaultDeliveryFee = \App\Services\ConfigService::getDefaultDeliveryFee();
        $topRatedThreshold = \App\Services\ConfigService::getTopRatedThreshold();
        
        // Si c'est une requête AJAX, retourner JSON
        if ($request->ajax() || $request->wantsJson()) {
            $restaurantService = new \App\Services\RestaurantService();
            $filters = [
                'city' => $request->get('city'),
                'min_rating' => $request->get('min_rating'),
                'max_delivery_fee' => $request->get('max_delivery_fee'),
                'cuisine' => $request->get('cuisine'),
                'search' => $request->get('search'),
                'sort' => $request->get('sort', 'popular'),
                'per_page' => $request->get('per_page', 12),
            ];
            
            $filters = array_filter($filters, function($value) {
                return $value !== null && $value !== '';
            });
            
            if (isset($filters['cuisine']) && is_string($filters['cuisine'])) {
                $filters['cuisine'] = explode(',', $filters['cuisine']);
            }
            
            $paginator = $restaurantService->searchRestaurants($filters);
            
            // Formater les données comme l'API
            $defaultDeliveryTimeMin = \App\Services\ConfigService::getDefaultDeliveryTimeMin();
            $defaultDeliveryTimeMax = \App\Services\ConfigService::getDefaultDeliveryTimeMax();
            $defaultRating = \App\Services\ConfigService::getDefaultRating();
            $topRatedThreshold = \App\Services\ConfigService::getTopRatedThreshold();
            $topRatedMinReviews = \App\Services\ConfigService::getTopRatedMinReviews();
            
            $data = $paginator->getCollection()->map(function($restaurant) use ($defaultDeliveryFee, $defaultDeliveryTimeMin, $defaultDeliveryTimeMax, $defaultRating, $topRatedThreshold, $topRatedMinReviews) {
                $etaMin = $defaultDeliveryTimeMin;
                $etaMax = $defaultDeliveryTimeMax;
                if ($restaurant->avg_delivery_time) {
                    try {
                        $time = \Carbon\Carbon::parse($restaurant->avg_delivery_time);
                        $minutes = $time->hour * 60 + $time->minute;
                        if ($minutes > 0) {
                            $etaMin = max(15, $minutes - 5);
                            $etaMax = $minutes + 5;
                        }
                    } catch (\Exception $e) {}
                }
                
                $deliveryFee = $restaurant->delivery_charges ?? $defaultDeliveryFee;
                $cuisines = $restaurant->cuisines->pluck('name')->toArray();
                $isTopRated = ($restaurant->featured ?? false) || ($restaurant->avg_rating >= $topRatedThreshold && $restaurant->rating_count >= $topRatedMinReviews);
                
                return [
                    'id' => $restaurant->id,
                    'name' => $restaurant->name,
                    'avg_rating' => round($restaurant->avg_rating, 1),
                    'rating_count' => $restaurant->rating_count,
                    'delivery_fee' => (float)$deliveryFee,
                    'eta_min' => (int)$etaMin,
                    'eta_max' => (int)$etaMax,
                    'eta_display' => $etaMin . '-' . $etaMax . ' min',
                    'cuisines' => $cuisines,
                    'cuisines_display' => implode(' · ', array_slice($cuisines, 0, 3)),
                    'is_top_rated' => $isTopRated,
                    'is_featured' => $restaurant->featured ?? false,
                    'thumbnail_url' => $restaurant->logo ? url('images/restaurant_images/' . $restaurant->logo) : null,
                    'city' => $restaurant->city,
                    'address' => $restaurant->address,
                    'min_order' => $restaurant->min_order ?? 0,
                ];
            });
            
            return response()->json([
                'status' => true,
                'data' => $data,
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                ],
            ]);
        }
        
        // Sinon, retourner la vue avec les données initiales
        $restaurantService = new \App\Services\RestaurantService();
        $filters = [
            'cuisine' => $request->get('cuisine'),
            'sort' => $request->get('sort', 'popular'),
            'per_page' => 12,
        ];
        
        if (isset($filters['cuisine']) && is_string($filters['cuisine'])) {
            $filters['cuisine'] = [(int)$filters['cuisine']];
        }
        
        $paginator = $restaurantService->searchRestaurants($filters);
        $restaurants = $paginator->getCollection();
        $cuisineId = $request->get('cuisine');
        
        return view('frontend.restaurants', compact('restaurants', 'cuisines', 'cuisineId', 'paginator', 'defaultDeliveryFee', 'topRatedThreshold'));
    }
    public function resturantDetail($id)
    {
        // Récupérer le restaurant directement (sans utiliser getRestaurantWithData qui charge working_hours)
        $restaurant = Restaurant::where('id', $id)
            ->where('approved', true)
            ->with(['cuisines', 'ratings'])
            ->first();
        
        if (!$restaurant) {
            abort(404, 'Restaurant non trouvé ou non approuvé');
        }
        
        // Charger les relations nécessaires (certaines tables peuvent ne pas exister)
        try {
            $restaurant->load(['ratings.user', 'cuisines']);
        } catch (\Exception $e) {
            // Si certaines relations échouent, continuer sans elles
        }
        
        // Récupérer le statut ouvert/fermé (si le service existe)
        try {
            if (class_exists(\App\Services\RestaurantStatusService::class)) {
                $status = \App\Services\RestaurantStatusService::getStatus($restaurant);
            } else {
                $status = ['is_open' => true, 'message' => 'Ouvert'];
            }
        } catch (\Exception $e) {
            $status = ['is_open' => true, 'message' => 'Ouvert'];
        }
        
        // Récupérer les avis récents (10 premiers) - si la table existe
        try {
            $recentReviews = $restaurant->ratings()
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        } catch (\Exception $e) {
            $recentReviews = collect([]);
        }
        
        // Récupérer les promos actives - si la table vouchers existe
        try {
            $activePromos = $restaurant->vouchers()
                ->where('start_date', '<=', now())
                ->where('end_date', '>=', now())
                ->orderBy('discount', 'desc')
                ->get();
        } catch (\Exception $e) {
            $activePromos = collect([]);
        }
        
        // Récupérer les catégories avec produits pour le menu
        $abc = Category::where('restaurant_id', $id)
            ->with(['products' => function($q) use ($id) {
                $q->where('restaurant_id', $id)
                  ->orderBy('sort_order')
                  ->orderBy('featured', 'desc')
                  ->orderBy('name');
            }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
        
        // Toutes les cuisines pour le menu de navigation
        $cuisines = DataSyncService::getCuisinesWithRestaurants();
        
        // Valeurs par défaut depuis ConfigService
        $defaultRating = \App\Services\ConfigService::getDefaultRating();
        $defaultDeliveryFee = \App\Services\ConfigService::getDefaultDeliveryFee();
        
        return view('frontend.menu', compact(
            'restaurant', 
            'cuisines', 
            'abc', 
            'status', 
            'recentReviews', 
            'activePromos',
            'defaultRating',
            'defaultDeliveryFee'
        ));
    }

    public function proDetail($id)
    {
         $proDetail=Product::findOrFail($id);
         $restaurant=Restaurant::findOrFail($proDetail->restaurant_id);

        $products=Product::get();
        //dd($getReq);
        return view('frontend.product_detail', compact('products', 'proDetail', 'restaurant'));
    }
    public function cartDeatil()
    {
        // Si l'utilisateur est connecte
        if(auth()->check()){
            $id = auth()->user()->id;
            
            // Migrer le panier de session vers la base de donnees
            $this->migrateSessionCartToDb();
            
            $cartData = DB::table('carts')
                ->join('products', 'products.id', '=', 'carts.product_id')
                ->select('carts.*', 'products.image', 'products.name', 'products.description', 'products.price')
                ->where('user_id', $id)->get();
                
            $total = Cart::where('user_id', $id)->sum(\DB::raw('price * qty'));
            $rest_id = Cart::where('user_id', $id)->first();
            $check = $rest_id ? Restaurant::find($rest_id->restaurant_id) : null;
            
            return view('frontend.cart', compact('cartData', 'total', 'check'));
        }
        // Pour les invites, utiliser la session
        else {
            $cart = session()->get('cart', []);
            $cartData = collect();
            $total = 0;
            $check = null;
            
            if(!empty($cart)){
                $productIds = array_keys($cart);
                $products = Product::whereIn('id', $productIds)->get();
                
                foreach($products as $product){
                    $qty = $cart[$product->id]['qty'] ?? 1;
                    $subTotal = $product->price * $qty;
                    $total += $subTotal;
                    
                    $cartData->push((object)[
                        'id' => $product->id,
                        'product_id' => $product->id,
                        'name' => $product->name,
                        'image' => $product->image,
                        'description' => $product->description,
                        'price' => $product->price,
                        'qty' => $qty,
                        'sub_total' => $subTotal,
                        'restaurant_id' => $cart[$product->id]['restaurant_id'] ?? null
                    ]);
                    
                    if(!$check && isset($cart[$product->id]['restaurant_id'])){
                        $check = Restaurant::find($cart[$product->id]['restaurant_id']);
                    }
                }
            }
            
            return view('frontend.cart', compact('cartData', 'total', 'check'));
        }
    }
    
    // Migrer le panier de session vers la base de donnees apres connexion
    private function migrateSessionCartToDb()
    {
        if(!auth()->check()) return;
        
        $cart = session()->get('cart', []);
        if(empty($cart)) return;
        
        $userId = auth()->user()->id;
        
        foreach($cart as $productId => $item){
            $existing = Cart::where('product_id', $productId)->where('user_id', $userId)->first();
            $product = Product::find($productId);
            
            if(!$product) continue;
            
            if($existing){
                $existing->increment('qty', $item['qty']);
                $existing->sub_total = $existing->qty * $product->price;
                $existing->save();
            } else {
                Cart::create([
                    'product_id' => $productId,
                    'user_id' => $userId,
                    'restaurant_id' => $item['restaurant_id'],
                    'qty' => $item['qty'],
                    'sub_total' => $product->price * $item['qty']
                ]);
            }
        }
        
        // Vider le panier de session
        session()->forget('cart');
    }
    public function deleteItem($id=null){
        // Si l'utilisateur est connecte
        if(auth()->check()){
            $delete_item = Cart::findOrFail($id);
            $delete_item->delete();
        }
        // Pour les invites, supprimer de la session
        else {
            $cart = session()->get('cart', []);
            if(isset($cart[$id])){
                unset($cart[$id]);
                session()->put('cart', $cart);
            }
        }
        return back()->with('message','Supprime avec succes !');
    }

    public function updateItem(Request $request, $cart){
        // Si l'utilisateur est connecte
        if(auth()->check()){
            $cartItem = Cart::find($cart);
            if($cartItem){
                $qty = (int) $request->qty;
                if($qty > 0){
                    $cartItem->qty = $qty;
                    $cartItem->update();
                } else {
                    $cartItem->delete();
                }
            }
        }
        // Pour les invites, mettre a jour la session
        else {
            $sessionCart = session()->get('cart', []);
            if(isset($sessionCart[$cart])){
                $qty = (int) $request->qty;
                if($qty > 0){
                    $sessionCart[$cart]['qty'] = $qty;
                    session()->put('cart', $sessionCart);
                } else {
                    unset($sessionCart[$cart]);
                    session()->put('cart', $sessionCart);
                }
            }
        }
        
        // Si c'est une requête AJAX, retourner JSON
        if($request->ajax() || $request->wantsJson()){
            return response()->json([
                'status' => true,
                'message' => 'Quantité mise à jour'
            ]);
        }
        
        return back()->with('message','Quantite mise a jour');
    }

    public function Login()
    {
        if (auth()->check())
            return redirect('/');
        return view('frontend.login');
    }
    public function SignUp()
    {
        if (auth()->check())
            return redirect('/');
        return view('frontend.signup');
    }
    
     public function register(Request $request)
    {
        $request->validate([
                'name'=>'nullable',
                'email' => 'required|email|max:255|unique:users',
                'password' => 'required',
                'phone'=>'required|unique:users',
                'image' => 'nullable|image|mimes:jpeg,png,jpg',
            ]);

            // Définir explicitement le type 'user' pour les clients
            $request['password'] = bcrypt($request->password);
            $request['type'] = 'user';
            
            DB::beginTransaction();

                try {
                    $user = User::create($request->all());
                    $image = $request->image;
                    $destination = 'images/profile_images';
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
                        $user->image = $filename;
                        $user->save();
                    }
                    
            $data = array(
            'name' => $user->name,
            'email' => $user->email,
        );
            //sending email
            Mail::to($request->email)->send(new RegisterEmail($data));
                    DB::commit();
                    
                    // Connecter automatiquement l'utilisateur après inscription
                    auth()->login($user);
                    
                } catch (\Exception $exception) {
                    DB::rollBack();
                    return response()->json([
                        'message' => $exception->getMessage()
                    ], 403);
                }
                
                // Rediriger vers la page d'accueil avec un message de succès
                return redirect('/')->with('alert', [
                    'type' => 'success',
                    'message' => 'Inscription réussie ! Bienvenue sur ' . ConfigService::getCompanyName() . '.'
                ]);
            }
    
    
   public function Checkout(){
        // Connexion requise pour le paiement
        if(!auth()->check()){
            // Stocker l'intention de checkout pour rediriger apres connexion
            session()->put('checkout_redirect', true);
            return redirect()->route('user.login')->with('message', 'Veuillez vous connecter pour finaliser votre commande');
        }
        
        $id = auth()->user()->id;
        
        // Migrer le panier de session vers la base de donnees
        $this->migrateSessionCartToDb();
        
        $checkoutData = DB::table('carts')
            ->join('products', 'products.id', '=', 'carts.product_id')
            ->select('carts.*', 'products.image', 'products.name', 'products.description')
            ->where('user_id', $id)->get();
            
        $name = $checkoutData->pluck('name')->toArray();
        $qty = $checkoutData->pluck('qty')->toArray();
        $total = Cart::where('user_id', $id)->sum(\DB::raw('price * qty'));
        $resturant = Cart::where('user_id', $id)->first();
        $charges = Charge::first();
        $address = DB::table('user_address')->where('user_id', $id)->first();

        return view('frontend.checkout', compact('checkoutData', 'total', 'charges', 'address', 'name', 'qty', 'resturant'));
   }
   public function profile(){
       if (auth()->check())
       return view('frontend.profile');
       else
       return redirect('/');
   }

   public function logout()
    {
        auth()->logout();
        return redirect('/');
    }

    public function thanks(){
        $order = Order::where('order_no', $_GET['orderID'])->first();
        if($order){
            Cart::where('user_id', $order->user_id)->delete();
            return view('frontend.thanks', compact('order')); 
            }else{
            abort('404'); 
            }
   }

    public function addToCart(Request $request){
        $inputToCart = $request->all();
        
        if(empty($inputToCart['qty']) || $inputToCart['qty'] == ""){
            $message = 'Veuillez sélectionner une quantité';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            return back()->with('message', $message);
        }
        
        // Récupérer le produit pour obtenir le prix
        $product = Product::find($inputToCart['product_id']);
        if(!$product){
            $message = 'Produit non trouvé';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 404);
            }
            return back()->with('message', $message);
        }

        // Bloquer l'ajout au panier si le produit est indisponible
        if (isset($product->is_available) && !$product->is_available) {
            $message = 'Ce produit est indisponible pour le moment';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            return back()->with('message', $message);
        }
        
        // Définir le prix (prix remisé si disponible, sinon prix normal)
        $price = $product->discount_price > 0 ? $product->discount_price : $product->price;
        
        // Si l'utilisateur est connecté, utiliser la base de données
        if(auth()->check()){
            $inputToCart['user_id'] = auth()->user()->id;
            $inputToCart['price'] = $price;
            $inputToCart['sub_total'] = $price * $inputToCart['qty'];
            
            $existingCart = Cart::where(['product_id' => $inputToCart['product_id']])
                                ->where(['user_id' => $inputToCart['user_id']])->first();
            
            if($existingCart){
                $existingCart->increment('qty', $inputToCart['qty']);
                $existingCart->sub_total = $existingCart->qty * $price;
                $existingCart->save();
                $message = 'Quantité mise à jour !';
                $totalItems = Cart::where('user_id', auth()->user()->id)->sum('qty');
            }
            else{
                Cart::create($inputToCart);
                $message = 'Produit ajouté au panier';
                $totalItems = Cart::where('user_id', auth()->user()->id)->sum('qty');
            }
        }
        // Sinon, utiliser la session pour les invités
        else {
            $cart = session()->get('cart', []);
            $productId = $inputToCart['product_id'];
            $qty = (int)$inputToCart['qty'];
            $restaurantId = $inputToCart['restaurant_id'] ?? null;
            
            if(isset($cart[$productId])){
                $cart[$productId]['qty'] += $qty;
            } else {
                $cart[$productId] = [
                    'product_id' => $productId,
                    'restaurant_id' => $restaurantId,
                    'qty' => $qty,
                    'price' => $price
                ];
            }
            
            session()->put('cart', $cart);
            $message = 'Produit ajouté au panier';
            $totalItems = array_sum(array_column($cart, 'qty'));
        }
        
        // Retourner JSON si requête AJAX, sinon redirection classique
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'total_items' => $totalItems
            ]);
        }
        
        return back()->with('message', $message);
    }

        public function forgotPassword(Request $request)
        {
          $request->validate([
              'email'=>'required',
              'phone'=>'required',
              'password'=>'required',
            ]);
            $user=User::where('phone',$request->phone)->where('email',$request->email)->first();
            
            if($user){
                $request['password'] = bcrypt($request->password);
            $user->password=$request->password;
            $user->save(); 
            // Rediriger vers la page de connexion après succès
            return redirect()->route('user.login')->with('alert', [
                'type' => 'success',
                'message' => 'Mot de passe mis à jour avec succès ! Vous pouvez maintenant vous connecter.'
            ]);
                
            }
            else{
                return back()->with('alert', [
                    'type' => 'error',
                    'message' => 'Email ou téléphone invalide !'
                ]);
            }

        }


     public function searchResult(Request $request)
        {
              $request->validate([
                'qurey'=>'required',
                ]);
                $qurey=$request->qurey;
                
          // Utiliser le service de synchronisation pour une recherche avancée
          $filters = [
              'search' => $qurey,
              'min_rating' => $request->min_rating ?? null,
              'cuisine_id' => $request->cuisine_id ?? null,
              'city' => $request->city ?? null,
          ];
          
          $restaurants = DataSyncService::searchRestaurants($qurey, array_filter($filters));

            return view('frontend.search', compact('restaurants', 'qurey'));
        }
        
    /**
     * Recherche AJAX en temps réel
     */
    public function searchAjax(Request $request)
    {
        $query = $request->get('q', '');
        
        if (strlen($query) < 2) {
            return response()->json(['results' => []]);
        }
        
        $filters = [
            'search' => $query,
            'min_rating' => $request->get('min_rating'),
            'cuisine_id' => $request->get('cuisine_id'),
        ];
        
        $restaurants = DataSyncService::searchRestaurants($query, array_filter($filters), 10);
        
        $results = $restaurants->map(function($restaurant) {
            return [
                'id' => $restaurant->id,
                'name' => $restaurant->name,
                'address' => $restaurant->address,
                'city' => $restaurant->city,
                'logo' => asset('images/restaurant_images/' . $restaurant->logo),
                'rating' => number_format($restaurant->ratings()->avg('rating') ?: \App\Services\ConfigService::getDefaultRating(), 1),
                'cuisines' => $restaurant->cuisines->pluck('name')->implode(', '),
                'url' => route('resturant.detail', $restaurant->id)
            ];
        });
        
        return response()->json(['results' => $results]);
    }

    public function about()
    {
        return view('frontend.about');

    }
    public function contact()
    {
        return view('frontend.contact');
    }
    public function terms()
    {
        return view('frontend.terms');
    }
    public function refundPolicy()
    {
        return view('frontend.policy');
    }

    public function privacyPolicy()
    {
        return view('frontend.privacy_policy');
    }

    /**
     * Page publique "Suppression des données utilisateur" (exigence Meta).
     */
    public function dataDeletion()
    {
        return view('frontend.data_deletion');
    }
    
    public function faq()
    {
        return view('frontend.faq');
    }
    
    public function help()
    {
        return view('frontend.help');
    }
    
    public function offers()
    {
        return view('frontend.offers');
    }
    
    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
        ]);
        
        $user = auth()->user();
        $user->name = $request->name;
        $user->phone = $request->phone;
        $user->save();
        
        return back()->with('success', 'Profil mis à jour avec succès !');
    }
    
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|min:6|confirmed',
        ]);
        
        $user = auth()->user();
        
        if (!\Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Le mot de passe actuel est incorrect.']);
        }
        
        $user->password = \Hash::make($request->password);
        $user->save();
        
        return back()->with('success', 'Mot de passe mis à jour avec succès !');
    }
    
    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
        
        $user = auth()->user();
        
        if ($request->hasFile('avatar')) {
            $image = $request->file('avatar');
            $imageName = time() . '_' . $user->id . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images/profile_images'), $imageName);
            
            // Delete old image if exists
            if ($user->image && file_exists(public_path('images/profile_images/' . $user->image))) {
                unlink(public_path('images/profile_images/' . $user->image));
            }
            
            $user->image = $imageName;
            $user->save();
        }
        
        return back()->with('success', 'Photo de profil mise à jour !');
    }

    /**
     * Suppression self-service: anonymise le compte et déconnecte l'utilisateur.
     */
    public function deleteAccount(Request $request)
    {
        if (!auth()->check()) {
            return redirect()->route('user.login')->with('error', 'Veuillez vous connecter.');
        }

        $request->validate([
            'confirm' => 'required|string|in:SUPPRIMER',
        ], [
            'confirm.in' => 'Veuillez saisir "SUPPRIMER" pour confirmer.',
        ]);

        $user = auth()->user();
        \App\Services\UserDeletionService::anonymizeUser($user, [
            'source' => 'self_service',
        ]);

        auth()->logout();
        return redirect()->route('home')->with('message', 'Votre compte a été supprimé et vos données personnelles ont été anonymisées.');
    }
    
    public function forgot()
    {
        return view('frontend.forgot');

    }
    
    
    public function restaurantByCuisine($id)
    {
        // Utiliser le service de synchronisation
        $restaurants = DataSyncService::getRestaurantsByCuisine($id);
        $cuisine = Cuisine::find($id);
        
        if (!$cuisine) {
            abort(404, 'Cuisine non trouvée');
        }
        
        return view('frontend.restaurant_by_cuisines', compact('restaurants', 'cuisine'));
    }
     public function checkVoucher(Request $request){
         $voucher = Voucher::where('name', $request->voucher)
                          ->where('restaurant_id', $request->restaurant)
                          ->where('start_date', '<=', Carbon::now())
                          ->where('end_date', '>=', Carbon::now())
                          ->first();
         
         if($voucher){
             return response()->json([
                 'status' => true,
                 'data' => $voucher,
                 'message' => 'Code promo valide !'
             ]);
         }
         else{
             return response()->json([
                 'status' => false,
                 'data' => null,
                 'message' => 'Code promo invalide ou expiré'
             ]);
         }
     }
     
    /**
     * Passer une commande (paiement cash ou mobile money)
     */
    public function getOrders(Request $request)
    {
        // Vérifier que l'utilisateur est connecté
        if(!auth()->check()){
            return redirect()->route('user.login')->with('message', 'Veuillez vous connecter');
        }
        
        $request->validate([
            'delivery_address' => 'required|string|max:500',
            'payment_method' => 'required|in:cash,mobile_money,paypal',
        ]);
        
        $userId = auth()->user()->id;
        $cartItems = Cart::where('user_id', $userId)->get();
        
        if($cartItems->isEmpty()){
            return redirect()->route('cart.detail')->with('message', 'Votre panier est vide');
        }
        
        // Récupérer les frais
        $charges = Charge::first();
        if(!$charges){
            // Créer des frais par défaut si inexistants
            $defaultDeliveryFee = \App\Services\ConfigService::getDefaultDeliveryFee();
            $charges = Charge::create([
                'delivery_fee' => $defaultDeliveryFee,
                'tax' => 5,
                'service_fee' => 2
            ]);
        }
        
        // Calcul des totaux
        $subTotal = 0;
        foreach($cartItems as $item){
            $product = Product::find($item->product_id);
            if($product){
                $price = $product->discount_price > 0 ? $product->discount_price : $product->price;
                $subTotal += $price * $item->qty;
            }
        }
        
        $tax = ($charges->tax / 100) * $subTotal;
        $serviceFee = (($charges->delivery_fee + $tax + $subTotal) / 100) * $charges->service_fee;
        $driverTip = $request->driver_tip ?? 0;
        $total = $subTotal + $charges->delivery_fee + $tax + $serviceFee + $driverTip;
        
        // Appliquer le voucher si présent
        $discount = 0;
        if($request->voucher_code){
            $restaurantId = $cartItems->first()->restaurant_id;
            $voucher = Voucher::where('name', $request->voucher_code)
                              ->where('restaurant_id', $restaurantId)
                              ->where('start_date', '<=', now())
                              ->where('end_date', '>=', now())
                              ->first();
            if($voucher){
                $discount = ($voucher->discount / 100) * $subTotal;
                $total -= $discount;
            }
        }
        
        // Appliquer les points de fidélité si utilisés
        $loyaltyDiscount = 0;
        $loyaltyPointsUsed = 0;
        if($request->use_loyalty_points && auth()->check()){
            $loyaltyPoints = LoyaltyService::getBalance($userId);
            if($loyaltyPoints > 0){
                $maxDiscount = LoyaltyService::calculateDiscount($loyaltyPoints);
                // Limiter à 20% du total avant points
                $totalBeforeLoyalty = $subTotal + $charges->delivery_fee + $tax + $serviceFee;
                $loyaltyDiscount = min($maxDiscount, $totalBeforeLoyalty * 0.2);
                
                // Calculer les points nécessaires (100 points = 1000 FCFA)
                $loyaltyPointsUsed = floor(($loyaltyDiscount / 1000) * 100);
                
                // Limiter aux points disponibles
                if($loyaltyPointsUsed > $loyaltyPoints){
                    $loyaltyPointsUsed = $loyaltyPoints;
                    $loyaltyDiscount = LoyaltyService::calculateDiscount($loyaltyPoints);
                }
                
                if($loyaltyPointsUsed > 0 && LoyaltyService::usePoints($userId, $loyaltyPointsUsed, null)){
                    $total -= $loyaltyDiscount;
                } else {
                    $loyaltyDiscount = 0;
                    $loyaltyPointsUsed = 0;
                }
            }
        }
        
        // Générer le numéro de commande
        $orderNo = 'TD-' . date('Ymd') . '-' . rand(1000, 9999);
        
        // Créer les commandes
        DB::beginTransaction();
        try {
            foreach($cartItems as $item){
                $product = Product::find($item->product_id);
                $price = $product ? ($product->discount_price > 0 ? $product->discount_price : $product->price) : $item->price;
                
                DB::table('orders')->insert([
                    'user_id' => $userId,
                    'restaurant_id' => $item->restaurant_id,
                    'product_id' => $item->product_id,
                    'qty' => $item->qty,
                    'price' => $price,
                    'driver_id' => null,
                    'order_no' => $orderNo,
                    'offer_discount' => $discount + $loyaltyDiscount,
                    'tax' => $tax,
                    'delivery_charges' => $charges->delivery_fee,
                    'sub_total' => $subTotal,
                    'total' => $total,
                    'admin_commission' => 2,
                    'restaurant_commission' => 4,
                    'driver_tip' => $driverTip,
                    'delivery_address' => $request->delivery_address,
                    'latitude' => $request->d_lat ?? null,
                    'longitude' => $request->d_lng ?? null,
                    'd_lat' => $request->d_lat ?? '-4.2767',
                    'd_lng' => $request->d_lng ?? '15.2832',
                    'payment_method' => $request->payment_method,
                    'payment_status' => $request->payment_method == 'cash' ? 'pending' : 'pending',
                    'status' => 'pending',
                    'ordered_time' => now(),
                    'delivered_time' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            
            // Vider le panier
            Cart::where('user_id', $userId)->delete();
            
            // Récupérer les commandes créées pour créer les livraisons
            $orders = Order::where('order_no', $orderNo)->get();
            
            // Créer une livraison pour chaque commande et déclencher dispatch automatique
            $deliveryService = new \App\Services\DeliveryService();
            foreach($orders as $order) {
                try {
                    $delivery = $deliveryService->createForOrder($order);
                    
                    // Dispatch automatique : déclencher le job d'assignation
                    // Le job sera traité immédiatement si queue = 'sync', ou en arrière-plan si queue = 'database'
                    \App\Jobs\AutoAssignDeliveryJob::dispatch($delivery);
                } catch (\Exception $e) {
                    \Log::error('Erreur création livraison', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage()
                    ]);
                    // Ne pas bloquer la commande si la livraison échoue
                }
            }
            
            // Ajouter les points de fidélité
            $orderRecord = Order::where('order_no', $orderNo)->first();
            if ($orderRecord) {
                LoyaltyService::addPointsFromOrder($userId, $orderRecord->id, $total);
            }
            
            DB::commit();
            
            // Envoyer des notifications
            try {
                // Notification à l'utilisateur
                $userToken = UserToken::where('user_id', $userId)->first();
                if ($userToken && $userToken->device_tokens) {
                    NotificationService::sendToDevice(
                        $userToken->device_tokens,
                        'Commande confirmée',
                        'Votre commande #' . $orderNo . ' a été confirmée et est en préparation.',
                        'orderConfirmed',
                        $userId,
                        'user'
                    );
                }
                
                // Notification au restaurant
                $restaurant = Restaurant::find($cartItems->first()->restaurant_id);
                if ($restaurant && $restaurant->user_id) {
                    // Trouver l'utilisateur restaurant
                    $restaurantUser = User::where('id', $restaurant->user_id)
                                         ->where('type', 'restaurant')
                                         ->first();
                    if ($restaurantUser) {
                        $restaurantToken = UserToken::where('user_id', $restaurantUser->id)->first();
                        if ($restaurantToken && $restaurantToken->device_tokens) {
                            NotificationService::sendToDevice(
                                $restaurantToken->device_tokens,
                                'Nouvelle commande',
                                'Nouvelle commande #' . $orderNo . ' reçue.',
                                'newOrder',
                                $restaurantUser->id,
                                'restaurant'
                            );
                        }
                    }
                }
            } catch (\Exception $e) {
                // Log l'erreur mais ne bloque pas la commande
                \Log::error('Erreur lors de l\'envoi des notifications: ' . $e->getMessage());
            }
            
            // Stocker le numéro de commande en session
            session()->put('order_no', $orderNo);
            
            return redirect()->route('thanks', ['orderID' => $orderNo])
                             ->with('success', 'Commande passée avec succès !');
                             
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('message', 'Erreur lors de la commande: ' . $e->getMessage());
        }
    }
    
    /**
     * Obtenir le nombre d'articles dans le panier (pour AJAX)
     */
    public function getCartCount()
    {
        if(auth()->check()){
            $count = Cart::where('user_id', auth()->user()->id)->sum('qty');
        } else {
            $cart = session()->get('cart', []);
            $count = array_sum(array_column($cart, 'qty'));
        }
        
        return response()->json(['count' => $count]);
    }
    
    /**
     * Suivre une commande
     */
    public function trackOrder(Request $request, $orderNo = null)
    {
        if (!$orderNo && $request->has('order_no')) {
            $orderNo = $request->order_no;
        }
        
        if (!$orderNo) {
            return redirect()->route('user.profile')->with('message', 'Numéro de commande requis');
        }
        
        $order = Order::where('order_no', $orderNo)->first();
        
        if (!$order) {
            return redirect()->route('user.profile')->with('message', 'Commande non trouvée');
        }
        
        // Vérifier que l'utilisateur peut voir cette commande
        if (auth()->check() && $order->user_id != auth()->user()->id) {
            abort(403, 'Accès non autorisé');
        }
        
        // Charger la relation delivery avec driver
        $order->load(['delivery.driver', 'restaurant']);
        
        // Récupérer tous les produits de la commande
        $orderItems = Order::where('order_no', $orderNo)
                          ->with(['product', 'restaurant'])
                          ->get();
        
        // Calculer le temps estimé de livraison
        $estimatedTime = 30; // minutes par défaut
        if ($order->restaurant && $order->restaurant->avg_delivery_time) {
            $estimatedTime = $order->restaurant->avg_delivery_time;
        }
        
        // Calculer le temps écoulé depuis la commande
        $elapsedMinutes = now()->diffInMinutes($order->created_at);
        $remainingMinutes = max(0, $estimatedTime - $elapsedMinutes);
        
        // Récupérer les informations de livraison
        $delivery = $order->delivery;
        
        return view('frontend.track_order', compact('order', 'orderItems', 'estimatedTime', 'remainingMinutes', 'delivery'));
    }
    
    /**
     * API: Récupérer le statut d'une commande en temps réel
     * Route: GET /api/order/{orderNo}/status
     */
    public function getOrderStatus($orderNo)
    {
        try {
            $order = Order::where('order_no', $orderNo)
                ->with(['restaurant', 'driver', 'user'])
                ->first();
            
            if (!$order) {
                return response()->json([
                    'status' => false,
                    'message' => 'Commande non trouvée'
                ], 404);
            }
            
            // Vérifier l'autorisation (optionnel - peut être public ou avec auth)
            if (auth()->check() && $order->user_id != auth()->user()->id) {
                return response()->json([
                    'status' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }
            
            // Récupérer tous les items de la commande
            $orderItems = Order::where('order_no', $orderNo)
                ->with('product')
                ->get();
            
            // Calculer le temps estimé
            $estimatedTime = 30;
            if ($order->restaurant && $order->restaurant->avg_delivery_time) {
                $estimatedTime = $order->restaurant->avg_delivery_time;
            }
            
            $elapsedMinutes = now()->diffInMinutes($order->created_at);
            $remainingMinutes = max(0, $estimatedTime - $elapsedMinutes);
            
            // Déterminer le pourcentage de progression
            $progressPercentage = 0;
            $statusSteps = ['pending' => 0, 'prepairing' => 25, 'assign' => 50, 'pickup' => 75, 'onway' => 90, 'completed' => 100];
            if (isset($statusSteps[$order->status])) {
                $progressPercentage = $statusSteps[$order->status];
            }
            
            // Charger la livraison avec le livreur
            $order->load(['delivery.driver']);
            $delivery = $order->delivery;
            
            // Récupérer la position GPS du livreur (dernière position connue)
            $driverLocation = null;
            if ($delivery && $delivery->driver) {
                // D'abord essayer depuis driver_locations (historique)
                $latestLocation = \App\DriverLocation::where('driver_id', $delivery->driver->id)
                    ->orderBy('timestamp', 'desc')
                    ->first();
                
                if ($latestLocation) {
                    $driverLocation = [
                        'latitude' => (float) $latestLocation->latitude,
                        'longitude' => (float) $latestLocation->longitude,
                        'accuracy' => $latestLocation->accuracy ? (float) $latestLocation->accuracy : null,
                        'heading' => $latestLocation->heading ? (float) $latestLocation->heading : null,
                        'speed' => $latestLocation->speed ? (float) $latestLocation->speed : null,
                        'timestamp' => $latestLocation->timestamp->toIso8601String(),
                    ];
                } elseif ($delivery->driver->latitude && $delivery->driver->longitude) {
                    // Fallback sur la position dans la table drivers
                    $driverLocation = [
                        'latitude' => (float) $delivery->driver->latitude,
                        'longitude' => (float) $delivery->driver->longitude,
                        'accuracy' => null,
                        'heading' => null,
                        'speed' => null,
                        'timestamp' => $delivery->driver->updated_at->toIso8601String(),
                    ];
                }
            }
            
            // Coordonnées du restaurant
            $restaurantLocation = null;
            if ($order->restaurant && $order->restaurant->latitude && $order->restaurant->longitude) {
                $restaurantLocation = [
                    'latitude' => (float) $order->restaurant->latitude,
                    'longitude' => (float) $order->restaurant->longitude,
                ];
            }
            
            // Coordonnées de livraison (client)
            $deliveryLocation = null;
            if ($order->d_lat && $order->d_lng) {
                $deliveryLocation = [
                    'latitude' => (float) $order->d_lat,
                    'longitude' => (float) $order->d_lng,
                ];
            } elseif ($order->latitude && $order->longitude) {
                $deliveryLocation = [
                    'latitude' => (float) $order->latitude,
                    'longitude' => (float) $order->longitude,
                ];
            }
            
            return response()->json([
                'status' => true,
                'order' => [
                    'order_no' => $order->order_no,
                    'status' => $order->status,
                    'progress' => $progressPercentage,
                    'created_at' => $order->created_at->toDateTimeString(),
                    'estimated_time' => $estimatedTime,
                    'remaining_minutes' => $remainingMinutes,
                    'restaurant' => [
                        'id' => $order->restaurant->id ?? null,
                        'name' => $order->restaurant->name ?? null,
                        'address' => $order->restaurant->address ?? null,
                        'location' => $restaurantLocation, // Position GPS
                    ],
                    'driver' => ($delivery && $delivery->driver) ? [
                        'id' => $delivery->driver->id,
                        'name' => $delivery->driver->name,
                        'phone' => $delivery->driver->phone,
                        'vehicle' => $delivery->driver->vehicle ?? null,
                        'location' => $driverLocation, // Position GPS en temps réel avec métadonnées
                    ] : null,
                    'delivery_address' => $order->delivery_address,
                    'delivery_location' => $deliveryLocation, // Position GPS de livraison
                    'total' => $order->total,
                ],
                'items' => $orderItems->map(function($item) {
                    return [
                        'product_name' => $item->product->name ?? 'Produit supprimé',
                        'qty' => $item->qty,
                        'price' => $item->price,
                    ];
                }),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Erreur lors de la récupération du statut',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function myShipments()
    {
        if (!auth()->check()) {
            return redirect()->route('user.login');
        }

        $shipments = \App\Domain\Colis\Models\Shipment::where('customer_id', auth()->id())
            ->latest()
            ->get();

        return view('frontend.colis.index', compact('shipments'));
    }

    public function createShipment()
    {
        if (!auth()->check()) {
            return redirect()->route('user.login');
        }

        return view('frontend.colis.create');
    }

    public function storeShipment(Request $request)
    {
        return (new \App\Http\Controllers\Api\V1\Colis\ShipmentController())->store($request);
    }

    public function showShipment($id)
    {
        if (!auth()->check()) {
            return redirect()->route('user.login');
        }

        $shipment = \App\Domain\Colis\Models\Shipment::where('customer_id', auth()->id())
            ->with(['addresses', 'events'])
            ->findOrFail($id);

        return view('frontend.colis.show', compact('shipment'));
    }

    /**
     * Page vitrine du service Colis
     */
    public function colisLanding()
    {
        return view('frontend.colis.landing');
    }

    /**
     * Suivi public d'un colis
     */
    public function trackShipmentPublic(Request $request)
    {
        $trackingNumber = $request->get('tracking_number');
        $shipment = null;
        
        if ($trackingNumber) {
            $shipment = \App\Domain\Colis\Models\Shipment::where('tracking_number', $trackingNumber)
                ->with(['events' => function($query) {
                    $query->latest();
                }])
                ->first();
        }
        
        return view('frontend.colis.track_public', compact('shipment', 'trackingNumber'));
    }
 
}
