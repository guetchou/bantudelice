<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    protected $table = 'notification_logs';

    protected $fillable = [
        'channel', 'recipient_type', 'recipient_id', 'recipient_address',
        'title', 'body', 'provider', 'status', 'context', 'read_at', 'archived_at',
    ];

    protected $casts = [
        'context' => 'array',
        'read_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    public function routePath(): ?string
    {
        return data_get($this->context, 'route_path');
    }

    public function orderNo(): ?string
    {
        $orderNo = data_get($this->context, 'order_no');
        if ($orderNo) {
            return (string) $orderNo;
        }

        if (preg_match('/#([A-Z0-9-]+)/i', (string) $this->body, $matches)) {
            return $matches[1];
        }

        return null;
    }

    public function module(): string
    {
        return data_get($this->context, 'module', 'general');
    }
}
