<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\User;
use App\Address;
use App\Order;
use App\Restaurant;
use App\Product;
use App\Rating;
use App\Review;
use App\CompletedOrder;
use App\UserToken;
use Illuminate\Support\Facades\Mail;
use App\Mail\RegisterEmail;
use App\Http\Requests\User\UpdateProfileRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Laravel\Passport\Token;

if (!defined('BASE_URL_PROFILE')) define('BASE_URL_PROFILE',URL::to('/').'/images/profile_images/');

class UserController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            array(
                'name'=>'nullable',
                'email' => 'required|email|max:255|unique:users',
                'password' => 'required',
                'phone'=>'required|unique:users',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:8192',
                'type' => 'nullable|in:user,admin,restaurant,driver',
            ));

        if ($validator->fails()) {
            $error_messages = implode(',', $validator->messages()->all());
            return response()->json([
                'status' => false,
                'error_code' => 101,
                'message' => $error_messages,
            ], 422);
        } else {
            $request['password'] = bcrypt($request->password);
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
                    DB::commit();
                    $data = array(
            			'name' => $user->name,
            			'email' => $user->email,
            		);
                   //sending email
                   Mail::to($request->email)->send(new RegisterEmail($data));
            
                } catch (\Exception $exception) {
                    DB::rollBack();
                    return response()->json([
                        'message' => $exception->getMessage()
                    ], 403);
                }
                $response_array = array('status' => true, 'user_id' =>$user->id ,'status_code' => 200);
            }
        return response()->json($response_array, 200);
    }
    public function login(Request $request)
    {
        $validator = Validator::make(
            $request->all(),[
            'phone' => 'required',
            'password' => 'required',
        ]);
        if ($validator->fails()) {
            $error_messages = implode(',', $validator->messages()->all());
            return response()->json([
                'status' => false,
                'error_code' => 101,
                'message' => $error_messages,
            ], 422);
        }
        else {
            $user = User::where(function ($query) use ($request) {
                $query->where('phone', $request->phone)->first();
            })->first();

            if (!$user)
                return response()->json([
                    'message' => 'incorrect number',
                    'status'=>false
                ], 403);

            if (!auth()->loginUsingId((password_verify($request->password, $user->password)) ? $user->id : 0))
                return response()->json([
                    'message' => 'incorrect password',
                    'status'=>false
                ], 403);
            $user = auth()->user();
            $request['user_id'] = $user->id;
            $request['email']=$user->email;
            $request['phone']=$user->phone;
            $data = 'Bearer' . ' ' . $user->createToken('MyApp')->accessToken;
            $response_array = array('user_id'=>$request->user_id,
               'email'=>$request->email, 'phone'=>$request->phone,
                'status' => true,'status_code'=>200,'message' => 'Connexion réussie', 'data'=>$data);
        }
        return response()->json($response_array, 200);
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make(
            $request->all(),[
            'phone' => 'required',
            'password' => 'required',
        ]);
        if ($validator->fails()) {
            $error_messages = implode(',', $validator->messages()->all());
            return response()->json([
                'status' => false,
                'error_code' => 101,
                'message' => $error_messages,
            ], 422);
        }
        else{


        $check_password=User::where(['phone'=>$request->phone])->first();
            $password=bcrypt($request->password);
            if($check_password)
            { 
            User::where('phone',$request->phone)->update(['password'=>$password]);
            return response()->json([
                'status' => true,
            ]);
        }
        else{
            return response()->json([
                'status' => false,
                'message' => 'Utilisateur introuvable',
            ], 404);
        }

        }
        $response = response()->json($response_array, 200);
        return $response;
    }
    public function profile($user)
    {
        $getUser=User::select('id','name','email','image','phone')->where('id',$user)->first();

        if (!$getUser) {
            return response()->json([
                'status' => false,
                'message' => 'Utilisateur introuvable',
                'data' => null,
                'BASE_URL_PROFILE' => BASE_URL_PROFILE,
            ], 404);
        }

        $getUser->image_url = !empty($getUser->image)
            ? (filter_var($getUser->image, FILTER_VALIDATE_URL) ? $getUser->image : URL::to('/') . '/images/profile_images/' . $getUser->image)
            : null;

        return response()->json([
            'status' => true,
            'data' => $getUser,
            'BASE_URL_PROFILE'=>BASE_URL_PROFILE,
        ]);
    }
    public function updateProfile(UpdateProfileRequest $request)
    {
        // IDOR guard: if a Passport token is present, the token owner must match user_id
        $tokenUser = auth('api')->user();
        if (!$tokenUser || (int)$tokenUser->id !== (int)$request->user_id) {
            return response()->json(['status' => false, 'message' => 'Accès non autorisé'], 403);
        }

        $validator = Validator::make(
            $request->all(),
            array(
                'user_id'=>'required',
                'name'=>'nullable',
                'email' => 'required|email|max:255',
                'phone'=>'required',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:8192',
            ));
            $user=User::find($request->user_id);
        if ($validator->fails()) {
            $error_messages = implode(',', $validator->messages()->all());
            return response()->json([
                'status' => false,
                'error_code' => 101,
                'message' => $error_messages,
            ], 422);
        } elseif (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Utilisateur introuvable',
            ], 404);
        }
        else {
                  $user->name = $request->name;
                  $user->email = $request->email;
                  $user->phone = $request->phone;
                  //$user->image = $request->image;
                  $user->save();
                   
                    if($image = $request->image=='')
                    {
                        $image = $user->image;
                    }
                    else{
                        $image = $request->image;
                    }
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
                $response_array = array('status' => true, 'status_code' => 200);
            }
        $response = response()->json($response_array, 200);
        return $response;
    }
    
     public function addUserAddress(Request $request)
    {
        // IDOR guard
        $tokenUser = auth('api')->user();
        if (!$tokenUser || (int)$tokenUser->id !== (int)$request->user_id) {
            return response()->json(['status' => false, 'message' => 'Accès non autorisé'], 403);
        }

        $validator = Validator::make(
            $request->all(),
            array(
                'title'=>'required',
                'user_id'=>'required',
                'building_no'=>'required',
                'street_no' => 'required',
                'area'=>'required',
                'latitude'=>'required',
                'longitude'=>'required',
                'complete_address'=>'required',
                'floor' => 'nullable',
            ));
        if ($validator->fails()) {
            $error_messages = implode(',', $validator->messages()->all());
            return response()->json([
                'status' => false,
                'error_code' => 101,
                'message' => $error_messages,
            ], 422);
        }
        elseif (!User::where('id', $request->user_id)->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'Utilisateur introuvable',
            ], 404);
        }
        else{
        $address = new Address;
        $address->title = $request->title;
        $address->user_id = $request->user_id;
        $address->building_no = $request->building_no;
        $address->street_no = $request->street_no;
        $address->area = $request->area;
        $address->floor = $request->floor;
        $address->latitude = $request->latitude;
        $address->longitude = $request->longitude;
        $address->complete_address = $request->complete_address;
        $address->save();
        if($address){
           return response()->json([
             'status' => true,
           ]);
        }
        }
    }
     public function getUserAddress($user)
    {
        if (!User::where('id', $user)->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'Utilisateur introuvable',
                'address' => [],
            ], 404);
        }

        $getUserAddress=Address::where('user_id',$user)->get();
          return response()->json([
             'status' => true,
             'address' => $getUserAddress,
           ]);
    }
        
    public function trackOrders(Request $request)
        {
            $validator = Validator::make($request->all(), [
                'order_no' => 'required',
                'restaurant_id' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'error_code' => 101,
                    'message' => implode(',', $validator->messages()->all()),
                ], 422);
            }

            $orderNo=$request->order_no;
            $restaurantId=$request->restaurant_id;
            $getProDetail=Order::with('restaurant')->where([

        ['restaurant_id', '=', $restaurantId],['order_no', '=', $orderNo]

       ])
        ->select('user_id','restaurant_id','order_no','product_id','d_lat','d_lng','tax','offer_discount','delivery_charges','sub_total','total','ordered_time','status')->first();

            if (!$getProDetail) {
                return response()->json([
                    'status' => false,
                    'message' => 'Commande introuvable',
                    'data' => null,
                ], 404);
            }
        
        $getProIDs=Order::with('restaurant')->where([

        ['restaurant_id', '=', $restaurantId],['order_no', '=', $orderNo],['status', '!=', 'completed']

       ])
        ->select('product_id')->get();
            
            $cartProIDs=$getProIDs->pluck('product_id')->toArray();
            
            $getProDetail['products']=Product::whereIn('id',$cartProIDs)->get();
            return response()->json([
                'status' => true,
                'data' => $getProDetail
              ]);
        }
        
        public function trackCompletedOrders(Request $request)
        {
            $validator = Validator::make($request->all(), [
                'order_no' => 'required',
                'restaurant_id' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'error_code' => 101,
                    'message' => implode(',', $validator->messages()->all()),
                ], 422);
            }

            $orderNo=$request->order_no;
            $restaurantId=$request->restaurant_id;
            $getProDetail=CompletedOrder::with('restaurant')->where([

        ['restaurant_id', '=', $restaurantId],['order_no', '=', $orderNo]

       ])
        ->select('user_id','restaurant_id','order_no','product_id','tax','d_lat','d_lng','offer_discount','delivery_charges','sub_total','total','status','ordered_time','delivery_address','updated_at')->first();

            if (!$getProDetail) {
                return response()->json([
                    'status' => false,
                    'message' => 'Commande introuvable',
                    'data' => null,
                ], 404);
            }
        
        $getProIDs=CompletedOrder::with('restaurant')->where([

        ['restaurant_id', '=', $restaurantId],['order_no', '=', $orderNo]

       ])
        ->select('product_id')->get();
            
            $cartProIDs=$getProIDs->pluck('product_id')->toArray();
            
            $getProDetail['products']=Product::whereIn('id',$cartProIDs)->get();
            return response()->json([
                'status' => true,
                'data' => $getProDetail
              ]);
        }
    
    public function sendReviewsToRestaurant(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'restaurant_id'    => 'required|integer',
            'user_id'          => 'required|integer',
            'restaurant_rating'=> 'required|integer|min:1|max:5',
            'driver_id'        => 'nullable|integer',
            'driver_rating'    => 'nullable|integer|min:1|max:5',
            'restaurant_review'=> 'nullable|string|max:1000',
            'driver_review'    => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false, 'error_code' => 101,
                'message' => implode(',', $validator->messages()->all()),
            ], 422);
        }

        $userId       = (int) $request->user_id;
        $restaurantId = (int) $request->restaurant_id;

        // IDOR guard: if a Passport token is present, the token owner must match user_id
        $tokenUser = auth('api')->user();
        if (!$tokenUser || (int)$tokenUser->id !== $userId) {
            return response()->json(['status' => false, 'message' => 'Accès non autorisé'], 403);
        }

        // S6.6 — Avis vérifiés : l'utilisateur doit avoir une commande livrée dans ce restaurant
        $hasDeliveredOrder = \App\CompletedOrder::where('user_id', $userId)
            ->where('restaurant_id', $restaurantId)
            ->where('status', 'completed')
            ->exists();

        if (!$hasDeliveredOrder) {
            // Fallback : vérifier dans orders avec status delivered/completed
            $hasDeliveredOrder = Order::where('user_id', $userId)
                ->where('restaurant_id', $restaurantId)
                ->whereIn('status', ['completed', 'delivered'])
                ->exists();
        }

        if (!$hasDeliveredOrder) {
            return response()->json([
                'status'  => false,
                'message' => 'Vous ne pouvez noter un restaurant qu\'apres avoir recu une commande.',
            ], 403);
        }

        // Eviter les doublons : un avis par user+restaurant
        $existingRating = Rating::where('user_id', $userId)
            ->where('restaurant_id', $restaurantId)
            ->first();

        if ($existingRating) {
            $existingRating->update([
                'rating'  => $request->restaurant_rating,
                'reviews' => $request->restaurant_review,
            ]);
        } else {
            Rating::create([
                'restaurant_id' => $restaurantId,
                'user_id'       => $userId,
                'reviews'       => $request->restaurant_review,
                'rating'        => $request->restaurant_rating,
            ]);
        }

        // Avis livreur (optionnel)
        if ($request->filled('driver_id') && $request->filled('driver_rating')) {
            $existingReview = Review::where('user_id', $userId)
                ->where('driver_id', $request->driver_id)
                ->first();
            if ($existingReview) {
                $existingReview->update([
                    'rating'  => $request->driver_rating,
                    'reviews' => $request->driver_review,
                ]);
            } else {
                Review::create([
                    'driver_id' => $request->driver_id,
                    'user_id'   => $userId,
                    'reviews'   => $request->driver_review,
                    'rating'    => $request->driver_rating,
                ]);
            }
        }

        return response()->json(['status' => true, 'message' => 'Avis enregistre.']);
    }    
    
    public function userDeviceToken(Request $request)
    {
        $validator = Validator::make(
            $request->all(), [
            'device_token' => 'required',
            'user_id' => 'required',
        ]);
        
        if ($validator->fails()) {
            $error_messages = implode(',', $validator->messages()->all());
            return response()->json([
                'status' => false,
                'error_code' => 101,
                'message' => $error_messages,
            ], 422);
        }
        elseif (!User::where('id', $request->user_id)->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'Utilisateur introuvable',
            ], 404);
        }
        else{
        $driver =UserToken::updateOrCreate(
            ['user_id' => $request->user_id],
            ['device_tokens' => $request->device_token]
        );
 
             $response_array = array('status' => true, 'status_code' => 200);
            }
        $response = response()->json($response_array, 200);
        return $response;
    }

    /**
     * S5.2 — Logout mobile : révoque le token Passport courant.
     */
    public function logout(Request $request)
    {
        $user = auth('api')->user();
        if ($user) {
            // Révoquer le token courant
            $user->token()?->revoke();
        }
        return response()->json(['status' => true, 'message' => 'Déconnexion réussie.']);
    }

    /**
     * S5.4 — GET profil utilisateur authentifié (pas de {user} en URL).
     */
    public function me(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Non authentifié'], 401);
        }

        $user->image_url = !empty($user->image)
            ? (filter_var($user->image, FILTER_VALIDATE_URL) ? $user->image : URL::to('/') . '/images/profile_images/' . $user->image)
            : null;

        return response()->json([
            'status' => true,
            'data' => $user->only(['id', 'name', 'email', 'phone', 'image_url', 'address']),
        ]);
    }

    /**
     * S5.5 — PATCH profil utilisateur authentifié.
     */
    public function updateMe(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Non authentifié'], 401);
        }

        $validator = Validator::make($request->all(), [
            'name'  => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:30|regex:/^(\+?[0-9]{7,15})$/',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }

        if ($request->filled('name'))  $user->name  = $request->name;
        if ($request->filled('email')) $user->email = $request->email;
        if ($request->filled('phone')) $user->phone = $request->phone;

        if ($request->hasFile('image')) {
            $file     = $request->file('image');
            $filename = 'profile_' . $user->id . '_' . time() . '.' . $file->extension();
            $file->move(public_path('images/profile_images'), $filename);
            $user->image = $filename;
        }

        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Profil mis à jour.',
            'data' => $user->only(['id', 'name', 'email', 'phone']),
        ]);
    }

    /**
     * S5.3 — Favoris restaurants en API mobile.
     */
    public function favoriteRestaurants(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Non authentifié'], 401);
        }

        $restaurants = $user->favoriteRestaurants()
            ->with('cuisines')
            ->withAvg('ratings', 'rating')
            ->orderByDesc('restaurant_favorites.created_at')
            ->paginate(15);

        $items = collect($restaurants->items())->map(function ($r) {
            return [
                'id'             => $r->id,
                'name'           => $r->name,
                'address'        => $r->address,
                'logo'           => $r->logo ? URL::to('/') . '/images/restaurant_images/' . $r->logo : null,
                'avg_rating'     => round((float) ($r->ratings_avg_rating ?? 0), 1),
                'cuisines'       => $r->cuisines->pluck('name'),
                'delivery_fee'   => $r->delivery_charges ?? 0,
            ];
        });

        return response()->json([
            'status' => true,
            'data'   => $items,
            'meta'   => [
                'current_page' => $restaurants->currentPage(),
                'last_page'    => $restaurants->lastPage(),
                'total'        => $restaurants->total(),
            ],
        ]);
    }

    /**
     * S5.3 — Toggle favori restaurant en API mobile.
     */
    public function toggleFavoriteRestaurant(Request $request, $restaurantId)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Non authentifié'], 401);
        }

        $restaurant = Restaurant::find($restaurantId);
        if (!$restaurant) {
            return response()->json(['status' => false, 'message' => 'Restaurant introuvable'], 404);
        }

        $isFav = $user->favoriteRestaurants()->where('restaurants.id', $restaurantId)->exists();

        if ($isFav) {
            $user->favoriteRestaurants()->detach($restaurantId);
        } else {
            $user->favoriteRestaurants()->syncWithoutDetaching([$restaurantId]);
        }

        return response()->json([
            'status'      => true,
            'is_favorite' => !$isFav,
            'message'     => $isFav ? 'Retiré des favoris.' : 'Ajouté aux favoris.',
        ]);
    }
    }
