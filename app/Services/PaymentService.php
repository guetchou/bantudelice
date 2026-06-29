<?php

namespace App\Services;

use App\Cart;
use App\Domain\Food\Enums\OrderPaymentStatus;
use App\Domain\Food\Services\OrderPricingService;
use App\Domain\Food\Services\PlaceOrderService;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Events\PaymentConfirmed;
use App\Domain\Payment\PaymentGatewayFactory;
use App\Domain\Payment\Services\PaymentAllocationService;
use App\Domain\Payment\Services\PaymentStateMachine;
use App\Order;
use App\Payment;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    public function __construct(
        protected ?FinancialEventService $financialEventService = null,
        protected ?PaymentGatewayFactory $gatewayFactory = null,
        protected ?PaymentStateMachine $paymentStateMachine = null,
        protected ?PaymentAllocationService $paymentAllocationService = null,
    ) {
    }

    protected function gateway(): PaymentGatewayFactory
    {
        return $this->gatewayFactory ?? app(PaymentGatewayFactory::class);
    }

    protected function stateMachine(): PaymentStateMachine
    {
        return $this->paymentStateMachine ?? app(PaymentStateMachine::class);
    }

    protected function allocations(): PaymentAllocationService
    {
        return $this->paymentAllocationService ?? app(PaymentAllocationService::class);
    }

    public function verifyCallbackSignature(string $provider, array $payload): bool
    {
        $normalizedProvider = $provider === 'mtn_momo' ? 'momo' : $provider;

        try {
            $adapter = $this->gateway()->for($normalizedProvider);
        } catch (\Throwable $e) {
            Log::error('Adapter introuvable pour la vérification de signature callback', [
                'provider' => $normalizedProvider,
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        return $adapter->verifySignature($payload);
    }

    public function initiateExternalPayment($payment, $cartItems, array $checkoutData = []): array
    {
        $adapter = $this->gateway()->for($payment->provider);
        $result = $adapter->initiate($payment, $checkoutData);

        if (! $result->success) {
            throw new \RuntimeException(
                $result->error ?? 'Impossible d\'initier le paiement. Veuillez réessayer.'
            );
        }

        $eventName = $result->isDemo ? 'payment_initiated_demo' : 'payment_initiated';
        $this->financialEvents()->recordForPayment($payment, $eventName, $result->meta);

        return $result->toArray();
    }

    public function prepareExternalPayment(
        $payment,
        $cartItems,
        array $checkoutData = [],
        array $baseMeta = []
    ): array {
        $payment->update([
            'meta' => $this->mergePaymentMeta(
                $payment->meta ?? [],
                $baseMeta,
                ['checkout_data' => $checkoutData]
            ),
        ]);

        $paymentInit = $this->initiateExternalPayment(
            $payment->fresh(),
            $cartItems,
            $checkoutData
        );

        $payment->update([
            'provider_reference' => $paymentInit['provider_reference'] ?? $payment->provider_reference,
            'meta' => $this->mergePaymentMeta(
                $payment->fresh()->meta ?? [],
                $paymentInit['meta'] ?? []
            ),
        ]);

        return [
            'payment' => $payment->fresh(),
            'payment_payload' => $paymentInit,
        ];
    }

    public function startManagedPayment(
        array $paymentAttributes,
        array $checkoutData = [],
        array $baseMeta = [],
        $cartItems = null
    ): array {
        $payment = Payment::create(array_merge([
            'status' => PaymentStatus::PENDING->value,
            'currency' => 'XAF',
            'meta' => [],
        ], $paymentAttributes));

        return $this->prepareExternalPayment(
            $payment,
            $cartItems ?? collect([]),
            $checkoutData,
            $baseMeta
        );
    }

    public function finalizePayPalReturn(Payment $payment, array $query = []): Payment
    {
        if ($payment->provider !== 'paypal') {
            throw new \RuntimeException('Paiement PayPal invalide.');
        }

        if ($payment->isPaid()) {
            return $payment->fresh(['order']);
        }

        if ((bool) data_get($payment->meta, 'demo', false) || ($query['demo'] ?? null) === '1') {
            $this->markPaymentAsPaid($payment, [
                'paypal_demo_return' => true,
                'query' => $query,
            ]);

            return $payment->fresh(['order']);
        }

        $providerReference = trim((string) $payment->provider_reference);
        if ($providerReference === '') {
            throw new \RuntimeException('Référence PayPal manquante.');
        }

        $adapter = $this->gateway()->for('paypal');
        $captureStatus = $adapter->capture($providerReference);

        if (! $captureStatus->isPaid()) {
            $payment->update([
                'meta' => array_replace_recursive($payment->meta ?? [], $captureStatus->meta),
            ]);
            throw new \RuntimeException(
                $captureStatus->failureReason ?? 'Le paiement PayPal n\'a pas été validé.'
            );
        }

        $this->markPaymentAsPaid($payment, array_merge(
            ['paypal_return' => $query],
            $captureStatus->meta
        ));

        return $payment->fresh(['order']);
    }

    public function cancelExternalPayment(Payment $payment, array $context = []): Payment
    {
        if (! in_array($payment->canonicalStatus(), [
            PaymentStatus::INITIATED,
            PaymentStatus::PENDING,
            PaymentStatus::PROCESSING,
        ], true)) {
            return $payment->fresh();
        }

        $cancelled = $this->stateMachine()->transition(
            $payment,
            PaymentStatus::CANCELLED,
            [
                'cancelled_at' => now()->toIso8601String(),
                'cancel_context' => $context,
            ],
            'customer_or_system_cancellation'
        );

        $this->financialEvents()->recordForPayment(
            $cancelled,
            'payment_cancelled',
            $context
        );

        return $cancelled;
    }

    public function handleCallback(string $provider, array $payload): void
    {
        $normalizedProvider = $provider === 'mtn_momo' ? 'momo' : $provider;
        Log::info('Payment callback reçu', [
            'provider' => $normalizedProvider,
            'payload' => $payload,
        ]);

        $providerReference = $payload['reference']
            ?? $payload['referenceId']
            ?? $payload['transaction_id']
            ?? $payload['id']
            ?? $payload['external_id']
            ?? $payload['externalId']
            ?? null;

        if (! $providerReference) {
            throw new \RuntimeException('Référence de paiement manquante dans le callback');
        }

        $payment = Payment::query()
            ->where('provider_reference', $providerReference)
            ->whereIn('provider', $this->providerAliases($normalizedProvider))
            ->latest('id')
            ->first();

        if (! $payment) {
            throw new \RuntimeException(
                'Paiement non trouvé pour la référence: ' . $providerReference
            );
        }

        $adapter = $this->gateway()->for($normalizedProvider);
        if (! $adapter->verifySignature($payload)) {
            throw new \RuntimeException('Signature de callback invalide');
        }

        $gatewayStatus = $adapter->handleCallback($payload);
        $callbackMeta = [
            'callback' => $payload,
            'provider_status' => $gatewayStatus->providerStatus,
            'last_callback_at' => now()->toIso8601String(),
        ];

        if ($gatewayStatus->isPaid()) {
            $this->assertCallbackAmount($payment, $payload, $normalizedProvider);

            if (! $payment->canonicalStatus()->isUnresolved()
                && ! $payment->isPaid()) {
                $this->recordTerminalInconsistency(
                    $payment,
                    'provider_reports_paid_after_terminal_local_status',
                    $callbackMeta
                );

                return;
            }

            $this->markPaymentAsPaid($payment, array_merge($payload, [
                'provider_status' => $gatewayStatus->providerStatus,
            ]));

            return;
        }

        if ($gatewayStatus->isFailed()) {
            if ($payment->isPaid()) {
                $this->recordTerminalInconsistency(
                    $payment,
                    'provider_reports_failed_after_local_paid',
                    array_merge($callbackMeta, $gatewayStatus->meta)
                );

                return;
            }

            if (! $payment->canonicalStatus()->isUnresolved()) {
                $payment->update([
                    'meta' => $this->mergePaymentMeta($payment->meta ?? [], $callbackMeta),
                ]);

                return;
            }

            $failureMeta = array_filter([
                'failure_reason' => $gatewayStatus->failureReason,
                'failure_action' => $gatewayStatus->failureAction,
            ]);

            $failed = $this->stateMachine()->transition(
                $payment,
                PaymentStatus::FAILED,
                array_merge($callbackMeta, [
                    'failed_at' => now()->toIso8601String(),
                ], $failureMeta, $gatewayStatus->meta),
                'provider_callback_failed'
            );

            $this->propagateFailedAttemptToOrder($failed);
            $this->financialEvents()->recordForPayment($failed, 'payment_failed', [
                'callback' => $payload,
                'provider_status' => $gatewayStatus->providerStatus,
            ]);

            return;
        }

        if ($gatewayStatus->status === 'UNKNOWN') {
            if ($payment->canonicalStatus()->isUnresolved()
                && ! $payment->isUnknown()) {
                $payment = $this->stateMachine()->transition(
                    $payment,
                    PaymentStatus::UNKNOWN,
                    $callbackMeta,
                    'provider_callback_unknown'
                );
            } else {
                $payment->update([
                    'meta' => $this->mergePaymentMeta($payment->meta ?? [], $callbackMeta),
                ]);
            }

            $this->financialEvents()->recordForPayment(
                $payment->fresh(),
                'payment_callback_unknown',
                [
                    'callback' => $payload,
                    'provider_status' => $gatewayStatus->providerStatus,
                ]
            );

            return;
        }

        if ($payment->canonicalStatus() === PaymentStatus::INITIATED) {
            $payment = $this->stateMachine()->transition(
                $payment,
                PaymentStatus::PENDING,
                $callbackMeta,
                'provider_callback_pending'
            );
        } else {
            $payment->update([
                'meta' => $this->mergePaymentMeta($payment->meta ?? [], $callbackMeta),
            ]);
        }

        $this->financialEvents()->recordForPayment(
            $payment->fresh(),
            'payment_callback_pending',
            [
                'callback' => $payload,
                'provider_status' => $gatewayStatus->providerStatus,
            ]
        );
    }

    protected function mergePaymentMeta(array ...$segments): array
    {
        $merged = [];

        foreach ($segments as $segment) {
            if (! empty($segment)) {
                $merged = array_replace_recursive($merged, $segment);
            }
        }

        return $merged;
    }

    public function markPaymentAsPaid($payment, array $callbackData = []): void
    {
        DB::transaction(function () use ($payment, $callbackData) {
            $locked = Payment::query()
                ->lockForUpdate()
                ->findOrFail($payment->id);

            if ($locked->isPaid()) {
                $this->allocations()->allocateConfirmedPayment($locked);

                return;
            }

            if (! $locked->canonicalStatus()->isUnresolved()) {
                throw new \DomainException(
                    'Un paiement terminal ne peut pas être confirmé automatiquement: '
                    . $locked->status
                );
            }

            $confirmed = $this->stateMachine()->transition(
                $locked,
                PaymentStatus::PAID,
                [
                    'callback' => $callbackData,
                    'paid_at' => now()->toIso8601String(),
                ],
                'authoritative_provider_confirmation'
            );

            $allocation = $this->allocations()->allocateConfirmedPayment($confirmed);

            $this->financialEvents()->recordForPayment($confirmed, 'payment_paid', [
                'callback' => $callbackData,
                'allocation' => $allocation,
            ]);

            event(new PaymentConfirmed($confirmed->fresh()));
        });
    }

    protected function financialEvents(): FinancialEventService
    {
        return $this->financialEventService ?? app(FinancialEventService::class);
    }

    private function providerAliases(string $provider): array
    {
        return match (strtolower(trim($provider))) {
            'momo', 'mtn', 'mtn_momo' => ['momo', 'mtn', 'mtn_momo'],
            'airtel', 'airtel_money' => ['airtel', 'airtel_money'],
            default => [strtolower(trim($provider))],
        };
    }

    private function assertCallbackAmount(
        Payment $payment,
        array $payload,
        string $provider
    ): void {
        $callbackAmount = $payload['amount']
            ?? $payload['amountTransaction']
            ?? $payload['transaction_amount']
            ?? $payload['value']
            ?? null;

        if ($callbackAmount === null) {
            return;
        }

        $received = (float) $callbackAmount;
        $expected = (float) $payment->amount;

        if (abs($received - $expected) <= 1.0) {
            return;
        }

        Log::critical('Callback montant incohérent détecté', [
            'payment_id' => $payment->id,
            'provider' => $provider,
            'expected' => $expected,
            'received' => $received,
            'payload' => $payload,
        ]);

        throw new \RuntimeException(
            'Montant du callback invalide : '
            . $received
            . ' FCFA reçu, '
            . $expected
            . ' FCFA attendu.'
        );
    }

    private function recordTerminalInconsistency(
        Payment $payment,
        string $reason,
        array $context
    ): void {
        $payment->update([
            'meta' => $this->mergePaymentMeta($payment->meta ?? [], [
                'reconciliation_inconsistency' => [
                    'reason' => $reason,
                    'detected_at' => now()->toIso8601String(),
                    'context' => $context,
                ],
            ]),
        ]);

        $this->financialEvents()->recordForPayment(
            $payment->fresh(),
            'payment_inconsistent',
            [
                'reason' => $reason,
                'context' => $context,
            ]
        );
    }

    private function propagateFailedAttemptToOrder(Payment $payment): void
    {
        if (! $payment->order_id) {
            return;
        }

        $order = Order::find($payment->order_id);
        if (! $order) {
            return;
        }

        $funding = $this->allocations()
            ->fundingStatusForFoodOrderGroup((string) $order->order_no);
        if ($funding['fully_funded']) {
            return;
        }

        Order::where('order_no', $order->order_no)->update([
            'payment_status' => OrderPaymentStatus::FAILED->value,
        ]);

        try {
            if ($order->user_id) {
                NotificationService::sendToUser(
                    $order->user_id,
                    'Paiement échoué',
                    'Le paiement de votre commande #'
                        . $order->order_no
                        . ' a échoué. Vous pouvez réessayer sans créer une nouvelle commande.',
                    [
                        'key' => 'paymentFailed',
                        'channel' => 'user',
                        'module' => 'food',
                        'type' => 'payment_failed',
                    ]
                );
            }
        } catch (\Throwable $e) {
            Log::warning('PaymentService: erreur notification échec paiement', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public static function createOrderFromCart($orderData)
    {
        $user = auth()->user();
        $userId = $user->id;
        $cartItems = Cart::where('user_id', $userId)->get();

        if ($cartItems->isEmpty()) {
            throw new Exception('Le panier est vide');
        }

        $totals = app(OrderPricingService::class)->calculate($cartItems, $orderData);

        try {
            $orderNo = app(PlaceOrderService::class)->placeFromCart($user, $cartItems, [
                'offer_discount' => (float) ($totals['discount'] ?? 0),
                'tax' => (float) ($totals['tax'] ?? 0),
                'delivery_charges' => (float) ($totals['delivery_fee'] ?? 0),
                'sub_total' => (float) ($totals['sub_total'] ?? 0),
                'total' => (float) ($totals['total'] ?? 0),
                'driver_tip' => (float) ($totals['driver_tip'] ?? 0),
                'delivery_address' => $orderData['delivery_address'],
                'd_lat' => $orderData['d_lat'] ?? null,
                'd_lng' => $orderData['d_lng'] ?? null,
                'payment_method' => $orderData['payment_method'] ?? 'cash',
                'payment_status' => 'pending',
                'status' => 'pending',
                'ordered_time' => now(),
                'delivered_time' => null,
            ]);

            Cart::where('user_id', $userId)->delete();

            Log::info('Order created successfully', ['order_no' => $orderNo]);

            return $orderNo;
        } catch (Exception $e) {
            Log::error('Order creation failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public static function calculateTotals($cartItems, $options = [])
    {
        return app(OrderPricingService::class)->calculate($cartItems, (array) $options);
    }
}
