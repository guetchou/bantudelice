<?php

namespace App\Domain\GePay\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;

class GePayMerchantUser extends Model implements AuthenticatableContract
{
    use Authenticatable, Notifiable;

    protected $table = 'gepay_merchant_users';

    protected $fillable = [
        'merchant_id',
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'remember_token',
        'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(GePayMerchant::class, 'merchant_id');
    }

    public function operationTokens()
    {
        return $this->hasMany(GePayOperationToken::class, 'user_id');
    }
}
