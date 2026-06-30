<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MtnSmsService
{
    /**
     * Envoie un SMS via la plateforme Tinda de MTN Congo.
     *
     * @param  string|array<int,string>  $to
     * @return array{success:bool,message_id:?string,provider:string,error?:string,status?:string,result?:mixed}
     */
    public function sendSms(string|array $to, string $message, array $options = []): array
    {
        $config = config('external-services.notifications.mtn_sms', []);

        if (!($config['enabled'] ?? false)) {
            return $this->failure('MTN Tinda SMS désactivé');
        }

        if (blank($config['token'] ?? null)) {
            return $this->failure('MTN_TINDA_TOKEN non configuré');
        }

        $receivers = collect(is_array($to) ? $to : [$to])
            ->map(fn (string $number) => $this->normalizeReceiver($number))
            ->filter()
            ->unique()
            ->values();

        if ($receivers->isEmpty()) {
            return $this->failure('Aucun numéro destinataire valide');
        }

        if ($receivers->count() > 1000) {
            return $this->failure('La plateforme Tinda accepte au maximum 1000 destinataires par requête');
        }

        $payload = array_filter([
            'msg' => $message,
            'sender' => $options['sender'] ?? $config['sender_id'] ?? 'BantuDelice',
            'receivers' => $receivers->implode(','),
            'date_envois' => $options['date_envois'] ?? null,
            'externalId' => $options['external_id'] ?? $this->makeExternalId(),
            'callback_url' => $options['callback_url'] ?? $config['callback_url'] ?? null,
            'email' => $options['email'] ?? null,
            'msg_mail' => $options['msg_mail'] ?? null,
            'objet_mail' => $options['objet_mail'] ?? null,
        ], static fn ($value) => $value !== null && $value !== '');

        try {
            $response = $this->client($config)->post($config['api_url'], $payload);

            return $this->parseSendResponse($response, $receivers->all());
        } catch (\Throwable $e) {
            Log::error('[MTN Tinda SMS] Exception d’envoi', [
                'error' => $e->getMessage(),
                'receivers_count' => $receivers->count(),
            ]);

            return $this->failure($e->getMessage());
        }
    }

    /**
     * Interroge le statut d’une requête Tinda déjà acceptée.
     */
    public function getStatus(string|int $serverId): array
    {
        $config = config('external-services.notifications.mtn_sms', []);

        if (!($config['enabled'] ?? false) || blank($config['token'] ?? null)) {
            return $this->failure('MTN Tinda SMS non configuré');
        }

        try {
            $response = $this->client($config)->post($config['api_url'], [
                'op' => 'status',
                'id' => (string) $serverId,
            ]);

            $data = $response->json() ?: [];
            $apiStatus = (string) ($data['status'] ?? $response->status());
            $success = $response->successful() && in_array($apiStatus, ['200', '201'], true);

            return [
                'success' => $success,
                'provider' => 'mtn_tinda',
                'status' => $apiStatus,
                'external_id' => $data['externalId'] ?? null,
                'result' => $data['resultat'] ?? null,
                'error' => $success ? null : ($data['detail'] ?? $data['resultat'] ?? $response->body()),
            ];
        } catch (\Throwable $e) {
            Log::error('[MTN Tinda SMS] Exception de statut', ['error' => $e->getMessage()]);

            return $this->failure($e->getMessage());
        }
    }

    public function sendOrderConfirmation(string $phone, string $orderNo, float $total): array
    {
        return $this->sendSms(
            $phone,
            "BantuDelice: Commande #{$orderNo} confirmee. Total: ".number_format($total, 0, ',', ' ').' FCFA. Merci!'
        );
    }

    public function sendDeliveryUpdate(string $phone, string $orderNo, string $status): array
    {
        return $this->sendSms($phone, "BantuDelice: Commande #{$orderNo} - {$status}. Suivez sur bantudelice.cg");
    }

    public function sendOtp(string $phone, string $code): array
    {
        return $this->sendSms($phone, "BantuDelice: Code de remise {$code}. Donnez-le au livreur.");
    }

    private function client(array $config): \Illuminate\Http\Client\PendingRequest
    {
        $prefix = trim((string) ($config['authorization_prefix'] ?? 'Token'));

        return Http::acceptJson()
            ->asJson()
            ->withHeaders([
                'Authorization' => trim($prefix.' '.trim((string) $config['token'])),
            ])
            ->timeout((int) ($config['timeout'] ?? 15))
            ->retry((int) ($config['retry_times'] ?? 1), (int) ($config['retry_sleep_ms'] ?? 300), throw: false);
    }

    private function parseSendResponse(Response $response, array $receivers): array
    {
        $data = $response->json() ?: [];
        $apiStatus = (string) ($data['status'] ?? $response->status());
        $success = $response->successful() && in_array($apiStatus, ['200', '201'], true);

        if (!$success) {
            $error = $data['detail'] ?? $data['resultat'] ?? $response->body() ?: 'Erreur MTN Tinda';
            Log::warning('[MTN Tinda SMS] Envoi refusé', [
                'http_status' => $response->status(),
                'api_status' => $apiStatus,
                'error' => $error,
                'receivers_count' => count($receivers),
            ]);

            return $this->failure((string) $error, $apiStatus);
        }

        Log::info('[MTN Tinda SMS] Envoi accepté', [
            'message_id' => isset($data['id']) ? (string) $data['id'] : null,
            'receivers_count' => count($receivers),
            'api_status' => $apiStatus,
        ]);

        return [
            'success' => true,
            'message_id' => isset($data['id']) ? (string) $data['id'] : null,
            'provider' => 'mtn_tinda',
            'status' => $apiStatus,
            'result' => $data['resultat'] ?? null,
            'error' => null,
        ];
    }

    private function normalizeReceiver(string $phone): string
    {
        $phone = preg_replace('/\D+/', '', $phone) ?: '';

        if (str_starts_with($phone, '00')) {
            $phone = substr($phone, 2);
        }

        if (preg_match('/^0[456]\d{7,8}$/', $phone)) {
            $phone = '242'.substr($phone, 1);
        } elseif (!str_starts_with($phone, '242')) {
            $phone = '242'.$phone;
        }

        return preg_match('/^242[0456]\d{7,8}$/', $phone) ? $phone : '';
    }

    private function makeExternalId(): int
    {
        return (int) substr((string) now()->format('ymdHis').random_int(10, 99), -9);
    }

    private function failure(string $error, ?string $status = null): array
    {
        return [
            'success' => false,
            'message_id' => null,
            'provider' => 'mtn_tinda',
            'status' => $status,
            'error' => Str::limit($error, 1000),
        ];
    }
}
