<?php

namespace App\Services;

use App\Payment;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MobileMoneyBridgeService
{
    public function __construct(
        protected PaymentService $paymentService,
        protected PaymentExperienceService $paymentExperienceService,
        protected PaymentReconciliationService $paymentReconciliationService
    ) {}

    public function initiate(array $payload, array $bridgeClient): array
    {
        $callbackUrl = trim((string) ($payload['callback_url'] ?? ''));
        if ($callbackUrl !== '' && !$this->isAllowedCallbackUrl($callbackUrl)) {
            throw ValidationException::withMessages([
                'callback_url' => ['Cette URL de callback n’est pas autorisée. Utilisez une URL HTTPS publique.'],
            ]);
        }

        $existing = $this->findByExternalReference(
            (string) $payload['external_reference'],
            (string) $bridgeClient['key']
        );

        if ($existing) {
            return $this->buildPaymentPayload(
                $existing,
                false,
                'Paiement déjà existant pour cette référence externe.'
            );
        }

        $user = $this->resolveBridgeUser();
        $operator = $payload['operator'] === 'auto'
            ? DisbursementService::detectOperator((string) $payload['phone'])
            : $payload['operator'];

        if (!in_array($operator, ['mtn', 'airtel'], true)) {
            throw ValidationException::withMessages([
                'phone' => ['Impossible de déterminer un opérateur Mobile Money compatible pour ce numéro.'],
            ]);
        }

        $provider = $operator === 'airtel' ? 'airtel' : 'momo';

        $result = $this->paymentService->startManagedPayment(
            [
                'user_id' => $user->id,
                'provider' => $provider,
                'amount' => (int) $payload['amount'],
                'currency' => strtoupper((string) ($payload['currency'] ?? 'XAF')),
                'meta' => [
                    'bridge' => [
                        'external_reference' => (string) $payload['external_reference'],
                        'gateway_reference' => null,
                        'callback_url' => $callbackUrl !== '' ? $callbackUrl : null,
                        'client_key' => $bridgeClient['key'],
                        'client_name' => $bridgeClient['name'],
                        'metadata' => $payload['metadata'] ?? [],
                    ],
                    'requested_operator' => $payload['operator'],
                    'resolved_operator' => $operator,
                ],
            ],
            [
                'phone' => (string) $payload['phone'],
                'source' => 'mobile_money_bridge',
                'external_reference' => (string) $payload['external_reference'],
                'operator' => $operator,
            ],
            [
                'bridge' => [
                    'customer_name' => $payload['customer_name'] ?? null,
                ],
            ]
        );

        /** @var Payment $payment */
        $payment = $result['payment']->fresh();
        $gatewayReference = $this->gatewayReference($payment);
        $meta = $payment->meta ?? [];
        data_set($meta, 'bridge.gateway_reference', $gatewayReference);
        $payment->update(['meta' => $meta]);
        $payment = $payment->fresh();

        $response = $this->buildPaymentPayload($payment, true, 'Paiement Mobile Money initialisé.');
        $this->notifyCallbackIfNeeded($payment, 'initiated');

        return $response;
    }

    public function status(string $gatewayReference, array $bridgeClient, bool $reconcile = false): array
    {
        $payment = $this->resolvePayment($gatewayReference, $bridgeClient);
        $reconciliation = null;

        if ($reconcile && strtoupper((string) $payment->status) === 'PENDING') {
            $reconciliation = $this->paymentReconciliationService->reconcile($payment);
            $payment = $payment->fresh();
        }

        $this->notifyCallbackIfNeeded($payment, 'status');

        return $this->buildPaymentPayload($payment, false, null, $reconciliation);
    }

    public function reconcile(string $gatewayReference, array $bridgeClient): array
    {
        $payment = $this->resolvePayment($gatewayReference, $bridgeClient);
        $reconciliation = $this->paymentReconciliationService->reconcile($payment);
        $payment = $payment->fresh();
        $this->notifyCallbackIfNeeded($payment, 'reconcile');

        return $this->buildPaymentPayload(
            $payment,
            false,
            $reconciliation['message'] ?? null,
            $reconciliation
        );
    }

    protected function resolveBridgeUser(): User
    {
        $config = config('mobile-money-bridge.service_user');

        return User::firstOrCreate(
            ['email' => $config['email']],
            [
                'name' => $config['name'],
                'phone' => $config['phone'],
                'type' => 'user',
                'password' => Hash::make(Str::random(40)),
                'email_verified_at' => now(),
            ]
        );
    }

    protected function resolvePayment(string $gatewayReference, array $bridgeClient): Payment
    {
        $payment = null;
        $clientKey = (string) ($bridgeClient['key'] ?? '');

        if (preg_match('/^MMB-(\d+)$/', strtoupper($gatewayReference), $matches)) {
            $payment = Payment::find((int) $matches[1]);
            if ($payment && data_get($payment->meta, 'bridge.client_key') !== $clientKey) {
                $payment = null;
            }
        }

        if (!$payment) {
            $payment = $this->findByExternalReference($gatewayReference, $clientKey);
        }

        abort_unless($payment, 404, 'Paiement passerelle introuvable.');

        return $payment;
    }

    protected function findByExternalReference(string $externalReference, string $clientKey): ?Payment
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return Payment::query()
                ->latest('id')
                ->get()
                ->first(function (Payment $payment) use ($externalReference, $clientKey) {
                    return (string) data_get($payment->meta, 'bridge.external_reference') === $externalReference
                        && (string) data_get($payment->meta, 'bridge.client_key') === $clientKey;
                });
        }

        return Payment::query()
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(meta, '$.bridge.external_reference')) = ?", [$externalReference])
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(meta, '$.bridge.client_key')) = ?", [$clientKey])
            ->latest('id')
            ->first();
    }

    protected function gatewayReference(Payment $payment): string
    {
        return 'MMB-' . str_pad((string) $payment->id, 8, '0', STR_PAD_LEFT);
    }

    protected function buildPaymentPayload(
        Payment $payment,
        bool $created = false,
        ?string $message = null,
        ?array $reconciliation = null
    ): array {
        $payment->refresh();
        $experience = $this->paymentExperienceService->describe($payment);
        $bridge = data_get($payment->meta, 'bridge', []);

        return [
            'status' => true,
            'created' => $created,
            'message' => $message,
            'payment' => [
                'gateway_reference' => data_get($bridge, 'gateway_reference') ?: $this->gatewayReference($payment),
                'external_reference' => data_get($bridge, 'external_reference'),
                'provider' => $payment->provider,
                'provider_reference' => $payment->provider_reference,
                'status' => $payment->status,
                'amount' => (int) $payment->amount,
                'currency' => $payment->currency,
                'phone' => data_get($payment->meta, 'phone')
                    ?? data_get($payment->meta, 'checkout_data.phone'),
                'operator' => data_get($payment->meta, 'operator')
                    ?? data_get($payment->meta, 'resolved_operator')
                    ?? ($payment->provider === 'airtel' ? 'airtel' : 'mtn'),
                'created_at' => optional($payment->created_at)->toIso8601String(),
                'updated_at' => optional($payment->updated_at)->toIso8601String(),
            ],
            'payment_experience' => $experience,
            'reconciliation' => $reconciliation,
        ];
    }

    protected function notifyCallbackIfNeeded(Payment $payment, string $event): void
    {
        $callbackUrl = trim((string) data_get($payment->meta, 'bridge.callback_url', ''));
        if ($callbackUrl === '' || !$this->isAllowedCallbackUrl($callbackUrl)) {
            return;
        }

        $status = strtoupper((string) $payment->status);
        if (!in_array($status, ['PAID', 'FAILED', 'CANCELLED'], true) && $event !== 'initiated') {
            return;
        }

        $alreadyNotified = (string) data_get($payment->meta, 'bridge.last_notified_status', '');
        if ($alreadyNotified === $status) {
            return;
        }

        $payload = [
            'event' => 'mobile_money.payment.' . strtolower($event),
            'payment' => $this->buildPaymentPayload($payment)['payment'],
        ];

        try {
            $timestamp = (string) now()->timestamp;
            $encodedPayload = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $secret = $this->resolveBridgeClientSecret($payment);
            $headers = [
                'X-BantuDelice-Timestamp' => $timestamp,
            ];

            if ($secret !== null && $encodedPayload !== false) {
                $headers['X-BantuDelice-Signature'] = hash_hmac(
                    'sha256',
                    $timestamp . '.' . $encodedPayload,
                    $secret
                );
            }

            $response = Http::timeout(10)
                ->withHeaders($headers)
                ->post($callbackUrl, $payload);

            if (!$response->successful()) {
                return;
            }

            $meta = $payment->meta ?? [];
            data_set($meta, 'bridge.last_notified_status', $status);
            data_set($meta, 'bridge.last_callback_at', Carbon::now()->toIso8601String());
            $payment->update(['meta' => $meta]);
        } catch (\Throwable $e) {
            // Notification passive : le flux de paiement ne doit pas échouer.
        }
    }

    private function resolveBridgeClientSecret(Payment $payment): ?string
    {
        $clientKey = (string) data_get($payment->meta, 'bridge.client_key', '');
        $secret = (string) data_get(config('mobile-money-bridge.clients', []), $clientKey . '.secret', '');

        return $secret !== '' ? $secret : null;
    }

    private function isAllowedCallbackUrl(string $url): bool
    {
        $parts = parse_url($url);
        if (!is_array($parts)) {
            return false;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower(trim((string) ($parts['host'] ?? '')));

        if ($host === '' || in_array($host, ['localhost', 'localhost.localdomain'], true)) {
            return false;
        }

        if (app()->environment(['local', 'testing'])) {
            return in_array($scheme, ['http', 'https'], true);
        }

        if ($scheme !== 'https') {
            return false;
        }

        $ips = filter_var($host, FILTER_VALIDATE_IP)
            ? [$host]
            : (gethostbynamel($host) ?: []);

        if ($ips === []) {
            return false;
        }

        foreach ($ips as $ip) {
            if (!filter_var(
                $ip,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            )) {
                return false;
            }
        }

        return true;
    }
}
