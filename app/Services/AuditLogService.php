<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuditLogService
{
    public function record(array $payload): void
    {
        if (!Schema::hasTable('system_audit_logs')) {
            return;
        }

        DB::table('system_audit_logs')->insert([
            'actor_type' => $payload['actor_type'] ?? 'system',
            'actor_id' => $payload['actor_id'] ?? null,
            'target_type' => $payload['target_type'] ?? null,
            'target_id' => $payload['target_id'] ?? null,
            'target_ref' => $payload['target_ref'] ?? null,
            'action' => $payload['action'] ?? 'unknown',
            'status' => $payload['status'] ?? null,
            'meta' => !empty($payload['meta']) ? json_encode($payload['meta']) : null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
