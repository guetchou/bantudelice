<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MtnSmsService
{
    private const TOKEN_CACHE_KEY = 'mtn_api_oauth_token';
    // Endpoint officiel selon Swagger MTN MADAPI v1.1
    private const OAUTH_URL       = 'https://api.mtn.com/v1/oauth/access_token';
    private const SMS_URL         = 'https://api.mtn.com/v3/sms/messages/sms/outbound';

    private function getToken(): string
    {
        return Cache::remember(self::TOKEN_CACHE_KEY, 1_200_000, function () {
            $key    = config('external-services.notifications.mtn_sms.consumer_key');
            $secret = config('external-services.notifications.mtn_sms.consumer_secret');

            // Auth via formData (méthode officielle Swagger MTN MADAPI v1.1)
            $response = Http::asForm()->post(
                self::OAUTH_URL . '?grant_type=client_credentials',
                [
                    'client_id'     => $key,
                    'client_secret' => $secret,
                    'grant_type'    => 'client_credentials',
                ]
            );

            if (!$response->ok()) {
                Log::error('[MTN SMS] Auth failed', ['status' => $response->status(), 'body' => $response->body()]);
                throw new \RuntimeException('MTN OAuth failed: ' . $response->body());
            }

            return $response->json('access_token');
        });
    }

    /**
     * Envoyer un SMS via MTN SMS v3 API.
     *
     * @param  string|array  $to       Numéro(s) +242...
     * @param  string        $message
     * @return array  ['success' => bool, 'transactionId' => string|null, 'error' => string|null]
     */
    public function sendSms(string|array $to, string $message): array
    {
        if (!config('external-services.notifications.mtn_sms.enabled', false)) {
            Log::info('[MTN SMS] Désactivé', compact('to'));
            return ['success' => false, 'error' => 'MTN SMS désactivé'];
        }

        $sender  = config('external-services.notifications.mtn_sms.sender_id', 'BantuDelice');
        $numbers = is_array($to) ? $to : [$to];

        try {
            $token = $this->getToken();

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$token}",
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ])->post(self::SMS_URL, [
                'senderAddress'   => $sender,
                'receiverAddress' => $numbers,
                'message'         => $message,
                'serviceCode'     => '',
            ]);

            $data = $response->json();

            if (!$response->ok() || ($data['statusCode'] ?? 0) !== 200) {
                // Token expiré → vider le cache et réessayer une fois
                if (($data['statusCode'] ?? 0) === 4000) {
                    Cache::forget(self::TOKEN_CACHE_KEY);
                }
                Log::warning('[MTN SMS] Envoi échoué', ['response' => $data]);
                return ['success' => false, 'error' => $data['statusMessage'] ?? 'Erreur MTN'];
            }

            Log::info('[MTN SMS] Envoyé', [
                'to'            => $numbers,
                'transactionId' => $data['transactionId'] ?? null,
            ]);

            return [
                'success'       => true,
                'transactionId' => $data['transactionId'] ?? null,
            ];

        } catch (\Throwable $e) {
            Log::error('[MTN SMS] Exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function sendOrderConfirmation(string $phone, string $orderNo, float $total): array
    {
        return $this->sendSms($phone,
            "BantuDelice: Commande #{$orderNo} confirmee. Total: " . number_format($total, 0, ',', ' ') . " FCFA. Merci!"
        );
    }

    public function sendDeliveryUpdate(string $phone, string $orderNo, string $status): array
    {
        return $this->sendSms($phone,
            "BantuDelice: Commande #{$orderNo} - {$status}. Suivez sur bantudelice.cg"
        );
    }

    public function sendOtp(string $phone, string $code): array
    {
        return $this->sendSms($phone,
            "BantuDelice: Code de remise {$code}. Donnez-le au livreur."
        );
    }
}
