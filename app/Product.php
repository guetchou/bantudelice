<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class Product extends Model
{
    use SoftDeletes;
    protected $fillable=['restaurant_id','category_id','name','image',
        'price','discount_price','description','size','is_available','sort_order','featured'
    ];

    public function extras()
    {
        return $this->hasMany(Extra::class);
    }
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
    
    public function restaurants()
    {
        return $this->belongsTo(Restaurant::class,'restaurant_id');
    }
    public function categories()
    {
        return $this->belongsTo(Category::class,'category_id');
    }

    public function publicImageUrl(): string
    {
        $fallback = asset('images/product_images/default-food.jpg');
        $value = $this->image;

        if (empty($value)) {
            return $fallback;
        }

        if (Str::startsWith($value, ['http://', 'https://'])) {
            return $value;
        }

        $normalized = ltrim((string) $value, '/');
        $candidates = [];

        if (Str::contains($normalized, '/')) {
            $candidates[] = $normalized;
        }

        foreach ([
            'images/product_images',
            'images/cms/library',
            'images/cms',
        ] as $directory) {
            $candidates[] = trim($directory, '/') . '/' . $normalized;
        }

        foreach (array_unique($candidates) as $relativePath) {
            if (File::exists(public_path($relativePath))) {
                return asset($relativePath);
            }
        }

        return $fallback;
    }
}
