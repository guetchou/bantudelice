<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    // restaurant_id est nécessaire pour les créations via Category::create/firstOrCreate
    // (les relations $restaurant->categories()->create() le set aussi automatiquement)
    protected $fillable=['name', 'restaurant_id', 'is_available', 'sort_order'];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
    public static function scopeSearch($query, $search)
    {
        return $query->where('name', 'like', '%' .$search. '%');
    }
}
