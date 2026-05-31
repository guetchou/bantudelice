<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SearchFilter extends Model
{
    protected $guarded = [];

    
    public function filters()
    {
        return $this->belongsTo(Filter::class);
    }
}
