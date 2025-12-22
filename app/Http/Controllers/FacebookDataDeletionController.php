<?php

namespace App\Http\Controllers;

use App\Services\UserDeletionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FacebookDataDeletionController extends Controller
{
    /**
     * Endpoint Facebook "User Data Deletion".
     * Meta envoie un POST avec signed_request.
     *
     * Réponse attendue (JSON):
     * - url : URL publique de statut
     * - confirmation_code : code unique
     */
    public function handle(Request $request)
    {
        $signedRequest = $request->input('signed_request');
        if (!$signedRequest) {
            return response()->json([
                'error' => 'signed_request manquant',
            ], 400);
        }

        $secret = config('external-services.social_auth.facebook.client_secret');
        if (empty($secret)) {
            Log::error('FacebookDataDeletionController: FACEBOOK_CLIENT_SECRET non configuré');
            return response()->json([
                'error' => 'Configuration Facebook incomplète',
            ], 500);
        }

        try {
            [$encodedSig, $encodedPayload] = explode('.', $signedRequest, 2);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'signed_request invalide'], 400);
        }

        $sig = $this->base64UrlDecode($encodedSig);
        $payloadJson = $this->base64UrlDecode($encodedPayload);
        $payload = json_decode($payloadJson, true);

        if (!is_array($payload)) {
            return response()->json(['error' => 'payload invalide'], 400);
        }

        // Vérifier l'algorithme
        if (($payload['algorithm'] ?? '') !== 'HMAC-SHA256') {
            return response()->json(['error' => 'algorithme non supporté'], 400);
        }

        $expectedSig = hash_hmac('sha256', $encodedPayload, $secret, true);
        if (!hash_equals($expectedSig, $sig)) {
            return response()->json(['error' => 'signature invalide'], 400);
        }

        $facebookUserId = (string) ($payload['user_id'] ?? '');
        if ($facebookUserId === '') {
            return response()->json(['error' => 'user_id manquant'], 400);
        }

        // Traiter la demande
        $processed = UserDeletionService::anonymizeByFacebookUserId($facebookUserId, [
            'source' => 'facebook_data_deletion',
            'issued_at' => $payload['issued_at'] ?? null,
        ]);

        $code = Str::upper(Str::random(10));
        Cache::put("fb_data_deletion:{$code}", [
            'status' => $processed ? 'processed' : 'not_found',
            'facebook_user_id' => $facebookUserId,
            'created_at' => now()->toDateTimeString(),
        ], now()->addDays(30));

        $statusUrl = route('facebook.data_deletion.status', ['code' => $code]);

        return response()->json([
            'url' => $statusUrl,
            'confirmation_code' => $code,
        ]);
    }

    /**
     * Page publique de statut, utilisée par Meta (et par l'utilisateur).
     */
    public function status(string $code)
    {
        $data = Cache::get("fb_data_deletion:{$code}");
        if (!$data) {
            return response()->view('frontend.data_deletion_status', [
                'code' => $code,
                'status' => 'unknown',
            ], 404);
        }

        return response()->view('frontend.data_deletion_status', [
            'code' => $code,
            'status' => $data['status'] ?? 'unknown',
        ]);
    }

    private function base64UrlDecode(string $input): string
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $padLen = 4 - $remainder;
            $input .= str_repeat('=', $padLen);
        }
        $input = strtr($input, '-_', '+/');
        return base64_decode($input) ?: '';
    }
}


