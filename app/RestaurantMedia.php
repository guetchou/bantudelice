<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RestaurantMedia extends Model
{
    protected $fillable = [
        'restaurant_id',
        'source',       // upload|external
        'file_name',    // si source=upload
        'external_url', // si source=external
        'alt_text',
        'sort_order',
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function getPublicUrlAttribute()
    {
        if ($this->source === 'external') {
            return $this->external_url;
        }

        if (!$this->file_name) {
            return null;
        }

        return url('images/restaurant_gallery/' . $this->file_name);
    }
}


