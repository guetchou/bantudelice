<?php

namespace App\Domain\Payment\Models;

use App\Domain\Payment\Enums\LedgerDirection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialJournalLine extends Model
{
    protected $fillable = [
        'journal_entry_id',
        'financial_account_id',
        'direction',
        'amount',
        'narrative',
        'metadata',
    ];

    protected $casts = [
        'direction' => LedgerDirection::class,
        'amount' => 'integer',
        'metadata' => 'array',
    ];

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(FinancialJournalEntry::class, 'journal_entry_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'financial_account_id');
    }
}
