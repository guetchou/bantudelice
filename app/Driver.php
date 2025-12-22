<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;

class Driver extends Model implements AuthenticatableContract
{
     use Notifiable,HasApiTokens,HasFactory,\Illuminate\Auth\Authenticatable;
    protected $fillable=['restaurant_id','name','hourly_pay','email','cnic','password','phone','image','address','latitude','longitude','status','account_name','account_number','bank_name','branch_name','branch_address','licence_image','hours','vehicle','days', 'active_transport_vehicle_id'];
    
    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }
    public function activeTransportVehicle()
    {
        return $this->belongsTo(\App\Domain\Transport\Models\TransportVehicle::class, 'active_transport_vehicle_id');
    }
    public function vehicle()
    {
        return $this->hasOne(Vehicle::class);
    }
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
    public function payouts()
    {
        return $this->hasMany(DriverPayment::class);
    }
    
    public function deliveries()
    {
        return $this->hasMany(Delivery::class);
    }
    
    /**
     * Relation avec l'historique des positions GPS
     */
    public function locations()
    {
        return $this->hasMany(DriverLocation::class);
    }
    
    /**
     * Récupérer la dernière position GPS connue
     */
    public function getLatestLocation()
    {
        return $this->locations()
            ->orderBy('timestamp', 'desc')
            ->first();
    }

    /**
     * Relation avec les envois de colis assignés
     */
    public function shipments()
    {
        return $this->hasMany(\App\Domain\Colis\Models\Shipment::class, 'assigned_courier_id');
    }

    /**
     * Relation avec les réservations de transport (Taxi/Covoiturage/Location)
     */
    public function transportBookings()
    {
        return $this->hasMany(\App\Domain\Transport\Models\TransportBooking::class, 'driver_id');
    }
}
