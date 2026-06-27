<?php

namespace App\Jobs;

use App\Services\PartnerWithdrawalService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ReconcileWithdrawalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $backoff = 120; // 2 min between retries

    public function __construct(public readonly int $withdrawalId) {}

    public function handle(PartnerWithdrawalService $service): void
    {
        $service->reconcile($this->withdrawalId);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('ReconcileWithdrawalJob failed', [
            'withdrawal_id' => $this->withdrawalId,
            'error'         => $e->getMessage(),
        ]);
    }
}
