<?php

namespace App\Domain\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class FinancialAccount extends Model
{
    protected $fillable = [
        'uuid',
        'code',
        'name',
        'category',
        'purpose',
        'owner_type',
        'owner_id',
        'currency',
        'status',
        'metadata',
    ];

    protected $casts = [
        'owner_id' => 'integer',
        'metadata' => 'array',
    ];

    public function postings(): HasMany
    {
        return $this->hasMany(FinancialPosting::class, 'account_id');
    }
}
