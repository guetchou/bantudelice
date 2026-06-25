<?php

namespace App\Console\Commands;

use App\Domain\Food\Services\WorkflowOrderAcceptanceService;
use App\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReleaseScheduledFoodOrders extends Command
{
    protected $signature = 'food:release-scheduled {--limit=100 : Nombre maximal de commandes à libérer}';

    protected $description = 'Déclenche les commandes programmées lorsque leur fenêtre de préparation commence';

    public function __construct(
        protected WorkflowOrderAcceptanceService $acceptance
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $leadMinutes = max(0, (int) config('food.scheduled_preparation_lead_minutes', 30));
        $limit = max(1, min(500, (int) $this->option('limit')));
        $releaseBefore = now()->addMinutes($leadMinutes);

        $orderNos = Order::query()
            ->select('order_no')
            ->where('business_status', 'accepted_scheduled')
            ->whereNotNull('scheduled_date')
            ->where('scheduled_date', '<=', $releaseBefore)
            ->groupBy('order_no')
            ->orderByRaw('MIN(scheduled_date) ASC')
            ->limit($limit)
            ->pluck('order_no');

        $released = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($orderNos as $orderNo) {
            try {
                $order = Order::where('order_no', $orderNo)->orderBy('id')->first();
                if (! $order) {
                    $skipped++;
                    continue;
                }

                $this->acceptance->releaseScheduled($order);
                $released++;
            } catch (\Throwable $e) {
                $failed++;
                Log::error('ReleaseScheduledFoodOrders: échec de libération', [
                    'order_no' => $orderNo,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('ReleaseScheduledFoodOrders: exécution terminée', [
            'lead_minutes' => $leadMinutes,
            'candidates' => $orderNos->count(),
            'released' => $released,
            'skipped' => $skipped,
            'failed' => $failed,
        ]);

        $this->info("Programmées libérées: {$released}; ignorées: {$skipped}; erreurs: {$failed}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
