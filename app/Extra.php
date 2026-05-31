<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Extra extends Model
{
    protected $fillable = ['name', 'description', 'price', 'product_id', 'type_id'];

    protected $guarded = [];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
    public function types()
    {
        return $this->belongsTo(Type::class);
    }
}
