<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Responsabilité unique : transport FCM brut.
 *
 * Supporte deux protocoles :
 * - FCM HTTP v1 (OAuth2) — actif si FIREBASE_PROJECT_ID est défini (recommandé)
 * - FCM Legacy           — fallback si pas de project_id (clés AAAA... existantes)
 *
 * Pour activer FCM v1 :
 *   1. Télécharger le service account JSON depuis Firebase Console → Paramètres → Comptes de service
 *   2. Ajouter dans .env :
 *      FIREBASE_PROJECT_ID=bantudelice-xxxxx
 *      FIREBASE_CREDENTIALS=/opt/bantudelice/firebase-credentials.json
 */
class FcmTransport
{
    // FCM v1
    const FCM_V1_URL     = 'https://fcm.googleapis.com/v1/projects/%s/messages:send';
    const FCM_OAUTH_URL  = 'https://oauth2.googleapis.com/token';
    const FCM_SCOPE      = 'https://www.googleapis.com/auth/firebase.messaging';
    const TOKEN_CACHE_KEY = 'fcm_v1_oauth_token';
    const TOKEN_TTL_SLACK = 120; // secondes de marge avant expiration

    // FCM Legacy (fallback)
    const FCM_LEGACY_URL = 'https://fcm.googleapis.com/fcm/send';

    // ── Envoi principal ───────────────────────────────────────────

    public static function sendToDevice(
        string $deviceToken,
        string $title,
        string $body,
        string $key,
        $userId = null,
        string $type = 'user',
        array $dataExtra = []
    ): array {
        $data = self::buildData($key, $userId, $dataExtra);

        return self::useV1()
            ? self::sendV1($deviceToken, $title, $body, $data)
            : self::sendLegacy(['to' => $deviceToken], $title, $body, $data, $type);
    }

    public static function sendToMultipleDevices(
        array $deviceTokens,
        string $title,
        string $body,
        string $key,
        $userId = null,
        string $type = 'user',
        array $dataExtra = []
    ): array {
        $data = self::buildData($key, $userId, $dataExtra);

        if (self::useV1()) {
            // FCM v1 ne supporte pas multicast — envoie en boucle
            $results = [];
            foreach ($deviceTokens as $token) {
                $results[] = self::sendV1($token, $title, $body, $data);
            }
            $success = collect($results)->contains('success', true);
            return ['success' => $success, 'results' => $results];
        }

        return self::sendLegacy(['registration_ids' => $deviceTokens], $title, $body, $data, $type);
    }

    public static function sendWithAction(
        string $deviceToken,
        string $title,
        string $body,
        string $key,
        string $clickAction,
        array $dataExtra = []
    ): array {
        $data = array_merge(self::buildData($key, null, $dataExtra), ['click_action' => $clickAction]);

        return self::useV1()
            ? self::sendV1($deviceToken, $title, $body, $data)
            : self::sendLegacy(['to' => $deviceToken], $title, $body, $data, 'user');
    }

    public static function hasConfiguredKey(?string $type = null): bool
    {
        return self::useV1()
            ? (bool) self::getFirebaseProjectId()
            : self::resolveFcmKey($type ?? 'user') !== '';
    }

    // ── FCM HTTP v1 ───────────────────────────────────────────────

    private static function useV1(): bool
    {
        return (bool) self::getFirebaseProjectId() && (bool) self::getCredentialsPath();
    }

    private static function getFirebaseProjectId(): string
    {
        return trim((string) config('external-services.notifications.fcm.project_id', env('FIREBASE_PROJECT_ID', '')));
    }

    private static function getCredentialsPath(): string
    {
        return trim((string) config('external-services.notifications.fcm.credentials_path', env('FIREBASE_CREDENTIALS', '')));
    }

    private static function sendV1(string $token, string $title, string $body, array $data): array
    {
        $projectId = self::getFirebaseProjectId();
        if (!$projectId) {
            return ['success' => false, 'error' => 'fcm_v1_project_id_missing'];
        }

        $accessToken = self::getOAuthToken();
        if (!$accessToken) {
            Log::warning('FCM v1: impossible d\'obtenir un token OAuth, fallback legacy');
            return self::sendLegacyFallback($token, $title, $body, $data);
        }

        $url     = sprintf(self::FCM_V1_URL, $projectId);
        $payload = [
            'message' => [
                'token' => $token,
                'notification' => ['title' => $title, 'body' => $body],
                'data' => array_map('strval', $data),
                'android' => [
                    'notification' => [
                        'sound'       => $data['sound_key'] ?? 'default',
                        'click_action'=> 'FLUTTER_NOTIFICATION_CLICK',
                    ],
                ],
                'apns' => [
                    'payload' => ['aps' => ['sound' => $data['sound_key'] ?? 'default']],
                ],
            ],
        ];

        return self::curlPost($url, json_encode($payload), [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ]);
    }

