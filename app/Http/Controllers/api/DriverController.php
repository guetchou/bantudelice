<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Driver;
use App\Order;
use App\User;
use App\News;
use App\Restaurant;
use App\Product;
use App\DriverHistory;
use App\UserToken;
use App\Services\MissionPresenceBroadcastService;
use App\Services\NotificationService;
use Carbon\Carbon;
use App\CompletedOrder;
use Illuminate\Support\Facades\Mail;
use App\Mail\RegisterEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\Console\Input\Input;

if (!defined('BASE_URL_PROFILE')) define('BASE_URL_PROFILE',URL::to('/').'/images/driver_images/');

class DriverController extends Controller
{
    protected ?MissionPresenceBroadcastService $missionPresenceBroadcastService = null;
    
    public function notification( $body,$title,$device_token,$key,$user_id)
    {
        $result = NotificationService::sendToDevice($device_token, $title, $body, $key, $user_id, 'user');

        return response()->json(['data' => !empty($result['success']) ? 'notification sent' : 'notification failed', 'action' => $result['action'] ?? null], 200);
    }
    
    public function userNotification( $body,$title,$device_token,$key,$user_id)
    {
        $result = NotificationService::sendToMultipleDevices($device_token, $title, $body, $key, $user_id, 'user');

        return response()->json(['data' => !empty($result['success']) ? 'notification sent' : 'notification failed', 'action' => $result['action'] ?? null], 200);
    }
    
    public function register(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            array(
                'name'=>'required',
                'email' => 'required|email|max:255|unique:users',
                'password' => 'required',
                'phone'=>'required|unique:users',
                'address'=>'nullable',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:8192',
                'account_name'=>'nullable',
                'account_address' => 'required',
                'account_number' => 'required',
                'bank_name'=>'required',
                'branch_name'=>'required',
                'branch_address'=>'required',
                'paypal_account_no'=>'required',
                'licence_image' => 'required|image|mimes:jpeg,png,jpg,webp|max:8192',
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
                    $request['approved']=0;
                    $driver = Driver::create($request->all());
                    $image = $request->image;
                    $destination = 'images/driver_images';
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
                        $driver->licence_image = $filename;
                        $driver->save();
                    }

                    $image = $request->licence_image;
                    $destination = 'images/driver_images';
                    if ($request->hasFile('licence_image')) {
                        $filename = strtolower(
                            pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME)
                            . '-'
                            . uniqid()
                            . '.'
                            . $image->getClientOriginalExtension()
                        );
                        $image->move($destination, $filename);
                        str_replace(" ", "-", $filename);
                        $driver->image = $filename;
                        $driver->save();
                    }
                    
