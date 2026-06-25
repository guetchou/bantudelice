<?php

namespace App\Console\Commands;

use App\Order;
use App\Services\AuditLogService;
use App\Services\FoodOrderFinanceService;
use App\Services\FoodOrderStateMachineService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExpireUnacceptedFoodOrders extends Command
{
    protected $signature = 'food:expire-unaccepted {--limit=100 : Nombre maximal de commandes à traiter}';

    protected $description = 'Annule les commandes repas ignorées par le restaurant après le délai d’acceptation';

    public function __construct(
        protected FoodOrderFinanceService $finance,
        protected FoodOrderStateMachineService $stateMachine,
        protected AuditLogService $auditLogs
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $timeoutMinutes = max(1, (int) config('food.restaurant_acceptance_timeout_minutes', 5));
        $limit = max(1, min(500, (int) $this->option('limit')));
        $cutoff = now()->subMinutes($timeoutMinutes);

        $orderNos = Order::query()
            ->where('business_status', 'pending_restaurant_acceptance')
            ->where('created_at', '<=', $cutoff)
            ->orderBy('created_at')
            ->distinct()
            ->limit($limit)
            ->pluck('order_no');

        $expired = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($orderNos as $orderNo) {
            try {
                $result = DB::transaction(function () use ($orderNo, $timeoutMinutes): bool {
                    $order = Order::query()
                        ->where('order_no', $orderNo)
                        ->orderBy('id')
                        ->lockForUpdate()
                        ->first();

                    if (! $order) {
                        return false;
                    }

                    $currentStatus = $this->stateMachine->resolveCurrentBusinessStatus($order);
                    if ($currentStatus !== 'pending_restaurant_acceptance') {
                        return false;
                    }

                    Order::where('order_no', $orderNo)->update([
                        'technical_status' => 'restaurant_timeout',
                    ]);

                    $this->finance->cancelOrderGroup($orderNo, [
                        'actor_type' => 'system',
                        'actor_id' => null,
                        'reason_code' => 'restaurant_timeout',
                        'notes' => "Commande annulée automatiquement : aucune réponse du restaurant après {$timeoutMinutes} minute(s).",
                    ]);

                    $this->auditLogs->record([
                        'actor_type' => 'system',
                        'actor_id' => null,
                        'target_type' => 'food_order',
                        'target_id' => $order->id,
                        'target_ref' => $orderNo,
                        'action' => 'restaurant_acceptance_timeout',
                        'status' => 'cancelled',
                        'meta' => [
                            'timeout_minutes' => $timeoutMinutes,
                            'restaurant_id' => $order->restaurant_id,
                            'user_id' => $order->user_id,
                        ],
                    ]);

                    return true;
                }, 3);

                if ($result) {
                    $expired++;
                } else {
                    $skipped++;
                }
            } catch (\Throwable $e) {
                $failed++;
                Log::error('ExpireUnacceptedFoodOrders: échec expiration commande', [
                    'order_no' => $orderNo,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('ExpireUnacceptedFoodOrders: exécution terminée', [
            'timeout_minutes' => $timeoutMinutes,
            'candidates' => $orderNos->count(),
            'expired' => $expired,
            'skipped' => $skipped,
            'failed' => $failed,
        ]);

        $this->info("Commandes expirées: {$expired}; ignorées: {$skipped}; erreurs: {$failed}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
