<?php

namespace App\Domain\Payment\Models;

use App\Domain\Payment\Enums\JournalEntryStatus;
use App\Domain\Payment\Enums\JournalEntryType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class FinancialJournalEntry extends Model
{
    protected $fillable = [
        'uuid',
        'idempotency_key',
        'type',
        'status',
        'reference',
        'source_type',
        'source_id',
        'description',
        'currency',
        'effective_at',
        'posted_at',
        'reversed_entry_id',
        'metadata',
    ];

    protected $casts = [
        'type' => JournalEntryType::class,
        'status' => JournalEntryStatus::class,
        'effective_at' => 'datetime',
        'posted_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $entry): void {
            if (!$entry->uuid) {
                $entry->uuid = (string) Str::uuid();
            }
        });
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function lines(): HasMany
    {
        return $this->hasMany(FinancialJournalLine::class, 'journal_entry_id');
    }

    public function reversedEntry(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversed_entry_id');
    }

    public function totalDebits(): int
    {
        return (int) $this->lines()->where('direction', 'debit')->sum('amount');
    }

    public function totalCredits(): int
    {
        return (int) $this->lines()->where('direction', 'credit')->sum('amount');
    }

    public function isBalanced(): bool
    {
        return $this->totalDebits() === $this->totalCredits();
    }
}
