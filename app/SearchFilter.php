<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SearchFilter extends Model
{
    // Tables non présentes en DB — modèles legacy. Aucun champ mass-assignable.
    protected $fillable = [];

    
    public function filters()
    {
        return $this->belongsTo(Filter::class);
    }
}
