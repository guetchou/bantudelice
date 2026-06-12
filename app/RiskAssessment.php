<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RiskAssessment extends Model
{
    protected $fillable = [
        'scope',
        'subject_type',
        'subject_id',
        'order_id',
        'score',
        'level',
        'reason',
        'action',
        'payload',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'payload' => 'array',
    ];
}
