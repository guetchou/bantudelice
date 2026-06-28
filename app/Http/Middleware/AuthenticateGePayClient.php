<?php

namespace App\Http\Middleware;

use App\Domain\GePay\Models\GePayClient;
use App\Domain\GePay\Services\GePaySigner;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateGePayClient
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = trim((string) $request->header('X-GePay-Key'));
        $timestamp = trim((string) $request->header('X-GePay-Timestamp'));
        $signature = strtolower(trim((string) $request->header('X-GePay-Signature')));
        $nonce = trim((string) $request->header('X-GePay-Nonce'));
        $idempotencyKey = trim((string) $request->header('Idempotency-Key', ''));
        $nonceRequired = (bool) config('gepay.require_nonce', true);
        $invalidNonce = $nonce !== '' && (
            strlen($nonce) > 128
            || ! preg_match('/^[A-Za-z0-9._:-]+$/', $nonce)
        );

        if (
            $apiKey === ''
            || $timestamp === ''
            || $signature === ''
            || ! ctype_digit($timestamp)
            || ($nonceRequired && $nonce === '')
            || $invalidNonce
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Authentification GePay incomplète ou invalide.',
            ], 401);
        }

        $tolerance = max(30, (int) config('gepay.signature_tolerance_seconds', 300));
        if (abs(now()->timestamp - (int) $timestamp) > $tolerance) {
            return response()->json(['success' => false, 'message' => 'Signature GePay expirée.'], 401);
        }

        $client = GePayClient::query()
            ->where('api_key', $apiKey)
            ->where('is_active', true)
            ->first();

        if (! $client) {
            return response()->json(['success' => false, 'message' => 'Client GePay non autorisé.'], 401);
        }

        $allowedIps = array_values(array_filter($client->allowed_ips ?? []));
        if ($allowedIps !== [] && ! in_array($request->ip(), $allowedIps, true)) {
            return response()->json(['success' => false, 'message' => 'Adresse IP non autorisée.'], 403);
        }

        $expected = $nonce === ''
            ? GePaySigner::sign(
                (string) $client->api_secret,
                $timestamp,
                $request->method(),
                $request->getRequestUri(),
                (string) $request->getContent()
            )
            : GePaySigner::sign(
                (string) $client->api_secret,
                $timestamp,
                $request->method(),
                $request->getRequestUri(),
                (string) $request->getContent(),
                $nonce,
                $idempotencyKey
            );

        if (! hash_equals($expected, $signature)) {
            return response()->json(['success' => false, 'message' => 'Signature GePay invalide.'], 401);
        }

        if ($nonce !== '') {
            $nonceKey = 'gepay:nonce:'.$client->id.':'.hash('sha256', $nonce);
            $stored = Cache::add($nonceKey, 1, now()->addSeconds($tolerance * 2));

            if (! $stored) {
                return response()->json([
                    'success' => false,
                    'message' => 'Requête GePay dupliquée (nonce rejoué).',
                ], 401);
            }
        }

        $request->attributes->set('gepayClient', $client);

        return $next($request);
    }
}
