<?php

namespace App\Domain\Payment\Models;

use App\Domain\Payment\Enums\FinancialAccountType;
use App\Domain\Payment\Enums\JournalEntryStatus;
use App\Domain\Payment\Enums\LedgerDirection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class FinancialAccount extends Model
{
    protected $fillable = [
        'uuid',
        'code',
        'type',
        'normal_balance',
        'owner_type',
        'owner_id',
        'currency',
        'status',
        'metadata',
    ];

    protected $casts = [
        'type' => FinancialAccountType::class,
        'normal_balance' => LedgerDirection::class,
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $account): void {
            if (!$account->uuid) {
                $account->uuid = (string) Str::uuid();
            }
        });
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function lines(): HasMany
    {
        return $this->hasMany(FinancialJournalLine::class);
    }

    public function balance(): int
    {
        $baseQuery = fn () => $this->lines()
            ->whereHas('journalEntry', function ($query): void {
                $query->whereIn('status', [
                    JournalEntryStatus::POSTED->value,
                    JournalEntryStatus::REVERSED->value,
                ]);
            });

        $normal = $baseQuery()
            ->where('direction', $this->normal_balance->value)
            ->sum('amount');
        $opposite = $baseQuery()
            ->where('direction', $this->normal_balance->opposite()->value)
            ->sum('amount');

        return (int) $normal - (int) $opposite;
    }
}
