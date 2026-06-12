<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class Restaurant extends Model
{
    use SoftDeletes;
    protected $fillable=['user_id','name','email','password','city','address','phone','description','user_name','slogan','logo','cover_image',
        'latitude','longitude','min_order','avg_delivery_time',
        'services','account_name','account_number','bank_name','branch_name',
        'is_paused','paused_until','pause_reason','last_activity_at'];
    protected $casts = [
        'last_activity_at' => 'datetime',
        'paused_until'     => 'datetime',
    ];
    public function cuisines()
    {
        return $this->belongsToMany(Cuisine::class);
    }
    
    /**
     * Check if resturant has cuisines.
     *
     * @return bool
     */
    public function hasCuisine($cuisineId)
    {
        return in_array($cuisineId, $this->cuisines->pluck('id')->toArray());
    }
    public function drivers()
    {
        return $this->hasMany(Driver::class);
    }
    public function users()
    {
        return $this->hasMany(User::class);
    }
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
    public function payments()
    {
        return $this->hasMany(RestaurantPayment::class);
    }
    public function categories()
    {
        return $this->hasMany(Category::class);
    }
    public function products()
    {
        return $this->hasMany(Product::class);
    }
    public function employees()
    {
        return $this->hasMany(Employee::class);
    }
    public function working_hours()
    {
        return $this->hasMany(WorkingHour::class);
    }
    public function special_closures()
    {
        return $this->hasMany(RestaurantSpecialClosure::class)->orderBy('starts_on');
    }
     public function ratings()
    {
        return $this->hasMany(Rating::class);
    }
     public function cart()
    {
        return $this->hasMany(Cart::class);
    }
    public function vouchers()
    {
        return $this->hasMany(Voucher::class);
    }

    public function favoritedBy()
    {
        return $this->belongsToMany(User::class, 'restaurant_favorites')
            ->withTimestamps();
    }

    public function media()
    {
        return $this->hasMany(RestaurantMedia::class)->orderBy('sort_order')->orderBy('id');
    }

    public function publicIdentityImageUrl(): string
    {
        return $this->resolveRestaurantImageUrl(
            $this->logo ?: $this->cover_image ?: $this->galleryPreviewImageUrl(),
            asset('images/home/service-restaurant.jpg')
        );
    }

    public function publicCoverImageUrl(): string
    {
        return $this->resolveRestaurantImageUrl(
            $this->cover_image ?: $this->logo ?: $this->galleryPreviewImageUrl(),
            asset('images/home/service-restaurant.jpg')
        );
    }

    protected function resolveRestaurantImageUrl(?string $value, string $fallback): string
    {
        if (empty($value)) {
            return $fallback;
        }

        if (Str::startsWith($value, ['http://', 'https://'])) {
            return $value;
        }

        $normalized = ltrim($value, '/');
        $candidates = [];

        if (Str::contains($normalized, '/')) {
            $candidates[] = $normalized;
        }

        foreach ([
            'images/restaurant_images',
            'images/restaurant_gallery',
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

    protected function galleryPreviewImageUrl(): ?string
    {
        $media = $this->relationLoaded('media')
            ? $this->media->first()
            : $this->media()->first();

        return $media->public_url ?? null;
    }
}
