<?php

namespace App\Http\Controllers\Api\GePay\V1;

use App\Domain\GePay\Enums\TransactionType;
use App\Domain\GePay\Models\GePayClient;
use App\Domain\GePay\Models\GePayTransaction;
use App\Domain\GePay\Services\GePayGateway;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TransactionController extends Controller
{
    public function client(Request $request): JsonResponse
    {
        /** @var GePayClient $client */
        $client = $request->attributes->get('gepayClient');

        return response()->json([
            'success' => true,
            'client' => [
                'uuid' => $client->uuid,
                'name' => $client->name,
                'capabilities' => $client->capabilities,
            ],
        ]);
    }

    public function collection(Request $request, GePayGateway $gateway): JsonResponse
    {
        return $this->initiate($request, $gateway, TransactionType::COLLECTION);
    }

    public function disbursement(Request $request, GePayGateway $gateway): JsonResponse
    {
        return $this->initiate($request, $gateway, TransactionType::DISBURSEMENT);
    }

    public function show(Request $request, string $uuid): JsonResponse
    {
        /** @var GePayClient $client */
        $client = $request->attributes->get('gepayClient');
        $transaction = GePayTransaction::query()->where('client_id', $client->id)->where('uuid', $uuid)->firstOrFail();

        return response()->json(['success' => true, 'transaction' => $this->resource($transaction)]);
    }

    public function refresh(Request $request, string $uuid, GePayGateway $gateway): JsonResponse
    {
        /** @var GePayClient $client */
        $client = $request->attributes->get('gepayClient');
        $transaction = GePayTransaction::query()->where('client_id', $client->id)->where('uuid', $uuid)->firstOrFail();

        return response()->json(['success' => true, 'transaction' => $this->resource($gateway->refresh($transaction))]);
    }

    private function initiate(Request $request, GePayGateway $gateway, TransactionType $type): JsonResponse
    {
        $payload = $request->validate([
            'amount' => ['required', 'integer', 'min:1', 'max:2000000000'],
            'currency' => ['nullable', 'string', Rule::in(['XAF'])],
            'phone' => ['required', 'string', 'max:30'],
            'provider' => ['nullable', 'string', Rule::in(['mtn_momo'])],
            'external_reference' => ['required', 'string', 'max:191'],
            'payer_message' => ['nullable', 'string', 'max:64'],
            'payee_note' => ['nullable', 'string', 'max:64'],
            'callback_url' => ['nullable', 'url', 'max:500'],
        ]);

        $idempotencyKey = trim((string) $request->header('Idempotency-Key'));
        if ($idempotencyKey === '' || strlen($idempotencyKey) > 191) {
            return response()->json(['success' => false, 'message' => 'En-tête Idempotency-Key obligatoire et limité à 191 caractères.'], 422);
        }

        /** @var GePayClient $client */
        $client = $request->attributes->get('gepayClient');

        try {
            $transaction = $gateway->initiate($client, $type, $payload, $idempotencyKey);
        } catch (\Throwable $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage()], 422);
        }

        $httpStatus = match ($transaction->status->value) {
            'successful' => 200,
            'failed', 'cancelled', 'expired' => 422,
            default => 202,
        };

        return response()->json([
            'success' => ! in_array($transaction->status->value, ['failed', 'cancelled', 'expired'], true),
            'transaction' => $this->resource($transaction),
        ], $httpStatus);
    }

    private function resource(GePayTransaction $transaction): array
    {
        return [
            'reference' => $transaction->uuid,
            'external_reference' => $transaction->external_reference,
            'provider' => $transaction->provider,
            'provider_reference' => $transaction->provider_reference,
            'type' => $transaction->type->value,
            'status' => $transaction->status->value,
            'amount' => (int) $transaction->amount,
            'currency' => $transaction->currency,
            'phone_masked' => $transaction->phone_masked,
            'failure_code' => $transaction->failure_code,
            'failure_message' => $transaction->failure_message,
            'created_at' => $transaction->created_at?->toIso8601String(),
            'completed_at' => $transaction->completed_at?->toIso8601String(),
        ];
    }
}
