<?php

namespace App;

use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class Driver extends Model implements AuthenticatableContract
{
    use Notifiable, HasApiTokens, HasFactory, \Illuminate\Auth\Authenticatable;

    protected $fillable = [
        'user_id',
        'restaurant_id',
        'name',
        'user_name',
        'hourly_pay',
        'email',
        'cnic',
        'password',
        'phone',
        'image',
        'address',
        'latitude',
        'longitude',
        'status',
        'is_available',
        'device_token',
        'account_name',
        'account_address',
        'account_number',
        'bank_name',
        'branch_name',
        'branch_address',
        'paypal_account_no',
        'licence_image',
        'hours',
        'vehicle',
        'days',
        'active_transport_vehicle_id',
        'avg_rating',
        'rating_count',
        'approved',
        'password_must_change',
        'password_changed_at',
        'password_temp_issued_at',
        'provisioned_by_admin_id',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'approved' => 'boolean',
        'is_available' => 'boolean',
        'password_must_change' => 'boolean',
        'password_changed_at' => 'datetime',
        'password_temp_issued_at' => 'datetime',
    ];

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

    public function locations()
    {
        return $this->hasMany(DriverLocation::class);
    }

    public function getLatestLocation()
    {
        return $this->locations()
            ->orderByDesc('timestamp')
            ->first();
    }

    public function shipments()
    {
        return $this->hasMany(\App\Domain\Colis\Models\Shipment::class, 'assigned_courier_id');
    }

    public function transportBookings()
    {
        return $this->hasMany(\App\Domain\Transport\Models\TransportBooking::class, 'driver_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
