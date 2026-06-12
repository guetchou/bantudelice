<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use App\Services\ConfigService;
use Illuminate\Support\Str;

/**
 * Service SMS unifié pour l'envoi de messages et OTP
 * Supporte: MTN SMS v3 (Congo), Africa's Talking, Twilio, BulkGate, SMS Local Congo
 *
 * Routing : numéros +242 → MTN en priorité, fallback Africa's Talking.
 */
class SmsService
{
    /**
     * Durée de validité d'un OTP en minutes
     */
    const OTP_EXPIRY_MINUTES = 10;
    
    /**
     * Longueur du code OTP
     */
    const OTP_LENGTH = 6;
    
    /**
     * Envoyer un SMS
     * 
     * @param string $phone Numéro de téléphone (format international)
     * @param string $message Message à envoyer
     * @return array ['success' => bool, 'message_id' => string|null, 'error' => string|null]
     */
    public static function send(string $phone, string $message): array
    {
        $phone  = self::normalizePhone($phone);
        $config = config('external-services.notifications');

        // Numéros Congo (+242) → MTN v3 en priorité, fallback Africa's Talking
        if (str_starts_with($phone, '+242')) {
            if ($config['mtn_sms']['enabled'] ?? false) {
                $result = self::sendViaMtnSms($phone, $message);
                if ($result['success']) return $result;
                Log::warning('MTN SMS échoué, fallback Africa\'s Talking', ['phone' => substr($phone, 0, -4).'****', 'error' => $result['error'] ?? '']);
            }
            if ($config['africastalking']['enabled'] ?? false) {
                return self::sendViaAfricasTalking($phone, $message);
            }
        }

        // Autres numéros — ordre classique
        if ($config['twilio']['enabled'] ?? false) {
            return self::sendViaTwilio($phone, $message);
        }
        if ($config['africastalking']['enabled'] ?? false) {
            return self::sendViaAfricasTalking($phone, $message);
        }
        if ($config['bulkgate']['enabled'] ?? false) {
            return self::sendViaBulkGate($phone, $message);
        }
        if ($config['sms_local']['enabled'] ?? false) {
            return self::sendViaLocalProvider($phone, $message);
        }

        Log::info('SMS (mode démo)', ['phone' => $phone, 'message' => $message]);
        return [
            'success'    => true,
            'message_id' => 'DEMO-' . time(),
            'demo'       => true,
            'note'       => 'Aucun provider SMS configuré.',
        ];
    }
    
    /**
     * Générer et envoyer un code OTP
     * 
     * @param string $phone
     * @param string $type Type d'OTP (login, register, reset_password, verify_phone)
     * @return array
     */
    public static function sendOtp(string $phone, string $type = 'verify_phone'): array
    {
        $phone = self::normalizePhone($phone);
        
        // Générer le code OTP
        $otp = self::generateOtp();
        
        // Stocker en cache
        $cacheKey = "otp_{$type}_{$phone}";
        Cache::put($cacheKey, [
            'code' => $otp,
            'attempts' => 0,
            'created_at' => now()->toIso8601String(),
        ], now()->addMinutes(self::OTP_EXPIRY_MINUTES));
        
        // Construire le message
        $companyName = \App\Services\ConfigService::getCompanyName();
        $messages = [
            'login' => "Votre code de connexion {$companyName} est: {$otp}. Valide pendant " . self::OTP_EXPIRY_MINUTES . " minutes.",
            'register' => "Bienvenue sur {$companyName}! Votre code de vérification est: {$otp}",
            'reset_password' => "Code de réinitialisation {$companyName}: {$otp}. Si vous n'avez pas demandé ceci, ignorez ce message.",
            'verify_phone' => "Votre code de vérification {$companyName}: {$otp}",
            'order_confirmation' => "Code de confirmation de commande: {$otp}",
        ];
        
        $message = $messages[$type] ?? $messages['verify_phone'];
        
        // Envoyer le SMS
        $result = self::send($phone, $message);
        $result['otp_sent'] = true;
        $result['expires_in_minutes'] = self::OTP_EXPIRY_MINUTES;

        // Fallback email si SMS échoue et qu'un email est associé au contexte
        if (!($result['success'] ?? false) && !($result['demo'] ?? false)) {
            $result['sms_failed'] = true;
            $result['fallback_email_attempted'] = false;

            $userEmail = \App\User::where('phone', $phone)->value('email');
            if ($userEmail) {
                try {
                    Mail::raw(
                        "Votre code " . ConfigService::getCompanyName() . " : {$otp}\nValide pendant " . self::OTP_EXPIRY_MINUTES . " minutes.\n\n(Ce message a été envoyé par email car votre SMS n'a pas pu être délivré.)",
                        fn($m) => $m->to($userEmail)->subject('Votre code de vérification ' . ConfigService::getCompanyName())
                    );
                    $result['fallback_email_attempted'] = true;
                    $result['success'] = true;
                    Log::warning('SmsService: SMS échoué — fallback email envoyé', [
                        'phone' => substr($phone, 0, -4) . '****',
                        'type'  => $type,
                        'email' => substr($userEmail, 0, 3) . '***',
                    ]);
                } catch (\Throwable $e) {
                    Log::critical('SmsService: SMS ET email fallback échoués', [
                        'phone' => substr($phone, 0, -4) . '****',
                        'type'  => $type,
                        'error' => $e->getMessage(),
                    ]);
                }
            } else {
                Log::error('SmsService: SMS échoué, aucun email de fallback trouvé', [
                    'phone' => substr($phone, 0, -4) . '****',
                    'type'  => $type,
                    'sms_error' => $result['error'] ?? 'unknown',
                ]);
            }
        }

        // Ne pas retourner le code en production
        if (config('app.debug') || ($result['demo'] ?? false)) {
            $result['otp_code'] = $otp; // Pour tests seulement
        }

        Log::info('OTP envoyé', [
            'phone' => substr($phone, 0, -4) . '****',
            'type' => $type,
            'success' => $result['success']
        ]);

        return $result;
    }
    
