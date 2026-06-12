<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\User;
use App\Address;
use App\Order;
use App\Restaurant;
use App\Product;
use App\Reason;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;

if (!defined('BASE_URL_PROFILE')) define('BASE_URL_PROFILE',URL::to('/').'/images/profile_images/');

class ReasonController extends Controller
{
  public function getReason(Request $request)
    {
        if (!Schema::hasTable('reasons')) {
            return response()->json([
                'status' => true,
                'data' => [],
            ]);
        }

        $getResaon=Reason::where('type',1)->get();
        
        return response()->json([
            'status' =>true,
            'data' =>$getResaon
            
            ]);
    }
    
    public function rejectOrderRequests(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            array(
                'driver_id'=>'required',
                'user_id' => 'required',
                'order_no' =>'required',

            ));
            
        if ($validator->fails())
        {
            $error_messages = implode(',', $validator->messages()->all());
            return response()->json([
                'status' => false,
                'error_code' => 101,
                'message' => $error_messages,
            ], 422);
        }
        else{
            if (!Schema::hasTable('reason_reject')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Journal de rejet indisponible',
                ], 503);
            }

            if (!User::where('id', $request->user_id)->exists()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Utilisateur introuvable',
                ], 404);
            }

            if (!DB::table('drivers')->where('id', $request->driver_id)->exists()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Livreur introuvable',
                ], 404);
            }

            $ordersNo=str_replace(array( '[', ']' ), '', $request->order_no);
            $optionalArray = explode(',', $ordersNo);
            
            foreach($optionalArray as $orders){
                 DB::table('reason_reject')->insert(
            [ 
              'driver_id' => $request->driver_id, 
              'user_id' => $request->user_id,
              'order_no' =>$orders,
              'reason'=>$request->reason,
              'created_at' => Now(),
           ]); 
            }
            
             // Réinitialiser l'assignation via la relation Delivery pour rester
             // cohérent avec business_status — évite le raw update sans state machine.
             $deliveries = \App\Delivery::where('driver_id', $request->driver_id)
                 ->whereIn('status', ['PENDING', 'ASSIGNED'])
                 ->get();

             foreach ($deliveries as $delivery) {
                 try {
                     app(\App\Services\DeliveryService::class)->resetForOrderModification($delivery, [
                         'actor_type' => 'driver',
                         'actor_id'   => $request->driver_id,
                         'reason'     => 'driver_rejected',
                     ]);
                 } catch (\Throwable $e) {
                     \Illuminate\Support\Facades\Log::warning('rejectOrderRequests: resetForOrderModification échoué', [
                         'delivery_id' => $delivery->id,
                         'error'       => $e->getMessage(),
                     ]);
                 }
             }

             return response()->json([
            'status' =>true,

            ]);    
         } 
        
    }
}
