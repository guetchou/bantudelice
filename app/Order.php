<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable=['restaurant_id','user_id','product_id','qty','price','latitude','longitude','offer_discount','tax','delivery_charges','sub_total','total','admin_commission','restaurant_commission','driver_tip','delivery_address','scheduled_date','ordered_time','delivered_time','order_no','d_lat','d_lng','payment_method','payment_status','status','driver_id'];
    
   public function restaurant()
   {
       return $this->belongsTo(Restaurant::class);
   }
   public function product()
   {
       return $this->belongsTo(Product::class);
   }
    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function rating()
    {
        return $this->hasOne(Rating::class);
    }
    
    public function delivery()
    {
        return $this->hasOne(Delivery::class);
    }
    
    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

}
