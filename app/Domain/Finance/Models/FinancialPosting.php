<?php

namespace App\Domain\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FinancialPosting extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'batch_id',
        'account_id',
        'line_no',
        'direction',
        'amount',
        'currency',
        'description',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'batch_id' => 'integer',
        'account_id' => 'integer',
        'line_no' => 'integer',
        'amount' => 'integer',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(FinancialPostingBatch::class, 'batch_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'account_id');
    }
}
