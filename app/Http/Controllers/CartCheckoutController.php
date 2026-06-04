<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RemembersFrontendBrand;
use App\Cart;
use App\Order;
use App\CompletedOrder;
use App\Product;
use App\Restaurant;
use App\User;
use App\Address;
use App\Voucher;
use App\UserToken;
use App\Domain\Food\Services\OrderPricingService;
use App\Domain\Food\Services\PlaceOrderService;
use App\Services\CartGroupService;
use App\Services\CommerceSignalService;
use App\Services\ConfigService;
use App\Services\FinancialLedgerService;
use App\Services\LoyaltyService;
use App\Services\NotificationService;
use App\Services\PaymentExperienceService;
use App\Services\PromotionService;
use App\Services\RiskService;
use App\Services\SubstitutionService;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Cart\AddToCartRequest;
use App\Mail\RegisterEmail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class CartCheckoutController extends Controller
{
    use RemembersFrontendBrand;

    public function cartDetail()
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
        if(auth()->check()){
            // Vérification propriété : seul le propriétaire peut supprimer son article
            $delete_item = Cart::where('id', $id)
                ->where('user_id', auth()->id())
                ->firstOrFail();
            $delete_item->delete();
        } else {
            $cart = session()->get('cart', []);
            if(isset($cart[$id])){
                unset($cart[$id]);
                session()->put('cart', $cart);
            }
        }
        return back()->with('message','Supprime avec succes !');
    }

    public function updateItem(Request $request, $cart){
        if(auth()->check()){
            // Vérification propriété : seul le propriétaire peut modifier son article
            $cartItem = Cart::where('id', $cart)
                ->where('user_id', auth()->id())
                ->first();
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

        return back()->with('message','Quantité mise à jour');
    }

     public function register(Request $request)
    {
        $request->validate([
                'name'=>'nullable',
                'email' => 'required|email|max:255|unique:users',
                'password' => 'required',
                'phone'=>'required|unique:users',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:8192',
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
                return redirect()->intended('/')->with('alert', [
                    'type' => 'success',
                    'message' => 'Inscription réussie ! Bienvenue sur ' . ConfigService::getCompanyName() . '.'
                ]);
            }

   public function Checkout(){
        // Connexion requise pour le paiement
        if(!auth()->check()){
            session()->put('url.intended', route('checkout.detail'));
            return redirect()->route('user.login')->with('message', 'Veuillez vous connecter pour finaliser votre commande');
        }

        // Téléphone et adresse requis pour commander (profil social incomplet)
        $authUser = auth()->user();
        if (empty($authUser->phone)) {
            return redirect()->route('user.profile')->with('alert', [
                'type'    => 'warning',
                'message' => 'Veuillez renseigner votre numéro de téléphone avant de commander.',
            ]);
        }

        $id = auth()->user()->id;

        // Migrer le panier de session vers la base de donnees
        $this->migrateSessionCartToDb();

        $checkoutData = DB::table('carts')
            ->join('products', 'products.id', '=', 'carts.product_id')
            ->leftJoin('restaurants', 'restaurants.id', '=', 'carts.restaurant_id')
            ->select(
                'carts.*',
                'products.image',
                'products.name',
                'products.description',
                'products.price as product_price',
                'products.discount_price',
                'products.is_available',
                'products.size',
                'products.featured',
                'restaurants.name as restaurant_name',
                'restaurants.logo as restaurant_logo'
            )
            ->where('carts.user_id', $id)
            ->get();

        if ($checkoutData->isEmpty()) {
            return redirect()->route('cart.detail')->with('message', 'Votre panier est vide');
        }

        $name = $checkoutData->pluck('name')->toArray();
        $qty = $checkoutData->pluck('qty')->toArray();
        $pricing = $this->orderPricingService()->calculate($checkoutData, [
            'fulfillment_mode' => 'delivery',
        ]);
        $total = (float) ($pricing['sub_total'] ?? 0);
        $resturant = Cart::where('user_id', $id)->first();
        $restaurantModel = $resturant ? Restaurant::find($resturant->restaurant_id) : null;
        $charges = $this->orderPricingService()->chargeProfileForCart($checkoutData);
        $cartGroups = app(CartGroupService::class)->groupByRestaurant($checkoutData, [
            'delivery_fee' => $charges->delivery_fee ?? ConfigService::getDefaultDeliveryFee(),
            'pickup_fee' => $charges->pickup_fee ?? 0,
            'tax_rate' => $charges->tax ?? 0,
            'service_fee_rate' => $charges->service_fee ?? 0,
            'is_pickup' => false,
        ]);
        $hasMultipleRestaurants = $cartGroups->count() > 1;

        $savedAddresses = collect();
        $address = null;
        if (\Illuminate\Support\Facades\Schema::hasTable('user_address')) {
            $savedAddresses = Address::where('user_id', $id)
                ->orderByDesc('is_default')
                ->orderByDesc('id')
                ->get();
            $address = $savedAddresses->first();
        }

        $tax = (float) ($pricing['tax'] ?? 0);
        $service_fee = (float) ($pricing['service_fee'] ?? 0);
        $deliveryFee = (float) ($pricing['delivery_fee'] ?? 0);
        $loyaltyPoints = LoyaltyService::getBalance($id);
        $loyaltyDiscount = 0;
        $stockIssues = app(SubstitutionService::class)->suggestForCart($checkoutData, 4);
        if ($stockIssues->isNotEmpty()) {
            app(CommerceSignalService::class)->emit('catalog.checkout_out_of_stock', [
                'module' => 'food',
                'severity' => 'warning',
                'user_id' => $id,
                'restaurant_id' => optional($resturant)->restaurant_id,
                'payload' => [
                    'items' => $stockIssues->toArray(),
                ],
            ]);
        }

        if ($loyaltyPoints > 0) {
            $loyaltyDiscount = $this->resolveLoyaltyRedemption($id, $total + $deliveryFee + $tax + $service_fee)['discount'];
        }

        $grandTotal = (float) ($pricing['total'] ?? 0) - $loyaltyDiscount;

        // T1.5 — Surcharge saison des pluies
        $weatherSurcharge       = (float) ($pricing['weather_surcharge'] ?? 0);
        $weatherSurchargeActive = (bool)  ($pricing['weather_surcharge_active'] ?? false);
        $weatherSurchargeLabel  = $weatherSurchargeActive
            ? \App\Services\ConfigService::getConfigValue('weather_surcharge_label', 'Majoration saison des pluies', 'string')
            : null;

        return view('frontend.checkout', compact('checkoutData', 'cartGroups', 'hasMultipleRestaurants', 'total', 'charges', 'address', 'savedAddresses', 'name', 'qty', 'resturant', 'restaurantModel', 'tax', 'service_fee', 'loyaltyPoints', 'loyaltyDiscount', 'grandTotal', 'stockIssues', 'weatherSurcharge', 'weatherSurchargeActive', 'weatherSurchargeLabel'));
   }

    public function addToCart(AddToCartRequest $request){
        // Whitelist stricte — aucun champ libre de l'extérieur
        $validated = $request->validate([
            'product_id'    => 'required|integer|min:1',
            'qty'           => 'required|integer|min:1|max:99',
            'restaurant_id' => 'nullable|integer|min:1',
            'instructions'  => 'nullable|string|max:500',
            'txtarea1'      => 'nullable|string|max:500',
        ]);

        // Récupérer le produit pour obtenir le prix
        $product = Product::find($validated['product_id']);
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
            $suggestions = app(SubstitutionService::class)->suggestForProduct($product, 4);
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'suggestions' => $suggestions,
                ], 422);
            }
            return back()->with('message', $message)->with('suggestions', $suggestions);
        }

        // Définir le prix (prix remisé si disponible, sinon prix normal)
        $price = $product->discount_price > 0 ? $product->discount_price : $product->price;

        // Si l'utilisateur est connecté, utiliser la base de données
        if(auth()->check()){
            // restaurant_id : toujours depuis le produit (source de confiance)
            // Le champ client est ignoré pour éviter la manipulation de restaurant_id
            $cartData = [
                'restaurant_id' => $product->restaurant_id,
                'user_id'       => auth()->id(),
                'product_id'    => $product->id,
                'qty'           => $validated['qty'],
                'price'         => $price,
                'sub_total'     => $price * $validated['qty'],
                'description'   => $validated['txtarea1'] ?? $validated['instructions'] ?? null,
            ];

            $existingCart = Cart::where('product_id', $cartData['product_id'])
                                ->where('user_id', $cartData['user_id'])->first();

            if($existingCart){
                $existingCart->increment('qty', $cartData['qty']);
                $existingCart->sub_total = $existingCart->qty * $price;
                if (!empty($cartData['description'])) {
                    $existingCart->description = $cartData['description'];
                }
                $existingCart->save();
                $message = 'Quantité mise à jour !';
                $totalItems = Cart::where('user_id', auth()->id())->sum('qty');
            }
            else{
                Cart::create($cartData);
                $message = 'Produit ajouté au panier';
                $totalItems = Cart::where('user_id', auth()->id())->sum('qty');
            }
        }
        // Sinon, utiliser la session pour les invités
        else {
            $cart       = session()->get('cart', []);
            $productId  = $validated['product_id'];
            $qty        = $validated['qty'];
            $restaurantId = $product->restaurant_id; // source de confiance

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

        /**
         * Étape 1 : demander un OTP de réinitialisation (envoyé par email).
         * Étape 2 : soumettre OTP + nouveau mot de passe.
         *
         * Le formulaire doit envoyer :
         *   - step=request  → email requis → envoie OTP
         *   - step=reset    → email + otp + password requis → vérifie OTP et change
         */
        public function forgotPassword(Request $request)
        {
            $step = $request->input('step', 'request');

            if ($step === 'request') {
                $request->validate(['email' => 'required|email|max:255']);

                $user = User::where('email', $request->email)->first();

                // Réponse identique qu'il existe ou non — anti-énumération
                if ($user) {
                    $otp     = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                    $cacheKey = 'pwd_reset_' . sha1($request->email);
                    Cache::put($cacheKey, $otp, 600); // 10 minutes

                    try {
                        \Illuminate\Support\Facades\Mail::send([], [], function ($m) use ($user, $otp) {
                            $m->to($user->email, $user->name ?? 'Client')
                              ->subject('Réinitialisation de votre mot de passe BantuDelice')
                              ->html(
                                "<div style='font-family:sans-serif;max-width:480px;margin:0 auto;padding:24px;'>"
                                . "<img src='" . url('frontend/images/BuntuDelice.png') . "' alt='BantuDelice' style='height:36px;margin-bottom:16px;'>"
                                . "<h2 style='color:#0f172a;'>Réinitialisation du mot de passe</h2>"
                                . "<p style='color:#475569;'>Votre code de vérification est :</p>"
                                . "<div style='font-size:2.2rem;font-weight:900;letter-spacing:.3em;color:#009543;background:#f0fdf4;border-radius:12px;padding:16px 24px;text-align:center;margin:20px 0;'>{$otp}</div>"
                                . "<p style='color:#94a3b8;font-size:13px;'>Ce code expire dans <strong>10 minutes</strong>. Si vous n'avez pas fait cette demande, ignorez cet email.</p>"
                                . "</div>"
                              );
                        });
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning('Erreur envoi OTP reset password', ['error' => $e->getMessage()]);
                    }
                }

                return redirect()->back()->with('alert', [
                    'type'    => 'success',
                    'message' => 'Si ce compte existe, un code de vérification a été envoyé à votre adresse email.',
                ])->with('reset_email', $request->email)->with('reset_step', 'verify');
            }

            // step = reset : vérifier OTP et changer le mot de passe
            $request->validate([
                'email'    => 'required|email|max:255',
                'otp'      => 'required|digits:6',
                'password' => 'required|min:8|confirmed',
            ]);

            $cacheKey   = 'pwd_reset_' . sha1($request->email);
            $storedOtp  = Cache::get($cacheKey);
            $user       = User::where('email', $request->email)->first();

            if (!$storedOtp || !$user || !hash_equals((string) $storedOtp, (string) $request->otp)) {
                return redirect()->back()->withErrors(['otp' => 'Code invalide ou expiré.'])->withInput();
            }

            $user->password = bcrypt($request->password);
            $user->save();
            Cache::forget($cacheKey);

            return redirect()->route('user.login')->with('alert', [
                'type'    => 'success',
                'message' => 'Mot de passe mis à jour. Vous pouvez vous connecter.',
            ]);
        }

     public function checkVoucher(Request $request){
         $request->validate([
             'voucher'    => 'required|string|max:100',
             'restaurant' => 'nullable|integer|min:1',
         ]);

         $restaurant = $request->filled('restaurant') ? Restaurant::find($request->input('restaurant')) : null;
         $user = auth()->check() ? auth()->user() : null;
         $preview = app(PromotionService::class)->preview((string) $request->input('voucher'), $restaurant, $user, 0);

         if(!empty($preview['valid'])){
             return response()->json([
                 'status' => true,
                 'data' => $preview['voucher'],
                 'rules' => $preview['rules'] ?? [],
                 'remaining_usage' => $preview['remaining_usage'] ?? null,
                 'remaining_user_usage' => $preview['remaining_user_usage'] ?? null,
                 'message' => $preview['message'] ?? 'Code promo valide !'
             ]);
         }
         else{
             return response()->json([
                 'status' => false,
                 'data' => null,
                 'rules' => $preview['rules'] ?? [],
                 'message' => 'Code promo invalide ou expiré'
             ]);
         }
     }

    public function thanks(Request $request)
    {
        $orderNo = $request->query('orderID');

        if (empty($orderNo)) {
            return redirect('/')->with('message', 'Commande introuvable');
        }

        $order = Order::where('order_no', $orderNo)->first();

        if (! $order) {
            abort(404);
        }

        // Vider uniquement le panier de l'utilisateur authentifié propriétaire de la commande.
        // Évite qu'une URL thanks?orderID=X supprime le panier d'un autre utilisateur.
        if (auth()->check() && auth()->id() === (int) $order->user_id) {
            Cart::where('user_id', auth()->id())->delete();
        }

        // Stocker order_no en session pour permettre le polling API de statut aux invités
        // sans exposer l'endpoint à une énumération ouverte.
        if (! $request->session()->has('order_no') || $request->session()->get('order_no') !== $order->order_no) {
            $request->session()->put('order_no', $order->order_no);
        }

        return view('frontend.thanks', compact('order'));
    }

    public function orderReceipt(Request $request, $orderNo)
    {
        $completedOrderId = null;
        if (strpos($orderNo, 'completed-') === 0) {
            $completedOrderId = (int) substr($orderNo, strlen('completed-'));
        }

        $order = Order::with(['restaurant', 'driver', 'delivery.driver', 'user'])
            ->where('order_no', $orderNo)
            ->first();
        $items = collect();
        $delivery = null;

        if (! $order) {
            $order = CompletedOrder::with(['restaurant', 'driver', 'user', 'product'])
                ->when($completedOrderId, function ($query) use ($completedOrderId) {
                    $query->where('id', $completedOrderId);
                }, function ($query) use ($orderNo) {
                    $query->where('order_no', $orderNo);
                })
                ->firstOrFail();
            $items = collect([$order]);
        } else {
            $items = Order::with('product')
                ->where('order_no', $orderNo)
                ->get();
            $delivery = $order->delivery;
        }

        // Reçu accessible uniquement au propriétaire ou aux admins.
        // Un visiteur non-authentifié ne peut pas accéder au reçu d'une commande.
        if (! auth()->check()) {
            return redirect()->route('user.login');
        }
        if ((int) $order->user_id !== (int) auth()->id() && auth()->user()->type !== 'admin') {
            abort(403, 'Accès non autorisé');
        }

        $paymentExperience = $order instanceof \App\Order
            ? app(\App\Services\PaymentExperienceService::class)->describe($order->payment()->latest('id')->first())
            : null;

        return view('frontend.order_receipt', [
            'order' => $order,
            'items' => $items,
            'delivery' => $delivery,
            'paymentExperience' => $paymentExperience,
        ]);
    }

    private function orderPricingService(): OrderPricingService
    {
        return app(OrderPricingService::class);
    }

    private function resolveScheduledAt(?string $scheduledDate): ?Carbon
    {
        if (blank($scheduledDate)) {
            return null;
        }

        try {
            $candidate = Carbon::parse($scheduledDate);

            return $candidate->isFuture() ? $candidate : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function resolveLoyaltyRedemption(int $userId, float $baseAmount, bool $consume = false): array
    {
        $loyaltyPoints = LoyaltyService::getBalance($userId);
        if ($loyaltyPoints <= 0 || $baseAmount <= 0) {
            return [
                'discount' => 0.0,
                'points_used' => 0,
            ];
        }

        $discount = min(LoyaltyService::calculateDiscount($loyaltyPoints), $baseAmount * 0.2);
        $pointsUsed = (int) floor(($discount / 1000) * 100);

        if ($pointsUsed > $loyaltyPoints) {
            $pointsUsed = $loyaltyPoints;
            $discount = LoyaltyService::calculateDiscount($loyaltyPoints);
        }

        if ($consume && ($pointsUsed < 1 || ! LoyaltyService::usePoints($userId, $pointsUsed, null))) {
            return [
                'discount' => 0.0,
                'points_used' => 0,
            ];
        }

        return [
            'discount' => (float) $discount,
            'points_used' => $pointsUsed,
        ];
    }
}
