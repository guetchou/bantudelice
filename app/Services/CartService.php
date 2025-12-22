<?php

namespace App\Services;

use App\Cart;
use App\Product;
use Illuminate\Support\Facades\Log;

class CartService
{
    /**
     * Ajouter un produit au panier
     * 
     * @param array $cartData
     * @return array
     */
    public static function addToCart($cartData)
    {
        if (empty($cartData['qty']) || $cartData['qty'] == "") {
            return ['success' => false, 'message' => 'Veuillez sélectionner une quantité'];
        }
        
        $product = Product::find($cartData['product_id']);
        if (!$product) {
            return ['success' => false, 'message' => 'Produit non trouvé'];
        }
        
        $price = $product->discount_price > 0 ? $product->discount_price : $product->price;
        
        // Si l'utilisateur est connecté, utiliser la base de données
        if (auth()->check()) {
            $userId = auth()->user()->id;
            $cartData['user_id'] = $userId;
            $cartData['price'] = $price;
            $cartData['sub_total'] = $price * $cartData['qty'];
            
            $existingCart = Cart::where('product_id', $cartData['product_id'])
                                ->where('user_id', $userId)
                                ->first();
            
            if ($existingCart) {
                $existingCart->increment('qty', $cartData['qty']);
                $existingCart->sub_total = $existingCart->qty * $price;
                $existingCart->save();
                return ['success' => true, 'message' => 'Quantité mise à jour !'];
            } else {
                Cart::create($cartData);
                return ['success' => true, 'message' => 'Produit ajouté au panier'];
            }
        }
        // Sinon, utiliser la session pour les invités
        else {
            $cart = session()->get('cart', []);
            $productId = $cartData['product_id'];
            $qty = (int)$cartData['qty'];
            $restaurantId = $cartData['restaurant_id'] ?? null;
            
            if (isset($cart[$productId])) {
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
            return ['success' => true, 'message' => 'Produit ajouté au panier'];
        }
    }
    
    /**
     * Migrer le panier de session vers la base de données
     * 
     * @param int $userId
     * @return void
     */
    public static function migrateSessionCartToDb($userId)
    {
        $cart = session()->get('cart', []);
        if (empty($cart)) {
            return;
        }
        
        foreach ($cart as $productId => $item) {
            $existing = Cart::where('product_id', $productId)
                           ->where('user_id', $userId)
                           ->first();
            
            $product = Product::find($productId);
            if (!$product) {
                continue;
            }
            
            if ($existing) {
                $existing->increment('qty', $item['qty']);
                $existing->sub_total = $existing->qty * $product->price;
                $existing->save();
            } else {
                Cart::create([
                    'product_id' => $productId,
                    'user_id' => $userId,
                    'restaurant_id' => $item['restaurant_id'],
                    'qty' => $item['qty'],
                    'price' => $product->price,
                    'sub_total' => $product->price * $item['qty']
                ]);
            }
        }
        
        // Vider le panier de session
        session()->forget('cart');
        
        Log::info('Cart migrated from session to database', ['user_id' => $userId]);
    }
    
    /**
     * Obtenir le nombre d'articles dans le panier
     * 
     * @param int|null $userId
     * @return int
     */
    public static function getCartCount($userId = null)
    {
        if ($userId || auth()->check()) {
            $userId = $userId ?? auth()->user()->id;
            return Cart::where('user_id', $userId)->sum('qty');
        } else {
            $cart = session()->get('cart', []);
            return array_sum(array_column($cart, 'qty'));
        }
    }
    
    /**
     * Supprimer un article du panier
     * 
     * @param int $itemId
     * @param int|null $userId
     * @return bool
     */
    public static function removeFromCart($itemId, $userId = null)
    {
        if ($userId || auth()->check()) {
            $userId = $userId ?? auth()->user()->id;
            $cartItem = Cart::where('id', $itemId)
                           ->where('user_id', $userId)
                           ->first();
            
            if ($cartItem) {
                $cartItem->delete();
                return true;
            }
        } else {
            $cart = session()->get('cart', []);
            if (isset($cart[$itemId])) {
                unset($cart[$itemId]);
                session()->put('cart', $cart);
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Mettre à jour la quantité d'un article
     * 
     * @param int $itemId
     * @param int $qty
     * @param int|null $userId
     * @return bool
     */
    public static function updateQuantity($itemId, $qty, $userId = null)
    {
        if ($userId || auth()->check()) {
            $userId = $userId ?? auth()->user()->id;
            $cartItem = Cart::where('id', $itemId)
                           ->where('user_id', $userId)
                           ->first();
            
            if ($cartItem) {
                $cartItem->qty = $qty;
                $cartItem->sub_total = $cartItem->price * $qty;
                $cartItem->save();
                return true;
            }
        } else {
            $cart = session()->get('cart', []);
            if (isset($cart[$itemId])) {
                $cart[$itemId]['qty'] = $qty;
                session()->put('cart', $cart);
                return true;
            }
        }
        
        return false;
    }
}

