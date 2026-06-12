<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NotificationLogService
{
    public function record(array $payload): void
    {
        if (!Schema::hasTable('notification_logs')) {
            return;
        }

        DB::table('notification_logs')->insert([
            'channel' => $payload['channel'] ?? 'unknown',
            'recipient_type' => $payload['recipient_type'] ?? null,
            'recipient_id' => $payload['recipient_id'] ?? null,
            'recipient_address' => $payload['recipient_address'] ?? null,
            'title' => $payload['title'] ?? null,
            'body' => $payload['body'] ?? null,
            'provider' => $payload['provider'] ?? null,
            'status' => $payload['status'] ?? 'pending',
            'context' => !empty($payload['context']) ? json_encode($payload['context']) : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
