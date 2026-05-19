<?php

namespace App\Jobs;

use App\Services\OrderChatService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendOrderChatNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;
    public $backoff = [10, 30, 90];

    public function __construct(public int $messageId)
    {
        $this->onConnection('database');
        $this->onQueue('food');
    }

    public function handle(OrderChatService $chatService): void
    {
        $chatService->broadcastMessageById($this->messageId);
    }
}
