<?php

namespace App\Domain\Payment\Models;

use App\Domain\Payment\Enums\ReconciliationCaseStatus;
use App\Domain\Payment\Enums\ReconciliationDiscrepancy;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class PaymentReconciliationCase extends Model
{
    protected $fillable = [
        'uuid',
        'case_number',
        'fingerprint',
        'subject_type',
        'subject_id',
        'provider',
        'external_reference',
        'provider_reference',
        'status',
        'discrepancy_code',
        'expected_amount',
        'observed_amount',
        'currency',
        'expected_status',
        'observed_status',
        'evidence',
        'detected_at',
        'resolved_at',
        'resolved_by',
        'resolution_note',
    ];

    protected $casts = [
        'status' => ReconciliationCaseStatus::class,
        'discrepancy_code' => ReconciliationDiscrepancy::class,
        'expected_amount' => 'integer',
        'observed_amount' => 'integer',
        'evidence' => 'array',
        'detected_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $case): void {
            if (!$case->uuid) {
                $case->uuid = (string) Str::uuid();
            }

            if (!$case->case_number) {
                $case->case_number = 'RC-' . now()->format('Ymd') . '-' . strtoupper(substr(str_replace('-', '', $case->uuid), 0, 8));
            }
        });
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
