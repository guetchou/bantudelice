<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AdminAuditLog extends Model
{
    protected $table = 'admin_audit_logs';

    protected $fillable = [
        'admin_id', 'admin_email', 'method', 'path', 'route_name',
        'action', 'payload', 'ip', 'user_agent', 'response_status',
    ];

    protected $casts = ['payload' => 'array'];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
