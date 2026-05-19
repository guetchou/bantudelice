<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyBridgeSignature
{
    public function handle(Request $request, Closure $next)
    {
        if (!config('mobile-money-bridge.enabled')) {
            return response()->json([
                'status' => false,
                'message' => 'Passerelle Mobile Money désactivée.',
            ], 403);
        }

        $key = (string) $request->header('X-Bridge-Key', '');
        $timestamp = (string) $request->header('X-Bridge-Timestamp', '');
        $signature = (string) $request->header('X-Bridge-Signature', '');

        if ($key === '' || $timestamp === '' || $signature === '') {
            return response()->json([
                'status' => false,
                'message' => 'En-têtes de signature passerelle manquants.',
            ], 401);
        }

        if (!ctype_digit($timestamp)) {
            return response()->json([
                'status' => false,
                'message' => 'Timestamp passerelle invalide.',
            ], 401);
        }

        $tolerance = max(30, (int) config('mobile-money-bridge.tolerance_seconds', 300));
        if (abs(now()->timestamp - (int) $timestamp) > $tolerance) {
            return response()->json([
                'status' => false,
                'message' => 'Signature expirée.',
            ], 401);
        }

        $clients = config('mobile-money-bridge.clients', []);
        $client = $clients[$key] ?? null;

        if (!$client || empty($client['secret'])) {
            return response()->json([
                'status' => false,
                'message' => 'Client passerelle inconnu.',
            ], 401);
        }

        $payload = implode("\n", [
            $timestamp,
            strtoupper($request->getMethod()),
            '/' . ltrim($request->path(), '/'),
            $request->getContent(),
        ]);

        $expectedSignature = hash_hmac('sha256', $payload, (string) $client['secret']);

        if (!hash_equals($expectedSignature, $signature)) {
            return response()->json([
                'status' => false,
                'message' => 'Signature passerelle invalide.',
            ], 401);
        }

        $request->attributes->set('bridge_client', [
            'key' => $key,
            'name' => $client['name'] ?? $key,
        ]);

        return $next($request);
    }
}
