<?php

namespace App\Services;

use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;

class TwilioService
{
    private ?Client $client = null;

    private function client(): Client
    {
        if ($this->client === null) {
            $sid   = config('external-services.notifications.twilio.sid');
            $token = config('external-services.notifications.twilio.token');

            if (empty($sid) || empty($token)) {
                throw new \RuntimeException('Twilio non configuré (TWILIO_SID ou TWILIO_TOKEN manquant).');
            }

            $this->client = new Client($sid, $token);
        }

        return $this->client;
    }

    /**
     * Envoyer un SMS.
     *
     * @param  string  $to       Numéro international (+242...)
     * @param  string  $message
     * @return array   ['success' => bool, 'sid' => string|null, 'error' => string|null]
     */
    public function sendSms(string $to, string $message): array
    {
        if (!config('external-services.notifications.twilio.enabled', false)) {
            Log::info('[Twilio] SMS désactivé', compact('to'));
            return ['success' => false, 'error' => 'SMS désactivé'];
        }

        $from = config('external-services.notifications.twilio.from');

        if (empty($from)) {
            return ['success' => false, 'error' => 'TWILIO_FROM non configuré'];
        }

        try {
            $msg = $this->client()->messages->create($to, [
                'from' => $from,
                'body' => $message,
            ]);

            Log::info('[Twilio] SMS envoyé', [
                'to'     => $to,
                'sid'    => $msg->sid,
                'status' => $msg->status,
            ]);

            return ['success' => true, 'sid' => $msg->sid, 'status' => $msg->status];

        } catch (\Throwable $e) {
            Log::error('[Twilio] SMS échec', ['to' => $to, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * SMS confirmation commande.
     */
    public function sendOrderConfirmation(string $phone, string $orderNo, float $total): array
    {
        $msg = "BantuDelice: Commande #{$orderNo} confirmée. Total: " . number_format($total, 0, ',', ' ') . " FCFA. Merci!";
        return $this->sendSms($phone, $msg);
    }

    /**
     * SMS statut livraison.
     */
    public function sendDeliveryUpdate(string $phone, string $orderNo, string $statusLabel): array
    {
        $msg = "BantuDelice: Commande #{$orderNo} — {$statusLabel}. Suivez sur bantudelice.cg";
        return $this->sendSms($phone, $msg);
    }

    /**
     * SMS OTP remise commande.
     */
    public function sendOtp(string $phone, string $code): array
    {
        $msg = "BantuDelice: Code de remise {$code}. Donnez-le au livreur à la réception.";
        return $this->sendSms($phone, $msg);
    }

    /**
     * WhatsApp via Twilio sandbox (nécessite numéro sandbox WhatsApp).
     */
    public function sendWhatsApp(string $to, string $message): array
    {
        $from = config('external-services.notifications.twilio.whatsapp_from',
                        'whatsapp:+14155238886'); // sandbox Twilio par défaut

        try {
            $msg = $this->client()->messages->create("whatsapp:{$to}", [
                'from' => $from,
                'body' => $message,
            ]);

            Log::info('[Twilio] WhatsApp envoyé', ['to' => $to, 'sid' => $msg->sid]);
            return ['success' => true, 'sid' => $msg->sid];

        } catch (\Throwable $e) {
            Log::error('[Twilio] WhatsApp échec', ['to' => $to, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