    /**
     * Vérifier un code OTP
     * 
     * @param string $phone
     * @param string $code Code OTP fourni
     * @param string $type
     * @return array
     */
    public static function verifyOtp(string $phone, string $code, string $type = 'verify_phone'): array
    {
        $phone = self::normalizePhone($phone);
        $cacheKey = "otp_{$type}_{$phone}";
        
        $otpData = Cache::get($cacheKey);
        
        if (!$otpData) {
            return [
                'valid' => false,
                'error' => 'Code expiré ou invalide. Veuillez demander un nouveau code.'
            ];
        }
        
        // Vérifier le nombre de tentatives
        if (($otpData['attempts'] ?? 0) >= 5) {
            Cache::forget($cacheKey);
            return [
                'valid' => false,
                'error' => 'Trop de tentatives. Veuillez demander un nouveau code.'
            ];
        }
        
        // Incrémenter les tentatives
        $otpData['attempts'] = ($otpData['attempts'] ?? 0) + 1;
        Cache::put($cacheKey, $otpData, now()->addMinutes(self::OTP_EXPIRY_MINUTES));
        
        // Vérifier le code
        if ($otpData['code'] === $code) {
            Cache::forget($cacheKey);
            
            Log::info('OTP vérifié avec succès', [
                'phone' => substr($phone, 0, -4) . '****',
                'type' => $type
            ]);
            
            return [
                'valid' => true,
                'message' => 'Code vérifié avec succès'
            ];
        }
        
        return [
            'valid' => false,
            'error' => 'Code incorrect',
            'attempts_remaining' => 5 - $otpData['attempts']
        ];
    }
    
    /**
     * Envoyer une notification SMS pour une commande
     * 
     * @param string $phone
     * @param string $orderNo
     * @param string $status
     * @param array $extra
     * @return array
     */
    public static function sendOrderNotification(string $phone, string $orderNo, string $status, array $extra = []): array
    {
        $companyName = ConfigService::getCompanyName();
        $messages = [
            'confirmed' => "Commande #{$orderNo} confirmée! Préparation en cours. Suivez en temps réel sur {$companyName}.",
            'preparing' => "Votre commande #{$orderNo} est en préparation. Temps estimé: " . ($extra['eta'] ?? '20-30 min'),
            'ready' => "Commande #{$orderNo} prête! Le livreur arrive bientôt.",
            'picked_up' => "Commande #{$orderNo} récupérée par " . ($extra['driver_name'] ?? 'le livreur') . ". En route vers vous!",
            'on_the_way' => "Votre commande #{$orderNo} est en chemin! Arrivée dans ~" . ($extra['eta'] ?? '10 min'),
            'delivered' => "Commande #{$orderNo} livrée! Merci d'avoir choisi {$companyName}. Bon appétit!",
            'cancelled' => "Commande #{$orderNo} annulée. " . ($extra['reason'] ?? 'Contactez-nous pour plus d\'infos.'),
        ];
        
        $message = $messages[$status] ?? "Mise à jour commande #{$orderNo}: {$status}";
        
        return self::send($phone, $message);
    }
    