                    $dataEmail = array(
            			'name' => $driver->name,
            			'email' => $driver->email,
            		);
                   //sending email
                   Mail::to($request->email)->send(new RegisterEmail($dataEmail));
                    $data = 'Bearer' . ' ' . $driver->createToken('MyApp')->accessToken;
                    DB::commit();
                    
                } catch (\Exception $exception) {
                    DB::rollBack();
                    return response()->json([
                        'message' => $exception->getMessage()
                    ], 403);
                }
                $response_array = array('status' => true, 'driver_id' =>$driver->id ,'status_code' => 200, 'data' => $data);
            }
        return response()->json($response_array, 200);
    }
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'error_code' => 101,
                'message' => implode(',', $validator->messages()->all()),
            ], 422);
        }

        $phone = $request->input('phone');
        $password = $request->input('password');

        $driver = Driver::where('phone', '=', $request->phone)->first();
        if (!$driver) {
            return response()->json([
                'message' => 'Phone Dose Not Exist!',
                'status' => false
            ], 403);
        }
        elseif ($driver->approved==0) {
            return response()->json([
                'message' => 'You are not approved by admin!',
                'status' => false
            ], 403);
        }
        
        elseif (!Hash::check($password, $driver->password)) {
            return response()->json([
                'message' => 'Incorrect Password',
                'status' => false
            ], 403);
        }
        $request['driver_id'] = $driver->id;
        $request['name'] = $driver->name;
        $request['email'] = $driver->email;
        $request['phone'] = $driver->phone;
        $request['approved'] = $driver->approved;
        $request['image'] =  $driver->image;
        $data = 'Bearer' . ' ' . $driver->createToken('MyApp')->accessToken;
        $response_array = array('user_id'=>$request->driver_id,
                'name'=>$request->name,'email'=>$request->email,
                'image'=>$request->image,
                'password_change_required' => (bool) ($driver->password_must_change ?? false),
                'status' => true,'status_code'=>200,'message' => 'Connexion réussie',
                'data'=>$data);

        $response = response()->json($response_array, 200);
        return $response;
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make(
            $request->all(),[
            'phone' => 'required',
            'password' => 'required'
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


        $check_password=Driver::where(['phone'=>$request->phone])->first();
            $password=bcrypt($request->password);
            if($check_password)
            { 
            Driver::where('phone',$request->phone)->update([
                'password'=>$password,
                'password_must_change' => false,
                'password_changed_at' => now(),
            ]);
            return response()->json([
                'status' => true,
                'message' => 'Mis à jour avec succès !',
            ]);
        }
        else{
            return response()->json([
                'status' => false,
                'message' => 'Livreur introuvable',
            ], 404);
        }

        }
    }

    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required',
            'current_password' => 'required',
            'password' => 'required|min:8|different:current_password',
        ]);

        if ($validator->fails()) {
            $error_messages = implode(',', $validator->messages()->all());

            return response()->json([
                'status' => false,
                'error_code' => 101,
                'message' => $error_messages,
            ], 422);
        }

        $driver = Driver::where('phone', $request->phone)->first();
        if (!$driver) {
            return response()->json([
                'status' => false,
                'message' => 'Livreur introuvable',
            ], 404);
        }

        if (!Hash::check($request->current_password, $driver->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Mot de passe actuel incorrect',
            ], 403);
        }

        $driver->password = bcrypt($request->password);
        $driver->password_must_change = false;
        $driver->password_changed_at = now();
        $driver->save();

        return response()->json([
            'status' => true,
            'message' => 'Mot de passe mis à jour avec succès',
            'password_change_required' => false,
        ]);
    }
    public function profile($user)
    {
        $getUser=Driver::select('id','name','email','image','phone', 'created_at')->where('id',$user)->first();

        if (!$getUser) {
            return response()->json([
                'status' => false,
                'message' => 'Livreur introuvable',
                'data' => null,
                'years' => 0,
                'BASE_URL_PROFILE' => BASE_URL_PROFILE,
            ], 404);
        }

        $getUser->image_url = !empty($getUser->image)
            ? (filter_var($getUser->image, FILTER_VALIDATE_URL) ? $getUser->image : URL::to('/') . '/images/profile_images/' . $getUser->image)
            : null;
        
 $from = \Carbon\Carbon::parse($getUser->created_at);
 $to = now();

   $diff_in_years = $to->diffInYears($from);
        return response()->json([
            'status' => true,
            'data' => $getUser,
            'years' => $diff_in_years,
            'BASE_URL_PROFILE'=>BASE_URL_PROFILE,
        ]);
    }
    
    
    public function updateProfile(Request $request)
    {
        

$validator = Validator::make(
            $request->all(),
            array(
                'driver_id'=>'required',
                'name'=>'nullable',
                'paypal_account_no'=>'required',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:8192',
            ));
            $driver=Driver::find($request->driver_id); 
        if ($validator->fails()) {
            $error_messages = implode(',', $validator->messages()->all());
            return response()->json([
                'status' => false,
                'error_code' => 101,
                'message' => $error_messages,
            ], 422);
        }
        elseif (!$driver) {
            return response()->json([
                'status' => false,
                'message' => 'Livreur introuvable',
            ], 404);
        }
        else {
                  $driver->name = $request->name;
                  $driver->email = $request->email;
                  //$driver->image = $request->image;
                  $driver->save();
                   
                    if($image = $request->image=='')
                    {
                        $image = $driver->image;
                    }
                    else{
                        $image = $request->image;
                    }
                    $destination = 'images/driver_images';
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
                        $driver->image = $filename;
                        $driver->save();
                    }
                    $data['name']= $driver->name;
                    $data['email']= $driver->email;
                    $data['paypal_account_no']= $driver->paypal_account_no;
                $response_array = array('status' => true, 'data' => $data, 'status_code' => 200);
            }
        $response = response()->json($response_array, 200);
        return $response;
    }
    
    
    
    
    
    public function SetDriverOnline(Request $request , $driver)
    {
        $validator = Validator::make(
            $request->all(),
            array(
                'latitude' => 'required',
                'longitude' => 'required',
                'device_token' => 'required'
            ));

        if ($validator->fails()) {
            $error_messages = implode(',', $validator->messages()->all());
            return response()->json([
                'status' => false,
                'error_code' => 101,
                'message' => $error_messages,
            ], 422);
        }
        else {
        $update=Driver::find($driver);
        if (!$update) {
            return response()->json([
                'status' => false,
                'message' => 'Livreur introuvable',
            ], 404);
        }
        $update->status='online';
        $update->latitude=$request->latitude;
        $update->longitude=$request->longitude;
        $update->device_token=$request->device_token;
        $update->save();
         return response()->json([
            'status' => true,
            'message' =>'You are online now!'
        ]);
       }
       
    }
    
    public function SetDriverOnlineTime(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            array(
                'driver_id'=>'required',
                'start_date'=>'required',
                'end_date'=>'required',
                
            ));

        if ($validator->fails()) {
            $error_messages = implode(',', $validator->messages()->all());
            return response()->json([
                'status' => false,
                'error_code' => 101,
                'message' => $error_messages,
            ], 422);
        }

        if (!Driver::where('id', $request->driver_id)->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'Livreur introuvable',
            ], 404);
        }

        DriverHistory::create($request->all());

        return response()->json([
            'status' => true,
        ]);
    }
    
    public function SetDriverOffline($user)
    {
        $getUser=Driver::where('id',$user)->first();

        if (!$getUser) {
            return response()->json([
                'status' => false,
                'message' => 'Livreur introuvable',
            ], 404);
        }
        
        $getUser->status='offline';
        $getUser->save();

        return response()->json([
            'status' => true,
            'message' =>'You are Offline Now!'
        ]);
    }

    
    //Assign Orders 
     public function orderRequests($driver)
    {
        $driverModel = Driver::find($driver);

        if (!$driverModel) {
            return response()->json([
                'status' => false,
                'message' => 'Livreur introuvable',
                'data' => null,
            ], 404);
        }

        $getRestId=Order::where('driver_id',$driver)->first();
        $getRestIds=Order::where('driver_id',$driver)->get();
        if (!$getRestId) {
            $driverModel['orders'] = collect();

            return response()->json([
                'status' => true,
                'data' => $driverModel
            ]);
        }
        $cartProIDs=$getRestIds->pluck('product_id')->toArray();
        $cartOrderNo=$getRestIds->pluck('order_no')->toArray();
        $cartUserIDs=$getRestIds->pluck('user_id')->toArray();
        
        $getorderData=Restaurant::find($getRestId->restaurant_id);
       
         $Ids=array_unique($cartOrderNo);
            
        $getorderData['orders']=Order::whereIn('order_no',$Ids)->with('user')->take(count($Ids))->get();
        return response()->json([
            'status' =>true,
            'data' =>$getorderData
            
            ]);
    }
    
    public function ordersProducts($orderno)
    {
        $getOrders=Order::where('order_no',$orderno)->get();

        if ($getOrders->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'Commande introuvable',
                'products' => [],
            ], 404);
        }

        $ProIDs=$getOrders->pluck('product_id')->toArray();
        
        $products=Product::whereIn('id', $ProIDs)->get();
        
        return response()->json([
            'status' => true,
            'products' =>$products
            
            ]);
        
    }
    
     public function acceptOrderRequests(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'driver_id' => 'required',
            'status' => 'required|in:1,3',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'error_code' => 101,
                'message' => implode(',', $validator->messages()->all()),
            ], 422);
        }

        $driverId=$request->driver_id;
        if (!Driver::where('id', $driverId)->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'Livreur introuvable',
            ], 404);
        }

        $orders=Order::get()->unique('order_no');
        $users=$orders->pluck('user_id')->toArray();
        $user=UserToken::WhereIn('user_id',$users)->get();
        $tokens=$user->pluck('device_tokens')->toArray();
        if($request->status==1)
        {
        $body='Your is assign to driver';
        $title='Order Assign';
        $key='assignOrder';
          // Les transitions order.status/business_status sont gérées par la state machine
          // via DeliveryService::assignDriver(). Ce endpoint envoie uniquement la notification.
          \Illuminate\Support\Facades\Log::info('acceptOrderRequests: notification assign envoyée', ['driver_id' => $driverId]);
            $data=$this->userNotification($body,$title,$tokens,$key,$driverId);
          $status=true;
        }
        elseif($request->status==3){
            $body='Your Order is pickup from';
        $title='Order Pickup';
        $key='pickipOrder';
          // Les transitions order.status/business_status sont gérées par la state machine
          // via DeliveryService::updateStatus('PICKED_UP'). Ce endpoint envoie uniquement la notification.
          \Illuminate\Support\Facades\Log::info('acceptOrderRequests: notification pickup envoyée', ['driver_id' => $driverId]);
          $status=true;
          $data=$this->userNotification($body,$title,$tokens,$key,$driverId);
        }
        else{
            $status=false;
        }
        
        return response()->json([
            'status' =>$status,
            
            ]);
    }
    
    public function deliverySummary($driver)
    {
        $driverModel = Driver::find($driver);

        if (!$driverModel) {
            return response()->json([
                'status' => false,
                'message' => 'Livreur introuvable',
                'derlieries' => 0,
                'total' => 0,
            ], 404);
        }
       
        $records = DB::table('completed_orders')
        ->select(DB::raw('*'))
        ->whereRaw('Date(created_at) = CURDATE()')
        ->count();

       if (!Schema::hasTable('driver_histories')) {
            return response()->json([
                'status' => true,
                'derlieries' => $records,
                'total' => 0,
                'starttime' => null,
                'to' => 0,
            ]);
       }

       $driverHistory=DriverHistory::where('driver_id',$driver)
       ->latest()->first();

       if (!$driverHistory) {
            return response()->json([
                'status' => true,
                'derlieries' => $records,
                'total' => 0,
                'starttime' => null,
                'to' => 0,
            ]);
       }

  $start = Carbon::parse($driverHistory->start_date);
  $end = $driverHistory->end_date ? Carbon::parse($driverHistory->end_date) : now();

  $diff_in_hours = $start->diffInHours($end);
  $finalEarnings=$driverModel->hourly_pay * $diff_in_hours;
  
                 return response()->json([
                'status' =>true,
                'derlieries' =>$records,
                'total' =>$finalEarnings,
                'starttime' => $driverHistory->start_date,
                'to' => $diff_in_hours,
            
            ]);
                  
    }
    
    public function driverEarningHistory(Request $request, $driver)
    {
        if (!Driver::whereKey($driver)->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'Livreur introuvable',
                'totalEarning' => 0,
                'weeks' => [],
            ], 404);
        }
        
        // currentEarning = TotalEarning - WithdrawEarning
        
        
        $data=DriverHistory::where('driver_id',$driver)->get()->sum('earnings');
        
        
        // $result['weeks']= DriverHistory::where('driver_id',$driver)->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->selectRaw('year(created_at) year, monthname(created_at) month')
        //         ->groupBy('year', 'month')
        //          ->orderBy('year', 'desc')->get();
        
        if($request->start_date!='' || $request->end_date!='')
        {
         $result= DriverHistory::where('driver_id',$driver)->whereBetween('created_at', [$request->start_date, $request->end_date])->latest()->get();   
        }
        else{
        $result= DriverHistory::where('driver_id',$driver)->latest()->get();
        }
     return response()->json([
                'status' =>true,
                'totalEarning' =>$data,
                'weeks' =>$result,
            
            ]);
    }
    
    public function latestNews()
    {
        $news=News::latest()->get();
        return response()->json([
                'status' =>true,
                'data' =>$news,
            
            ]);
    }
    
    /**
     * API: Mettre à jour la position GPS du livreur en temps réel
     * Route: POST /api/driver/{driverId}/location
     * Body: { "latitude": 48.8566, "longitude": 2.3522 }
     */
    public function updateLocation(Request $request, $driverId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Données invalides',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $driver = Driver::find($driverId);
            
            if (!$driver) {
                return response()->json([
                    'status' => false,
                    'message' => 'Livreur non trouvé'
                ], 404);
            }
            
            // Mettre à jour la position dans la table drivers
            $driver->latitude = $request->latitude;
            $driver->longitude = $request->longitude;
            $driver->status = 'online'; // Marquer comme en ligne lors de la mise à jour
            $driver->save();
            
            // Enregistrer dans l'historique driver_locations
            try {
                \App\DriverLocation::create([
                    'driver_id' => $driver->id,
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                    'accuracy' => $request->accuracy ?? null,
                    'heading' => $request->heading ?? null,
                    'speed' => $request->speed ?? null,
                    'timestamp' => now(),
                ]);
            } catch (\Exception $e) {
                // Log l'erreur mais ne bloque pas la mise à jour
                \Log::warning('Erreur enregistrement historique position livreur', [
                    'driver_id' => $driver->id,
                    'error' => $e->getMessage()
                ]);
            }
            
            // Enregistrer aussi dans DriverHistory si la table existe (rétrocompatibilité)
            try {
                if (class_exists('App\DriverHistory')) {
                    \App\DriverHistory::create([
                        'driver_id' => $driver->id,
                        'latitude' => $request->latitude,
                        'longitude' => $request->longitude,
                        'created_at' => now(),
                    ]);
                }
            } catch (\Exception $e) {
                // Ignorer si la table n'existe pas
            }

            $this->missionPresenceBroadcasts()->broadcastForDriver($driver->fresh());
            
            return response()->json([
                'status' => true,
                'message' => 'Position mise à jour avec succès',
                'driver' => [
                    'id' => $driver->id,
                    'name' => $driver->name,
                    'latitude' => $driver->latitude,
                    'longitude' => $driver->longitude,
                    'status' => $driver->status,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Erreur lors de la mise à jour de la position',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    protected function missionPresenceBroadcasts(): MissionPresenceBroadcastService
    {
        return $this->missionPresenceBroadcastService ??= app(MissionPresenceBroadcastService::class);
    }
}
