<?php

namespace App;

use App\Support\HasDefaultAvatar;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Admin extends  Authenticatable
{
    use Notifiable, HasDefaultAvatar;
    protected $fillable=['name','email','password','phone','type','image'];

    protected $hidden = [
        'password'
    ];

}
