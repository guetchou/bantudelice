<?php

namespace App\Console\Commands;

use App\Domain\GePay\Enums\TransactionStatus;
use App\Domain\GePay\Models\GePayTransaction;
use App\Domain\GePay\Services\GePayGateway;
use Illuminate\Console\Command;

class GePayReconcile extends Command
{
    protected $signature = 'gepay:reconcile {--limit=100}';
    protected $description = 'Rapprocher les transactions GePay non terminales avec les fournisseurs.';

    public function handle(GePayGateway $gateway): int
    {
        $limit = max(1, min(1000, (int) $this->option('limit')));
        $transactions = GePayTransaction::query()
            ->whereIn('status', [
                TransactionStatus::SUBMITTED->value,
                TransactionStatus::PENDING->value,
                TransactionStatus::UNKNOWN->value,
            ])
            ->whereNotNull('provider_reference')
            ->orderBy('last_checked_at')
            ->limit($limit)
            ->get();

        $counts = ['checked' => 0, 'successful' => 0, 'failed' => 0, 'pending' => 0, 'unknown' => 0];
        foreach ($transactions as $transaction) {
            $fresh = $gateway->refresh($transaction);
            $counts['checked']++;
            $key = $fresh->status->value;
            if (array_key_exists($key, $counts)) {
                $counts[$key]++;
            }
        }

        $this->info(json_encode($counts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return self::SUCCESS;
    }
}
