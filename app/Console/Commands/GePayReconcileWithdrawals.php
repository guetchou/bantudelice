<?php

namespace App\Console\Commands;

use App\PartnerWithdrawal;
use App\Services\GePayWithdrawalReconciler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

final class GePayReconcileWithdrawals extends Command
{
    protected $signature = 'gepay:reconcile-withdrawals {--limit=100}';
    protected $description = 'Rapprocher les retraits partenaires GePay non terminaux.';

    public function handle(GePayWithdrawalReconciler $reconciler): int
    {
        $limit = max(1, min(1000, (int) $this->option('limit')));

        $withdrawals = PartnerWithdrawal::query()
            ->where('provider', 'gepay')
            ->whereIn('status', ['created', 'reserved', 'submitted', 'pending', 'unknown'])
            ->where(function ($query) {
                $query->whereNull('reconciled_at')
                    ->orWhere('reconciled_at', '<=', now()->subMinute());
            })
            ->orderBy('reconciled_at')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $counts = [
            'checked' => 0,
            'paid' => 0,
            'failed' => 0,
            'reversed' => 0,
            'pending' => 0,
            'unknown' => 0,
            'errors' => 0,
        ];

        foreach ($withdrawals as $withdrawal) {
            try {
                $fresh = $reconciler->reconcile($withdrawal);
                $counts['checked']++;

                if (array_key_exists($fresh->status, $counts)) {
                    $counts[$fresh->status]++;
                }
            } catch (\Throwable $exception) {
                $counts['errors']++;
                Log::error('GePay withdrawal reconciliation failed', [
                    'withdrawal_id' => $withdrawal->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        $this->info(json_encode($counts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $counts['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
