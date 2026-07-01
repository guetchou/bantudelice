<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PaymentReconciliationCase extends Model
{
    protected $fillable = [
        'uuid',
        'subject_type',
        'subject_id',
        'case_type',
        'severity',
        'status',
        'expected_amount',
        'observed_amount',
        'currency',
        'internal_status',
        'provider_status',
        'provider_reference',
        'assigned_to',
        'opened_at',
        'resolved_at',
        'resolution_note',
        'evidence',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'resolved_at' => 'datetime',
        'evidence' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $case) {
            $case->uuid ??= (string) Str::uuid();
            $case->opened_at ??= now();
        });
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['open', 'investigating'], true);
    }
}