    private static function getOAuthToken(): ?string
    {
        // Retourner depuis le cache si encore valide
        $cached = Cache::get(self::TOKEN_CACHE_KEY);
        if ($cached) return $cached;

        $credentialsPath = self::getCredentialsPath();
        if (!$credentialsPath || !file_exists($credentialsPath)) {
            Log::warning('FCM v1: fichier credentials introuvable', ['path' => $credentialsPath]);
            return null;
        }

        $credentials = json_decode(file_get_contents($credentialsPath), true);
        if (!$credentials || empty($credentials['private_key']) || empty($credentials['client_email'])) {
            Log::warning('FCM v1: credentials JSON invalide');
            return null;
        }

        try {
            // Construire le JWT pour Google OAuth2
            $now    = time();
            $header = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $claim  = base64_encode(json_encode([
                'iss'   => $credentials['client_email'],
                'scope' => self::FCM_SCOPE,
                'aud'   => self::FCM_OAUTH_URL,
                'iat'   => $now,
                'exp'   => $now + 3600,
            ]));

            $signInput = $header . '.' . $claim;
            openssl_sign($signInput, $signature, $credentials['private_key'], 'sha256WithRSAEncryption');
            $jwt = $signInput . '.' . base64_encode($signature);

            $result = self::curlPost(self::FCM_OAUTH_URL, http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]), ['Content-Type: application/x-www-form-urlencoded']);

            if (!$result['success'] || empty($result['data']['access_token'])) {
                Log::warning('FCM v1: OAuth token request échoué', ['response' => $result['data'] ?? null]);
                return null;
            }

            $token   = $result['data']['access_token'];
            $ttl     = max(0, ($result['data']['expires_in'] ?? 3600) - self::TOKEN_TTL_SLACK);
            Cache::put(self::TOKEN_CACHE_KEY, $token, $ttl);

            return $token;
        } catch (\Throwable $e) {
            Log::error('FCM v1: erreur génération JWT', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // ── FCM Legacy (fallback) ─────────────────────────────────────

    private static function sendLegacy(array $target, string $title, string $body, array $data, string $type): array
    {
        $apiKey = self::resolveFcmKey($type);
        if (!$apiKey) {
            Log::warning('FCM legacy: clé manquante', ['type' => $type]);
            return ['success' => false, 'error' => 'fcm_key_missing'];
        }

        $payload = array_merge($target, [
            'notification' => ['title' => $title, 'body' => $body, 'sound' => $data['sound_key'] ?? true],
            'data'         => $data,
        ]);

        return self::curlPost(self::FCM_LEGACY_URL, json_encode($payload), [
            'Authorization: key=' . $apiKey,
            'Content-Type: application/json',
        ]);
    }

    private static function sendLegacyFallback(string $token, string $title, string $body, array $data): array
    {
        return self::sendLegacy(['to' => $token], $title, $body, $data, 'user');
    }

    public static function resolveFcmKey(string $type = 'user'): string
    {
        $fcmConfig = config('external-services.notifications.fcm', []);
        $preferredKey = match ($type) {
            'restaurant' => data_get($fcmConfig, 'restaurant_key'),
            'driver'     => data_get($fcmConfig, 'driver_key'),
            default      => data_get($fcmConfig, 'user_key'),
        };
        foreach ([$preferredKey, data_get($fcmConfig, 'server_key')] as $candidate) {
            $key = trim((string) $candidate);
            if ($key !== '') return $key;
        }
        return '';
    }

    // ── HTTP helper ───────────────────────────────────────────────

    private static function curlPost(string $url, string $body, array $headers): array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYHOST => 2,   // S1.2 — SSL activé
            CURLOPT_SSL_VERIFYPEER => true, // S1.2 — SSL activé
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_POSTFIELDS     => $body,
        ]);

        $result   = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            Log::error('FCM curl error', ['url' => $url, 'error' => $error]);
            return ['success' => false, 'error' => $error, 'data' => null];
        }

        $response = json_decode($result, true);
        $success  = $httpCode >= 200 && $httpCode < 300;

        if (!$success) {
            Log::warning('FCM HTTP error', ['url' => $url, 'http_code' => $httpCode, 'response' => $response]);
        }

        return ['success' => $success, 'data' => $response, 'http_code' => $httpCode];
    }

    // ── Helpers ───────────────────────────────────────────────────

    private static function buildData(string $key, $userId, array $dataExtra): array
    {
        $data = ['key' => $key];
        if ($userId) $data['user_id'] = (string) $userId;
        return array_merge($data, self::normalizeFcmData($dataExtra));
    }

    public static function normalizeFcmData(array $data): array
    {
        $payload = [];
        foreach ($data as $key => $value) {
            if ($value === null || $value === '' || !is_string($key) || $key === '') continue;
            $k = Str::snake($key);
            $payload[$k] = is_array($value) ? json_encode($value) : (is_bool($value) ? ($value ? '1' : '0') : (string) $value);
        }
        return $payload;
    }

    public static function buildNotificationPayload(string $title, string $body, array $dataExtra = []): array
    {
        return [
            'title' => $title,
            'body'  => $body,
            'sound' => !empty($dataExtra['sound_key']) ? (string) $dataExtra['sound_key'] : true,
        ];
    }
}
