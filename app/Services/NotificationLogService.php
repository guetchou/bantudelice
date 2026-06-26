<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NotificationLogService
{
    public function record(array $payload): void
    {
        if (! Schema::hasTable('notification_logs')) {
            return;
        }

        $context = is_array($payload['context'] ?? null)
            ? $payload['context']
            : [];

        if ($this->isDuplicateEvent($payload, $context)) {
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
            'context' => $context !== [] ? json_encode($context) : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function isDuplicateEvent(array $payload, array $context): bool
    {
        $dedupKey = trim((string) ($context['dedup_key'] ?? ''));
        if ($dedupKey === '') {
            return false;
        }

        $query = DB::table('notification_logs')
            ->where('channel', $payload['channel'] ?? 'unknown')
            ->where('recipient_type', $payload['recipient_type'] ?? null)
            ->where('created_at', '>=', now()->subMinutes(10))
            ->where('context', 'like', '%"dedup_key":"' . addcslashes($dedupKey, '%_\\') . '"%');

        if (($payload['recipient_id'] ?? null) === null) {
            $query->whereNull('recipient_id');
        } else {
            $query->where('recipient_id', $payload['recipient_id']);
        }

        return $query->exists();
    }
}
