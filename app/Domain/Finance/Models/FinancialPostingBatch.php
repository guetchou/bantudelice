<?php

namespace App\Domain\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class FinancialPostingBatch extends Model
{
    protected $fillable = [
        'uuid',
        'event_type',
        'source_type',
        'source_id',
        'idempotency_key',
        'status',
        'reversal_of_batch_id',
        'effective_at',
        'posted_at',
        'metadata',
    ];

    protected $casts = [
        'source_id' => 'integer',
        'reversal_of_batch_id' => 'integer',
        'effective_at' => 'datetime',
        'posted_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function postings(): HasMany
    {
        return $this->hasMany(FinancialPosting::class, 'batch_id')->orderBy('line_no');
    }

    public function reversedBatch(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_batch_id');
    }
}
