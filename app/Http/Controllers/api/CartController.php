<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Cart;
use App\Charge;
use App\Product;
use App\Restaurant;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\URL;

class CartController extends Controller
{
    function getDistance($latitude2, $longitude2, $latitude1, $longitude1)
    {
        $earth_radius = 6356 ;

        $dLat = deg2rad($latitude2 - $latitude1);
        $dLon = deg2rad($longitude2 - $longitude1);

        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($latitude1)) * cos(deg2rad($latitude2)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * asin(sqrt($a));
        $d = $earth_radius * $c;

        return $d;
    }
    public function addToCart(Request $request)
    {
         $validator = Validator::make(
            $request->all(),
            array(
                'restaurant_id'=>'required',
                'user_id' => 'required',
                'product_id' => 'required',
                'qty'=>'required',
                'price' => 'required'
            ));
        if ($validator->fails()) {
            $error_messages = implode(',', $validator->messages()->all());
            return response()->json([
                'status' => false,
                'error_code' => 101,
                'message' => $error_messages,
            ], 422);
        }
        elseif (!User::whereKey($request->user_id)->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'Utilisateur introuvable',
            ], 404);
        }
        elseif (!Restaurant::whereKey($request->restaurant_id)->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'Restaurant introuvable',
            ], 404);
        }
        elseif (!Product::whereKey($request->product_id)->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'Produit introuvable',
            ], 404);
        }
         else{
            $checkUser=Cart::where('user_id',$request->user_id)->first();
            $cart = new Cart;
            
            if($checkUser==NULL){
                $cart->restaurant_id = $request->restaurant_id;
                $cart->user_id = $request->user_id;
                $cart->product_id = $request->product_id;
                $cart->qty = $request->qty;
                $cart->description = $request->instructions;
                $cart->price = $request->price;
                $cart->sub_total = $request->price * $request->qty;
        
                $cart->save();

                return response()->json([
                    'status' => true,
                    'message' => 'Ajouté avec succès !'
                    ]);
            }
            elseif($checkUser && $checkUser->restaurant_id==$request->restaurant_id){
                $cart->restaurant_id = $request->restaurant_id;
                $cart->user_id = $request->user_id;
                $cart->product_id = $request->product_id;
                $cart->qty = $request->qty;
                $cart->description = $request->instructions;
                $cart->price = $request->price;
                $cart->sub_total = $request->price * $request->qty;
        
                $cart->save();

                return response()->json([
                    'status' => true,
                    'message' => 'Ajouté avec succès !'
                    ]);
            }
            else{

                if($checkUser->restaurant_id!=$request->restaurant_id){
                     return response()->json([
                         'status' => false,
                         'message' => 'Do you want to delete previous record from cart!',
                     ]);
                }

            }
        }
    }

    public function showCartDetail($user)
        {
        // IDOR guard: if a Passport token is present, the token owner must match {user}
        $tokenUser = auth('api')->user();
        if (!$tokenUser || (int)$tokenUser->id !== (int)$user) {
            return response()->json(['status' => false, 'message' => 'Accès non autorisé'], 403);
        }

        if (!User::whereKey($user)->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'Utilisateur introuvable',
                'data' => [],
                'charges' => null,
            ], 404);
        }

        //get cart where user_id = 12
        $cartDetail=Cart::where('user_id',$user)->get();

        

        $cartRestIDs=$cartDetail->pluck('restaurant_id')->toArray();
        $cartProIDs=$cartDetail->pluck('product_id')->toArray();
        $cartUserIDs=$cartDetail->pluck('user_id')->toArray();

     
     $instructionColumn = Schema::hasColumn('carts', 'instructions') ? 'carts.instructions' : (Schema::hasColumn('carts', 'description') ? 'carts.description' : null);
     $productNameColumn = Schema::hasColumn('carts', 'product_name') ? 'carts.product_name' : null;

     $selectColumns = ['carts.id','carts.qty','carts.product_id','carts.user_id','carts.restaurant_id','carts.price','products.image'];
     if ($instructionColumn) {
        $selectColumns[] = DB::raw($instructionColumn . ' as instructions');
     }
     if ($productNameColumn) {
        $selectColumns[] = DB::raw($productNameColumn . ' as product_name');
     } else {
        $selectColumns[] = DB::raw('NULL as product_name');
     }

     $restDetail=DB::table('carts')
     ->join('restaurants', 'restaurants.id', '=', 'carts.restaurant_id')
     ->join('products', 'products.id', '=', 'carts.product_id')
     ->select($selectColumns)
     ->where('carts.user_id',$user)
     ->get();
      $charges=Charge::first();

            $restDetail = $restDetail->map(function ($item) {
                return [
                    'id' => $item->id,
                    'qty' => $item->qty,
                    'product_id' => $item->product_id,
                    'restaurant_id' => $item->restaurant_id,
                    'price' => $item->price,
                    'instructions' => $item->instructions,
                    'product_name' => $item->product_name,
                    'image' => $item->image,
                    'image_url' => !empty($item->image) ? URL::to('/') . '/images/product_images/' . $item->image : null,
                ];
            })->values();

            $chargesPayload = $charges ? [
                'delivery_fee' => $charges->delivery_fee ?? $charges->delivery_charges ?? null,
                'tax' => $charges->tax ?? null,
            ] : null;
   
            
            return response()->json([
                'status' => true,
                'data' => $restDetail,
                'charges' => $chargesPayload
            ]);
            
        }

    public function deletePreviousCart($user)
    {
        // IDOR guard: if a Passport token is present, the token owner must match {user}
        $tokenUser = auth('api')->user();
        if (!$tokenUser || (int)$tokenUser->id !== (int)$user) {
            return response()->json(['status' => false, 'message' => 'Accès non autorisé'], 403);
        }

       if (!User::whereKey($user)->exists()) {
           return response()->json([
               'status' => false,
               'message' => 'Utilisateur introuvable',
           ], 404);
       }

       $getResData=Cart::where('user_id',$user)->delete();
       return response()->json([
           'status' =>true,
           'message' =>'Panier supprimé avec succès !'
           ]);
    }
    
    public function deleteCartProduct($cart)
    {
        $getProduct=Cart::find($cart);

        if (!$getProduct) {
            return response()->json([
               'status' => false,
               'message' => 'Produit du panier introuvable'
            ], 404);
        }

        // IDOR guard: if a Passport token is present, the token owner must own this cart item
        $tokenUser = auth('api')->user();
        if (!$tokenUser || (int)$tokenUser->id !== (int)$getProduct->user_id) {
            return response()->json(['status' => false, 'message' => 'Accès non autorisé'], 403);
        }

        $getProduct->delete();
        return response()->json([
           'status' =>true,
           'message' => 'Données supprimées avec succès'
           ]);

    }
    
     public function UpdateCartDetail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cart_id' => 'required',
            'qty' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'error_code' => 101,
                'message' => implode(',', $validator->messages()->all()),
            ], 422);
        }

        $cart=$request->cart_id;
        $getQty=$request->qty;
        $getCart=Cart::find($cart);

        if (!$getCart) {
            return response()->json([
                'status' => false,
                'message' => 'Produit du panier introuvable',
            ], 404);
        }

        // IDOR guard: if a Passport token is present, the token owner must own this cart item
        $tokenUser = auth('api')->user();
        if (!$tokenUser || (int)$tokenUser->id !== (int)$getCart->user_id) {
            return response()->json(['status' => false, 'message' => 'Accès non autorisé'], 403);
        }

        ///multipel price*qty
        
if($getQty>0){
$getCart->qty= $getQty;
        $getCart->save();
      return response()->json([
'status' =>true,
'message' => 'Mis à jour avec succès !'

]);   
}
elseif($getQty<=0){
             $getCart->delete();

             return response()->json([
'status' =>true,
'message' => 'Supprimé avec succès !'

]);

        }

    }
}
