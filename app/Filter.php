<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Filter extends Model
{
    // Tables non présentes en DB — modèles legacy. Aucun champ mass-assignable.
    protected $fillable = [];

    public function searchfilters()
    {
        return $this->hasMany(SearchFilter::class);
    }
}