    /**
     * Envoyer via MTN SMS v3 API (Congo +242)
     * Auth : OAuth2 Client Credentials → Bearer token mis en cache 55 min
     */
    protected static function sendViaMtnSms(string $phone, string $message): array
    {
        $cfg = config('external-services.notifications.mtn_sms');

        try {
            $token = self::mtnOAuthToken($cfg);
            if (!$token) {
                return ['success' => false, 'error' => 'mtn_token_failed', 'provider' => 'mtn_sms'];
            }

            $correlator = 'BD-' . Str::upper(Str::random(10)) . '-' . time();
            $headers    = [
                'Authorization'            => 'Bearer ' . $token,
                'Content-Type'             => 'application/json',
                'Accept'                   => 'application/json',
            ];
            if (!empty($cfg['subscription_key'])) {
                $headers['Ocp-Apim-Subscription-Key'] = $cfg['subscription_key'];
            }

            $response = Http::withHeaders($headers)
                ->timeout(15)
                ->post($cfg['send_url'], [
                    'outboundSMSMessageRequest' => [
                        'address'              => [$phone],
                        'senderAddress'        => $cfg['sender_id'] ?? 'BantuDelice',
                        'outboundSMSTextMessage' => ['message' => $message],
                        'clientCorrelator'     => $correlator,
                        'requestDeliveryReceipt' => false,
                    ],
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $resourceUrl = data_get($data, 'outboundSMSMessageRequest.resourceURL')
                    ?? data_get($data, 'resourceReference.resourceURL')
                    ?? $correlator;
                Log::info('[MTN SMS] envoyé', ['to' => substr($phone, 0, -4).'****', 'correlator' => $correlator]);
                return ['success' => true, 'message_id' => $resourceUrl, 'provider' => 'mtn_sms'];
            }

            $err = $response->json()['message'] ?? $response->json()['error'] ?? $response->body();
            Log::warning('[MTN SMS] échec HTTP', ['status' => $response->status(), 'error' => $err]);
            return ['success' => false, 'error' => $err, 'provider' => 'mtn_sms'];

        } catch (\Throwable $e) {
            Log::error('[MTN SMS] exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage(), 'provider' => 'mtn_sms'];
        }
    }

    /**
     * Obtenir (ou renouveler) le token OAuth2 MTN — mis en cache 55 min
     */
    protected static function mtnOAuthToken(array $cfg): ?string
    {
        $cacheKey = 'mtn_sms_oauth_token';
        $cached   = Cache::get($cacheKey);
        if ($cached) return $cached;

        try {
            $response = Http::withBasicAuth($cfg['consumer_key'], $cfg['consumer_secret'])
                ->timeout(10)
                ->asForm()
                ->post($cfg['token_url'], ['grant_type' => 'client_credentials']);

            if (!$response->successful()) {
                Log::warning('[MTN SMS] token request échoué', ['status' => $response->status()]);
                return null;
            }

            $token = $response->json()['access_token'] ?? null;
            if (!$token) return null;

            $ttl = max(60, ($response->json()['expires_in'] ?? 3600) - 300);
            Cache::put($cacheKey, $token, $ttl);
            return $token;

        } catch (\Throwable $e) {
            Log::error('[MTN SMS] token exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Envoyer via Twilio
     */
    protected static function sendViaTwilio(string $phone, string $message): array
    {
        $config = config('external-services.notifications.twilio');
        
        try {
            $response = Http::withBasicAuth($config['sid'], $config['token'])
                ->asForm()
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$config['sid']}/Messages.json", [
                    'From' => $config['from'],
                    'To' => $phone,
                    'Body' => $message,
                ]);
            
            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'message_id' => $data['sid'] ?? null,
                    'provider' => 'twilio'
                ];
            }
            
            Log::error('Twilio SMS Error', ['response' => $response->json()]);
            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Erreur Twilio',
                'provider' => 'twilio'
            ];
            
        } catch (\Exception $e) {
            Log::error('Twilio Exception', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'provider' => 'twilio'
            ];
        }
    }
    
    /**
     * Envoyer via Africa's Talking
     */
    protected static function sendViaAfricasTalking(string $phone, string $message): array
    {
        $config = config('external-services.notifications.africastalking');
        
        try {
            $response = Http::withHeaders([
                'apiKey' => $config['api_key'],
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'application/json',
            ])
            ->asForm()
            ->post('https://api.africastalking.com/version1/messaging', [
                'username' => $config['username'],
                'to' => $phone,
                'message' => $message,
                'from' => $config['from'],
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                $recipient = $data['SMSMessageData']['Recipients'][0] ?? null;
                
                if ($recipient && $recipient['status'] === 'Success') {
                    return [
                        'success' => true,
                        'message_id' => $recipient['messageId'] ?? null,
                        'provider' => 'africastalking'
                    ];
                }
            }
            
            Log::error('Africa\'s Talking SMS Error', ['response' => $response->json()]);
            return [
                'success' => false,
                'error' => 'Erreur Africa\'s Talking',
                'provider' => 'africastalking'
            ];
            
        } catch (\Exception $e) {
            Log::error('Africa\'s Talking Exception', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'provider' => 'africastalking'
            ];
        }
    }
    
    /**
     * Envoyer via BulkGate
     */
    protected static function sendViaBulkGate(string $phone, string $message): array
    {
        $config = config('external-services.notifications.bulkgate');
        
        try {
            // BulkGate utilise une authentification basique avec application_id et api_key
            $response = Http::withBasicAuth($config['application_id'], $config['api_key'])
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($config['api_url'], [
                    'number' => $phone,
                    'text' => $message,
                    'sender_id' => $config['sender_id'],
                    'sender_id_value' => $config['sender_id'], // Valeur du sender ID
                ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                // BulkGate retourne généralement un objet avec un ID de message
                if (isset($data['data']['id']) || isset($data['message_id']) || isset($data['id'])) {
                    return [
                        'success' => true,
                        'message_id' => $data['data']['id'] ?? $data['message_id'] ?? $data['id'] ?? null,
                        'provider' => 'bulkgate'
                    ];
                }
                
                // Si la réponse indique un succès même sans ID explicite
                if (isset($data['status']) && $data['status'] === 'success') {
                    return [
                        'success' => true,
                        'message_id' => 'BULKGATE-' . time(),
                        'provider' => 'bulkgate'
                    ];
                }
            }
            
            Log::error('BulkGate SMS Error', [
                'response' => $response->json(),
                'status' => $response->status()
            ]);
            
            return [
                'success' => false,
                'error' => $response->json()['error'] ?? $response->json()['message'] ?? 'Erreur BulkGate',
                'provider' => 'bulkgate',
                'response' => $response->json()
            ];
            
        } catch (\Exception $e) {
            Log::error('BulkGate Exception', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'provider' => 'bulkgate'
            ];
        }
    }
    
    /**
     * Envoyer via provider local Congo
     */
    protected static function sendViaLocalProvider(string $phone, string $message): array
    {
        $config = config('external-services.notifications.sms_local');
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $config['api_key'],
                'Content-Type' => 'application/json',
            ])
            ->post($config['api_url'] . '/sms/send', [
                'to' => $phone,
                'message' => $message,
                'sender_id' => $config['sender_id'],
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => $data['status'] === 'success',
                    'message_id' => $data['message_id'] ?? null,
                    'provider' => 'sms_local'
                ];
            }
            
            return [
                'success' => false,
                'error' => 'Erreur provider local',
                'provider' => 'sms_local'
            ];
            
        } catch (\Exception $e) {
            Log::error('Local SMS Provider Exception', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'provider' => 'sms_local'
            ];
        }
    }
    
    /**
     * Générer un code OTP
     */
    protected static function generateOtp(): string
    {
        return str_pad((string)random_int(0, pow(10, self::OTP_LENGTH) - 1), self::OTP_LENGTH, '0', STR_PAD_LEFT);
    }
    
    /**
     * Normaliser un numéro de téléphone au format international
     */
    public static function normalizePhone(string $phone): string
    {
        // Supprimer espaces et caractères spéciaux
        $phone = preg_replace('/[\s\-\.\(\)]/', '', $phone);
        
        // Si commence par 0, ajouter le préfixe Congo (+242)
        if (preg_match('/^0[456]/', $phone)) {
            $phone = '+242' . substr($phone, 1);
        }
        
        // Si ne commence pas par +, ajouter
        if (!str_starts_with($phone, '+')) {
            // Assumer Congo si pas de préfixe
            if (preg_match('/^242/', $phone)) {
                $phone = '+' . $phone;
            } else {
                $phone = '+242' . $phone;
            }
        }
        
        return $phone;
    }
    
    /**
     * Vérifier si un numéro est valide pour le Congo
     */
    public static function isValidCongoPhone(string $phone): bool
    {
        $phone = self::normalizePhone($phone);
        
        // Format: +242 0X XXX XX XX (MTN: 04/05/06, Airtel: 05/06)
        return (bool)preg_match('/^\+242[0456]\d{7,8}$/', $phone);
    }
}

