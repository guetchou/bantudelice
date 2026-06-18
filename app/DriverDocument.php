<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DriverDocument extends Model
{
    protected $fillable = [
        'driver_id', 'type', 'file_path', 'original_name',
        'status', 'rejection_reason', 'reviewed_by', 'reviewed_at',
    ];

    protected $casts = ['reviewed_at' => 'datetime'];

    public static array $types = [
        'permis'    => ['label' => 'Permis de conduire',       'icon' => 'fa-id-card'],
        'assurance' => ['label' => "Attestation d'assurance",  'icon' => 'fa-shield-halved'],
        'cni'       => ['label' => "Carte Nationale d'Identité", 'icon' => 'fa-fingerprint'],
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function typeLabel(): string
    {
        return self::$types[$this->type]['label'] ?? $this->type;
    }

    public function isPending(): bool  { return $this->status === 'pending'; }
    public function isApproved(): bool { return $this->status === 'approved'; }
    public function isRejected(): bool { return $this->status === 'rejected'; }
}
