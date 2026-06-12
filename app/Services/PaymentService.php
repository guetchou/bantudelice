<?php

namespace App\Services;

use App\Cart;
use App\Domain\Food\Services\OrderPricingService;
use App\Domain\Food\Services\PlaceOrderService;
use App\Domain\Payment\Events\PaymentConfirmed;
use App\Domain\Payment\PaymentGatewayFactory;
use App\Order;
use App\Payment;
use App\Jobs\AutoAssignDeliveryJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Exception;

class PaymentService
{
    public function __construct(
        protected ?FinancialEventService $financialEventService = null,
        protected ?PaymentGatewayFactory $gatewayFactory = null,
    ) {}

    protected function gateway(): PaymentGatewayFactory
    {
        return $this->gatewayFactory ?? app(PaymentGatewayFactory::class);
    }

    /**
     * Initier un paiement externe via le gateway adapter approprié.
     */
    public function initiateExternalPayment($payment, $cartItems, array $checkoutData = []): array
    {
        $adapter = $this->gateway()->for($payment->provider);
        $result  = $adapter->initiate($payment, $checkoutData);

        if (! $result->success) {
            throw new \RuntimeException(
                $result->error ?? 'Impossible d\'initier le paiement. Veuillez réessayer.'
            );
        }

        $eventName = $result->isDemo ? 'payment_initiated_demo' : 'payment_initiated';
        $this->financialEvents()->recordForPayment($payment, $eventName, $result->meta);

        return $result->toArray();
    }

