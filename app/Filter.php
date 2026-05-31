<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Filter extends Model
{
    protected $guarded = [];

    public function searchfilters()
    {
        return $this->hasMany(SearchFilter::class);
    }
}
