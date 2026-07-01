<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PaymentReconciliationCase extends Model
{
    protected $fillable = [
        'case_key', 'payment_id', 'withdrawal_id', 'case_type', 'severity',
        'status', 'expected_amount', 'observed_amount', 'currency', 'provider',
        'provider_reference', 'summary', 'details', 'opened_at', 'resolved_at',
        'resolved_by',
    ];

    protected $casts = [
        'expected_amount' => 'decimal:2',
        'observed_amount' => 'decimal:2',
        'details' => 'array',
        'opened_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function withdrawal()
    {
        return $this->belongsTo(PartnerWithdrawal::class, 'withdrawal_id');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['open', 'investigating'], true);
    }
}
