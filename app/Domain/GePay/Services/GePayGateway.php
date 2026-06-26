<?php

namespace App\Domain\GePay\Services;

use App\Domain\GePay\Contracts\PaymentProviderInterface;
use App\Domain\GePay\Data\ProviderResult;
use App\Domain\GePay\Enums\TransactionStatus;
use App\Domain\GePay\Enums\TransactionType;
use App\Domain\GePay\Models\GePayClient;
use App\Domain\GePay\Models\GePayTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class GePayGateway
{
    /** @var array<string, PaymentProviderInterface> */
    private array $providers;

    public function __construct(MtnMomoProvider $mtnMomoProvider)
    {
        $this->providers = [$mtnMomoProvider->code() => $mtnMomoProvider];
    }

    public function initiate(GePayClient $client, TransactionType $type, array $payload, string $idempotencyKey): GePayTransaction
    {
        if (! $client->can($type->value)) {
            throw new RuntimeException('Cette capacité GePay n’est pas autorisée pour ce client.');
        }

        $providerCode = (string) ($payload['provider'] ?? config('gepay.default_provider', 'mtn_momo'));
        $requestHash = $this->requestHash($type, $providerCode, $payload);
        $provider = $this->provider($providerCode);
        if (! $provider->supports($type)) {
            throw new RuntimeException('Le fournisseur ne supporte pas cette opération.');
        }

        $transaction = DB::transaction(function () use ($client, $type, $payload, $idempotencyKey, $providerCode, $requestHash) {
            $lockedClient = GePayClient::query()->lockForUpdate()->findOrFail($client->id);

            $existing = GePayTransaction::query()
                ->where('client_id', $lockedClient->id)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing) {
                if (! hash_equals((string) $existing->request_hash, $requestHash)) {
                    throw new RuntimeException('Cette clé d’idempotence a déjà été utilisée avec une requête différente.');
                }
                return $existing;
            }

            if (GePayTransaction::query()
                ->where('client_id', $lockedClient->id)
                ->where('type', $type->value)
                ->where('external_reference', $payload['external_reference'])
                ->exists()) {
                throw new RuntimeException('Cette référence externe a déjà été utilisée.');
            }

            return GePayTransaction::create([
                'uuid' => (string) Str::uuid(),
                'client_id' => $lockedClient->id,
                'type' => $type,
                'provider' => $providerCode,
                'external_reference' => $payload['external_reference'],
                'idempotency_key' => $idempotencyKey,
                'request_hash' => $requestHash,
                'amount' => (int) $payload['amount'],
                'currency' => strtoupper((string) ($payload['currency'] ?? 'XAF')),
                'phone' => $payload['phone'],
                'phone_masked' => $this->maskPhone((string) $payload['phone']),
                'status' => TransactionStatus::CREATED,
                'metadata' => array_filter([
                    'payer_message' => $payload['payer_message'] ?? null,
                    'payee_note' => $payload['payee_note'] ?? null,
                    'callback_url' => $payload['callback_url'] ?? null,
                ], fn ($value) => $value !== null && $value !== ''),
            ]);
        });

        if ($transaction->status !== TransactionStatus::CREATED) {
            return $transaction;
        }

        $transaction->forceFill(['status' => TransactionStatus::SUBMITTED, 'submitted_at' => now()])->save();

        try {
            return $this->applyProviderResult($transaction, $provider->initiate($transaction));
        } catch (\Throwable $exception) {
            $transaction->forceFill([
                'status' => TransactionStatus::UNKNOWN,
                'failure_code' => 'GATEWAY_EXCEPTION',
                'failure_message' => $exception->getMessage(),
                'last_checked_at' => now(),
            ])->save();

            return $transaction->fresh();
        }
    }

    public function refresh(GePayTransaction $transaction): GePayTransaction
    {
        if ($transaction->status->isTerminal()) {
            return $transaction;
        }

        try {
            $result = $this->provider($transaction->provider)->checkStatus($transaction);
        } catch (\Throwable $exception) {
            $result = new ProviderResult(
                TransactionStatus::UNKNOWN,
                providerReference: $transaction->provider_reference,
                failureCode: 'RECONCILIATION_EXCEPTION',
                failureMessage: $exception->getMessage()
            );
        }

        return $this->applyProviderResult($transaction, $result);
    }

    private function applyProviderResult(GePayTransaction $transaction, ProviderResult $result): GePayTransaction
    {
        $payload = [
            'status' => $result->status,
            'failure_code' => $result->failureCode,
            'failure_message' => $result->failureMessage,
            'provider_metadata' => $result->metadata,
            'last_checked_at' => now(),
        ];

        if ($result->providerReference) {
            $payload['provider_reference'] = $result->providerReference;
        }
        if ($result->status->isTerminal()) {
            $payload['completed_at'] = now();
        }

        $transaction->forceFill($payload)->save();
        return $transaction->fresh();
    }

    private function provider(string $code): PaymentProviderInterface
    {
        if (! isset($this->providers[$code])) {
            throw new RuntimeException('Fournisseur GePay inconnu: '.$code);
        }
        return $this->providers[$code];
    }

    private function requestHash(TransactionType $type, string $provider, array $payload): string
    {
        $normalized = [
            'type' => $type->value,
            'provider' => $provider,
            'external_reference' => (string) $payload['external_reference'],
            'amount' => (int) $payload['amount'],
            'currency' => strtoupper((string) ($payload['currency'] ?? 'XAF')),
            'phone' => preg_replace('/\D+/', '', (string) $payload['phone']),
            'payer_message' => (string) ($payload['payer_message'] ?? ''),
            'payee_note' => (string) ($payload['payee_note'] ?? ''),
        ];

        return hash('sha256', json_encode($normalized, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    private function maskPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (strlen($digits) <= 4) {
            return str_repeat('•', strlen($digits));
        }
        return substr($digits, 0, 2).str_repeat('•', max(3, strlen($digits) - 4)).substr($digits, -2);
    }
}
