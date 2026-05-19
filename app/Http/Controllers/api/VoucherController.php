<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Restaurant;
use App\Voucher;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;


class VoucherController extends Controller
{
   public function getVoucher(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            array(
                'name'=>'required',
                'restaurant_id' => 'required',
            ));

        if ($validator->fails()) {
            $error_messages = implode(',', $validator->messages()->all());
            return response()->json([
                'status' => false,
                'error_code' => 101,
                'message' => $error_messages,
            ], 422);
        }
        elseif (!Restaurant::where('id', $request->restaurant_id)->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'Restaurant introuvable',
                'data' => [],
            ], 404);
        }
        else {
    	$get=Voucher::select('id','name','restaurant_id','discount','start_date','end_date')
    	->where('restaurant_id',$request->restaurant_id)->where('name',$request->name)
    	->where('end_date', '>', Carbon::now())->get();
    	
    	if($get->count()!=0){
    	    return response()->json([
           'status' => true,
           'data' => $get
    	]);
    	}
    	else{
    	    
    	   return response()->json([
           'status' => false,
    	]); 
    	}
    	
    }
    }
}
