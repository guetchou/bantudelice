<?php

namespace App\Services;

use AfricasTalking\SDK\AfricasTalking;
use Illuminate\Support\Facades\Log;

class AfricasTalkingService
{
    private ?AfricasTalking $client = null;

    private function client(): AfricasTalking
    {
        if ($this->client === null) {
            $username = config('external-services.notifications.africastalking.username');
            $apiKey   = config('external-services.notifications.africastalking.api_key');

            if (empty($username) || empty($apiKey)) {
                throw new \RuntimeException('AfricasTalking non configuré (AFRICASTALKING_USERNAME ou AFRICASTALKING_API_KEY manquant).');
            }

            $this->client = new AfricasTalking($username, $apiKey);
        }

        return $this->client;
    }

    /**
     * Envoyer un SMS à un ou plusieurs numéros.
     *
     * @param  string|array  $to     Numéro(s) au format international (+242...)
     * @param  string        $message
     * @return array  ['success' => bool, 'messageId' => string|null, 'error' => string|null]
     */
    public function sendSms(string|array $to, string $message): array
    {
        if (!config('external-services.notifications.africastalking.enabled', false)) {
            Log::info('[AT] SMS désactivé — message non envoyé', compact('to', 'message'));
            return ['success' => false, 'error' => 'SMS désactivé'];
        }

        $numbers = is_array($to) ? implode(',', $to) : $to;
        $from    = config('external-services.notifications.africastalking.from');

        // 'from' n'est envoyé que si un sender ID est enregistré sur AT
        // (sender ID "BantuDelice" nécessite enregistrement AT — utiliser le défaut sinon)
        $payload = ['to' => $numbers, 'message' => $message];
        if (!empty($from) && $from !== 'BantuDelice') {
            $payload['from'] = $from;
        }

        try {
            $result = $this->client()->sms()->send($payload);

            if (($result['status'] ?? '') !== 'success') {
                Log::warning('[AT] SMS échec', ['result' => $result]);
                return ['success' => false, 'error' => $result['data'] ?? 'Erreur inconnue'];
            }

            $recipients = $result['data']['SMSMessageData']['Recipients'] ?? [];
            $first = $recipients[0] ?? [];
            $success = ($first['status'] ?? '') === 'Success';

            Log::info('[AT] SMS envoyé', [
                'to'        => $numbers,
                'messageId' => $first['messageId'] ?? null,
                'cost'      => $result['data']['SMSMessageData']['Message'] ?? '',
            ]);

            return [
                'success'   => $success,
                'messageId' => $first['messageId'] ?? null,
                'cost'      => $first['cost'] ?? null,
                'error'     => $success ? null : ($first['status'] ?? 'Échec'),
            ];
        } catch (\Throwable $e) {
            Log::error('[AT] SMS exception', ['error' => $e->getMessage(), 'to' => $numbers]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * SMS de confirmation de commande.
     */
    public function sendOrderConfirmation(string $phone, string $orderNo, float $total): array
    {
        $message = "BantuDelice: Votre commande #{$orderNo} est confirmée. Total: " . number_format($total, 0, ',', ' ') . " FCFA. Merci!";
        return $this->sendSms($phone, $message);
    }

    /**
     * SMS statut livraison.
     */
    public function sendDeliveryUpdate(string $phone, string $orderNo, string $statusLabel): array
    {
        $message = "BantuDelice: Commande #{$orderNo} — {$statusLabel}. Suivez sur bantudelice.cg";
        return $this->sendSms($phone, $message);
    }

    /**
     * SMS OTP / code de remise.
     */
    public function sendOtp(string $phone, string $code): array
    {
        $message = "BantuDelice: Votre code de remise est {$code}. Communiquez-le au livreur.";
        return $this->sendSms($phone, $message);
    }
}