    public function prepareExternalPayment($payment, $cartItems, array $checkoutData = [], array $baseMeta = []): array
    {
        $payment->update([
            'meta' => $this->mergePaymentMeta(
                $payment->meta ?? [],
                $baseMeta,
                ['checkout_data' => $checkoutData]
            ),
        ]);

        $paymentInit = $this->initiateExternalPayment($payment->fresh(), $cartItems, $checkoutData);

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

    public function startManagedPayment(array $paymentAttributes, array $checkoutData = [], array $baseMeta = [], $cartItems = null): array
    {
        $payment = Payment::create(array_merge([
            'status' => 'PENDING',
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

        if ($payment->status === 'PAID') {
            return $payment->fresh(['order']);
        }

        if ((bool) data_get($payment->meta, 'demo', false) || ($query['demo'] ?? null) === '1') {
            $this->markPaymentAsPaid($payment, ['paypal_demo_return' => true, 'query' => $query]);
            return $payment->fresh(['order']);
        }

        $providerReference = trim((string) $payment->provider_reference);
        if ($providerReference === '') {
            throw new \RuntimeException('Référence PayPal manquante.');
        }

        /** @var \App\Domain\Payment\Adapters\PayPalAdapter $adapter */
        $adapter       = $this->gateway()->for('paypal');
        $captureStatus = $adapter->capture($providerReference);

        if (! $captureStatus->isPaid()) {
            $payment->update([
                'meta' => array_merge($payment->meta ?? [], $captureStatus->meta),
            ]);
            throw new \RuntimeException($captureStatus->failureReason ?? 'Le paiement PayPal n\'a pas été validé.');
        }

        $this->markPaymentAsPaid($payment, array_merge(
            ['paypal_return' => $query],
            $captureStatus->meta
        ));

        return $payment->fresh(['order']);
    }

    public function cancelExternalPayment(Payment $payment, array $context = []): Payment
    {
        if ($payment->status !== 'PENDING') {
            return $payment->fresh();
        }

        $payment->update([
            'status' => 'CANCELLED',
            'meta' => array_merge($payment->meta ?? [], [
                'cancelled_at' => now()->toIso8601String(),
                'cancel_context' => $context,
            ]),
        ]);

        $this->financialEvents()->recordForPayment($payment->fresh(), 'payment_cancelled', $context);

        return $payment->fresh();
    }

    /**
     * Gérer le callback d'un PSP
     * 
     * @param string $provider
     * @param array $payload
     * @return void
     */
    public function handleCallback(string $provider, array $payload): void
    {
        $normalizedProvider = $provider === 'mtn_momo' ? 'momo' : $provider;
        Log::info('Payment callback reçu', ['provider' => $normalizedProvider, 'payload' => $payload]);
        
        // 1. Retrouver le Payment à partir de la référence
        $providerRef = $payload['reference'] ?? $payload['referenceId'] ?? $payload['transaction_id'] ?? $payload['id'] ?? $payload['external_id'] ?? $payload['externalId'] ?? null;
        
        if (!$providerRef) {
            Log::error('Payment callback sans référence', ['provider' => $normalizedProvider, 'payload' => $payload]);
            throw new \RuntimeException('Référence de paiement manquante dans le callback');
        }
        
        $payment = Payment::where('provider_reference', $providerRef)
            ->where('provider', $normalizedProvider)
            ->first();
        
        if (!$payment) {
            Log::error('Payment non trouvé pour la référence', [
                'provider' => $normalizedProvider,
                'reference' => $providerRef,
                'payload' => $payload
            ]);
            throw new \RuntimeException('Paiement non trouvé pour la référence: ' . $providerRef);
        }

        // 2. Vérifier la signature via l'adapter
        $adapter = $this->gateway()->for($normalizedProvider);

        if (! $adapter->verifySignature($payload)) {
            Log::error('Signature callback invalide', [
                'provider'   => $normalizedProvider,
                'payment_id' => $payment->id,
            ]);
            throw new \RuntimeException('Signature de callback invalide');
        }

        // 3. Interpréter le statut via l'adapter
        $gatewayStatus = $adapter->handleCallback($payload);

        $payment->update([
            'meta' => array_merge($payment->meta ?? [], [
                'callback'         => $payload,
                'provider_status'  => $gatewayStatus->providerStatus,
                'last_callback_at' => now()->toIso8601String(),
            ]),
        ]);

        // 4. Appliquer la transition de statut
        if ($gatewayStatus->isPaid()) {
            // Vérification du montant : le callback doit correspondre au paiement attendu
            $callbackAmount = $payload['amount'] ?? $payload['amountTransaction'] ?? $payload['transaction_amount'] ?? $payload['value'] ?? null;
            if ($callbackAmount !== null) {
                $received = (float) $callbackAmount;
                $expected = (float) $payment->amount;
                if (abs($received - $expected) > 1.0) { // tolérance 1 FCFA pour arrondis
                    Log::critical('Callback montant frauduleux détecté', [
                        'payment_id' => $payment->id,
                        'provider'   => $normalizedProvider,
                        'expected'   => $expected,
                        'received'   => $received,
                        'payload'    => $payload,
                    ]);
                    throw new \RuntimeException('Montant du callback invalide : ' . $received . ' FCFA reçu, ' . $expected . ' FCFA attendu.');
                }
            }

            $this->markPaymentAsPaid($payment, array_merge($payload, [
                'provider_status' => $gatewayStatus->providerStatus,
            ]));
            Log::info('Paiement marqué comme payé via adapter', [
                'payment_id' => $payment->id,
                'provider'   => $normalizedProvider,
            ]);
            return;
        }

        if ($gatewayStatus->isFailed()) {
            $failureMeta = array_filter([
                'failure_reason' => $gatewayStatus->failureReason,
                'failure_action' => $gatewayStatus->failureAction,
            ]);

            $payment->update([
                'status' => 'FAILED',
                'meta'   => array_merge($payment->meta ?? [], [
                    'failed_at' => now()->toIso8601String(),
                ], $failureMeta, $gatewayStatus->meta),
            ]);
            $this->financialEvents()->recordForPayment($payment->fresh(), 'payment_failed', [
                'callback'        => $payload,
                'provider_status' => $gatewayStatus->providerStatus,
            ]);
            Log::info('Paiement marqué comme échoué via adapter', [
                'payment_id' => $payment->id,
                'provider'   => $normalizedProvider,
                'status'     => $gatewayStatus->providerStatus,
            ]);
            return;
        }

        // PENDING ou UNKNOWN
        $this->financialEvents()->recordForPayment($payment->fresh(), 'payment_callback_pending', [
            'callback'        => $payload,
            'provider_status' => $gatewayStatus->providerStatus,
        ]);
        Log::warning('Statut de paiement non terminal après callback', [
            'payment_id'      => $payment->id,
            'provider'        => $normalizedProvider,
            'gateway_status'  => $gatewayStatus->status,
            'provider_status' => $gatewayStatus->providerStatus,
        ]);
    }

    protected function mergePaymentMeta(array ...$segments): array
    {
        $merged = [];

        foreach ($segments as $segment) {
            if (!empty($segment)) {
                $merged = array_merge($merged, $segment);
            }
        }

        return $merged;
    }

    public function markPaymentAsPaid($payment, array $callbackData = []): void
    {
        DB::transaction(function () use ($payment, $callbackData) {
            $payment->update([
                'status' => 'PAID',
                'meta'   => array_merge($payment->meta ?? [], [
                    'callback' => $callbackData,
                    'paid_at'  => now()->toIso8601String(),
                ]),
            ]);

            $this->financialEvents()->recordForPayment($payment->fresh(), 'payment_paid', [
                'callback' => $callbackData,
            ]);

            event(new PaymentConfirmed($payment->fresh()));
        });
    }

    protected function financialEvents(): FinancialEventService
    {
        return $this->financialEventService ?? app(FinancialEventService::class);
    }

    /**
     * Créer une commande depuis le panier (méthode statique conservée pour compatibilité)
     * 
     * @param array $orderData
     * @return string Numéro de commande
     */
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
    
    /**
     * Calculer les totaux d'une commande
     * 
     * @param array $cartItems
     * @param array $options
     * @return array
     */
    public static function calculateTotals($cartItems, $options = [])
    {
        return app(OrderPricingService::class)->calculate($cartItems, (array) $options);
    }

}
