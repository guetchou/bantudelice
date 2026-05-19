<?php

namespace App;

use App\Support\HasDefaultAvatar;
use App\Address;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, HasFactory, HasDefaultAvatar, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
    'name',
    'email',
    'password',
    'phone',
    'type',
    'image',
    'social_provider',
    'social_id',
    'social_avatar',
    'api_token',
    'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];
    public function restaurant()
    {
        return $this->hasOne(Restaurant::class);
    }

    public function favoriteRestaurants()
    {
        return $this->belongsToMany(Restaurant::class, 'restaurant_favorites')
            ->withTimestamps();
    }

    public function order()
    {
        return $this->hasMany(Order::class);
    }
    public function cancellation_reasons()
    {
        return $this->hasMany(CancellationReason::class);
    }
    
    public function loyaltyPoints()
    {
        return $this->hasOne(LoyaltyPoint::class);
    }
    
    public function loyaltyTransactions()
    {
        return $this->hasMany(LoyaltyTransaction::class);
    }

    public function addresses()
    {
        return $this->hasMany(Address::class)->orderByDesc('is_default')->orderByDesc('id');
    }

    /**
     * Relation avec les envois de colis
     */
    public function shipments()
    {
        return $this->hasMany(\App\Domain\Colis\Models\Shipment::class, 'customer_id');
    }

    /**
     * Relation avec les réservations de transport
     */
    public function transportBookings()
    {
        return $this->hasMany(\App\Domain\Transport\Models\TransportBooking::class, 'user_id');
    }



    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
}
