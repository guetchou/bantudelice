<?php

namespace App\Http\Controllers\Api\GePay\V1;

use App\Domain\GePay\Models\GePayTransaction;
use App\Domain\GePay\Models\GePayWebhookEvent;
use App\Domain\GePay\Services\GePayGateway;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WebhookController extends Controller
{
    public function mtn(Request $request, GePayGateway $gateway): JsonResponse
    {
        $payload = $request->all();
        $payloadHash = hash('sha256', (string) $request->getContent());
        $eventKey = (string) ($payload['referenceId'] ?? $payload['reference'] ?? $payload['externalId'] ?? $payloadHash);

        $event = GePayWebhookEvent::query()->firstOrCreate(
            ['provider' => 'mtn_momo', 'payload_hash' => $payloadHash],
            [
                'event_key' => Str::limit($eventKey, 191, ''),
                'status' => 'received',
                'payload' => $payload,
            ]
        );

        if ($event->processed_at) {
            return response()->json(['success' => true, 'duplicate' => true], 200);
        }

        $transaction = GePayTransaction::query()
            ->where('provider', 'mtn_momo')
            ->where(function ($query) use ($eventKey) {
                $query->where('provider_reference', $eventKey)
                    ->orWhere('external_reference', $eventKey);
            })
            ->first();

        if (! $transaction) {
            $event->forceFill(['status' => 'ignored', 'processed_at' => now(), 'error_message' => 'Transaction introuvable'])->save();
            return response()->json(['success' => true, 'matched' => false], 202);
        }

        try {
            $transaction = $gateway->refresh($transaction);
            $event->forceFill(['status' => 'processed', 'processed_at' => now()])->save();

            return response()->json(['success' => true, 'matched' => true, 'status' => $transaction->status->value]);
        } catch (\Throwable $exception) {
            $event->forceFill(['status' => 'failed', 'error_message' => $exception->getMessage()])->save();
            return response()->json(['success' => false], 500);
        }
    }
}
