<?php

namespace App\Console\Commands;

use App\Domain\Food\Enums\OrderPaymentStatus;
use App\Order;
use App\Payment;
use App\Services\AuditLogService;
use App\Services\FoodOrderStateMachineService;
use App\Services\NotificationService;
use App\Services\PaymentService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpireUnpaidAcceptedOrders extends Command
{
    protected $signature   = 'food:expire-unpaid-accepted';
    protected $description = 'Annule les commandes en accepted_awaiting_payment dont le délai de paiement est dépassé';

    public function __construct(
        protected FoodOrderStateMachineService $stateMachine,
        protected PaymentService $paymentService,
        protected AuditLogService $auditLogs
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $timeoutMinutes = (int) config('food.payment_failed_hold_timeout_minutes', 10);
        $cutoff = Carbon::now()->subMinutes($timeoutMinutes);

        $orderNos = Order::where('business_status', 'accepted_awaiting_payment')
            ->where('accepted_at', '<=', $cutoff)
            ->whereNotIn('payment_status', [
                OrderPaymentStatus::PAID->value,
                OrderPaymentStatus::EXPIRED->value,
            ])
            ->distinct()
            ->pluck('order_no');

        if ($orderNos->isEmpty()) {
            return 0;
        }

        foreach ($orderNos as $orderNo) {
            $this->expireGroup($orderNo);
        }

        $this->info("Expiré {$orderNos->count()} groupe(s) de commandes.");
        return 0;
    }

    private function expireGroup(string $orderNo): void
    {
        try {
            Order::where('order_no', $orderNo)->update([
                'payment_status' => OrderPaymentStatus::EXPIRED->value,
            ]);

            $payment = Payment::whereIn('status', ['PENDING', 'AUTHORIZED'])
                ->whereHas('order', fn ($q) => $q->where('order_no', $orderNo))
                ->first();

            if ($payment) {
                try {
                    $this->paymentService->cancelExternalPayment($payment, [
                        'reason' => 'payment_timeout_after_acceptance',
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('ExpireUnpaidAcceptedOrders: impossible d\'annuler le paiement PSP', [
                        'order_no'   => $orderNo,
                        'payment_id' => $payment->id,
                        'error'      => $e->getMessage(),
                    ]);
                }
            }

            // La notification générique de la machine d'état est neutralisée ici :
            // ce flux émet ensuite une seule notification détaillée et idempotente.
            $this->stateMachine->transitionOrderGroup($orderNo, 'cancelled', [
                'actor_type'            => 'system',
                'actor_id'              => null,
                'reason_code'           => 'payment_timeout_after_acceptance',
                'notes'                 => "Délai de paiement dépassé ({$this->getTimeoutMinutes()} min).",
                'suppress_notifications' => true,
            ]);

            $order = Order::with(['user', 'restaurant'])->where('order_no', $orderNo)->first();
            if ($order?->user_id) {
                $customerPath = NotificationService::routePath('track.order', ['orderNo' => $orderNo]);
                NotificationService::sendToUser(
                    $order->user_id,
                    'Commande annulée',
                    'Votre commande #' . $orderNo . ' a été annulée car le paiement n\'a pas été finalisé à temps.',
                    [
                        'key'        => 'orderCancelledPaymentTimeout',
                        'channel'    => 'user',
                        'module'     => 'food',
                        'type'       => 'order_cancelled',
                        'order_no'   => $orderNo,
                        'dedup_key'  => 'food:user:cancelled:payment_timeout:' . $orderNo,
                        'route_path' => $customerPath,
                        'deep_link'  => 'bantudelice://food/orders/' . $orderNo,
                        'actions'    => [
                            ['id' => 'open_order', 'label' => 'Voir la commande', 'path' => $customerPath],
                        ],
                    ]
                );
            }

            if ($order?->restaurant?->user_id) {
                $restaurantPath = NotificationService::routePath('restaurant.all_orders', ['focus' => $orderNo]);
                NotificationService::sendToUser(
                    $order->restaurant->user_id,
                    'Commande annulée automatiquement',
                    'La commande #' . $orderNo . ' a été annulée : le paiement du client n\'a pas été finalisé dans le délai imparti.',
                    [
                        'key'        => 'restaurantOrderCancelledPaymentTimeout',
                        'channel'    => 'restaurant',
                        'module'     => 'food',
                        'type'       => 'order_cancelled',
                        'order_no'   => $orderNo,
                        'dedup_key'  => 'food:restaurant:cancelled:payment_timeout:' . $orderNo,
                        'route_path' => $restaurantPath,
                        'actions'    => [
                            ['id' => 'open_order', 'label' => 'Voir la commande', 'path' => $restaurantPath],
                        ],
                    ]
                );
            }

            $this->auditLogs->record([
                'actor_type'  => 'system',
                'actor_id'    => null,
                'target_type' => 'food_order',
                'target_id'   => $order?->id,
                'target_ref'  => $orderNo,
                'action'      => 'payment_timeout_expiry',
                'status'      => 'cancelled',
                'meta'        => ['timeout_minutes' => $this->getTimeoutMinutes()],
            ]);

            Log::info('ExpireUnpaidAcceptedOrders: commande expirée', ['order_no' => $orderNo]);
        } catch (\Throwable $e) {
            Log::error('ExpireUnpaidAcceptedOrders: erreur expiration commande', [
                'order_no' => $orderNo,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    private function getTimeoutMinutes(): int
    {
        return (int) config('food.payment_failed_hold_timeout_minutes', 10);
    }
}
