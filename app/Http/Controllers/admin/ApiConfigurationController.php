<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use App\Services\SmsService;
use App\Services\GeolocationService;
use App\Services\MobileMoneyService;
use App\Services\SocialAuthService;
use App\Services\EnvConfigService;
use App\Services\ConfigService;
use App\Payment;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ApiConfigurationController extends Controller
{
    /**
     * Page de configuration des API
     */
    public function index()
    {
        $config = config('external-services');
        
        return view('admin.api-configuration', [
            'config' => $config,
            'google_maps_key' => env('GOOGLE_MAPS_API_KEY'),
            'twilio_enabled' => env('TWILIO_ENABLED', false),
            'mtn_enabled' => env('MTN_MOMO_ENABLED', false),
            'airtel_enabled' => env('AIRTEL_MONEY_ENABLED', false),
        ]);
    }
    
    /**
     * Tester l'envoi SMS
     */
    public function testSms(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'message' => 'nullable|string',
        ]);
        
        try {
            $phone = $request->input('phone');
            $companyName = ConfigService::getCompanyName();
            $message = $request->input('message', 'Test SMS depuis ' . $companyName . ' - ' . now()->format('H:i'));
            
            $result = SmsService::send($phone, $message);
            
            return response()->json([
                'success' => $result['success'] ?? false,
                'message' => $result['success'] 
                    ? 'SMS envoyé avec succès!' 
                    : 'Erreur: ' . ($result['error'] ?? 'Inconnue'),
                'demo' => $result['demo'] ?? false,
                'result' => $result,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Tester l'envoi OTP
     */
    public function testOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
        ]);
        
        try {
            $phone = $request->input('phone');
            $result = SmsService::sendOtp($phone, 'verify_phone');
            
            return response()->json([
                'success' => $result['success'] ?? false,
                'message' => $result['success'] 
                    ? 'Code OTP envoyé!' 
                    : 'Erreur: ' . ($result['error'] ?? 'Inconnue'),
                'demo' => $result['demo'] ?? false,
                'otp_code' => $result['otp_code'] ?? null, // Seulement en mode démo
                'result' => $result,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Tester la géolocalisation
     */
    public function testGeolocation(Request $request)
    {
        $request->validate([
            'address' => 'nullable|string',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'lat2' => 'nullable|numeric',
            'lng2' => 'nullable|numeric',
        ]);
        
        try {
            if ($request->has('address') && $request->input('address')) {
                // Test géocodage
                $result = GeolocationService::geocode($request->input('address'));
                
                return response()->json([
                    'success' => !isset($result['error']),
                    'message' => isset($result['error']) ? 'Erreur de géocodage' : 'Géocodage réussi',
                    'result' => $result,
                ]);
            }
            
            if ($request->has(['lat', 'lng']) && !$request->has('lat2')) {
                // Test géocodage inverse
                $result = GeolocationService::reverseGeocode(
                    (float)$request->input('lat'),
                    (float)$request->input('lng')
                );
                
                return response()->json([
                    'success' => !isset($result['error']),
                    'message' => isset($result['error']) ? 'Erreur de géocodage inverse' : 'Géocodage inverse réussi',
                    'result' => $result,
                ]);
            }
            
            // Test calcul distance
            $lat1 = $request->input('lat', -4.2767);
            $lng1 = $request->input('lng', 15.2832);
            $lat2 = $request->input('lat2', -4.2700);
            $lng2 = $request->input('lng2', 15.2600);
            
            $distance = GeolocationService::calculateDistance(
                (float)$lat1, (float)$lng1,
                (float)$lat2, (float)$lng2
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Calcul de distance réussi',
                'distance' => $distance,
                'distance_text' => number_format($distance, 2) . ' km',
                'result' => [
                    'point1' => ['lat' => $lat1, 'lng' => $lng1],
                    'point2' => ['lat' => $lat2, 'lng' => $lng2],
                    'distance_km' => round($distance, 2),
                ],
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Tester Mobile Money (simulation)
     */
    public function testMobileMoney(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'amount' => 'required|numeric|min:100',
            'operator' => 'required|in:mtn,airtel',
        ]);
        
        try {
            // Vérifier la configuration
            $operator = $request->input('operator');
            $configKey = $operator === 'mtn' ? 'payments.mtn_momo' : 'payments.airtel_money';
            $config = config("external-services.{$configKey}");
            
            $isConfigured = $config['enabled'] ?? false;
            
            if (!$isConfigured) {
                return response()->json([
                    'success' => true,
                    'message' => 'Mode démo: Configuration non activée. Le paiement serait simulé.',
                    'demo' => true,
                    'result' => [
                        'operator' => $operator,
                        'phone' => $request->input('phone'),
                        'amount' => $request->input('amount'),
                        'status' => 'SIMULATED',
                        'message' => 'Pour activer les paiements réels, configurez les clés API dans le .env',
                    ],
                ]);
            }
            
            // Si configuré, on pourrait créer un vrai paiement de test
            // Pour l'instant, on simule
            return response()->json([
                'success' => true,
                'message' => 'Service configuré. Les paiements réels sont possibles.',
                'demo' => false,
                'result' => [
                    'operator' => $operator,
                    'configured' => true,
                    'note' => 'Pour tester un vrai paiement, créez une commande depuis le frontend',
                ],
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Vider le cache de configuration
     */
    public function clearCache()
    {
        try {
            Artisan::call('config:clear');
            Artisan::call('cache:clear');
            
            return response()->json([
                'success' => true,
                'message' => 'Cache vidé avec succès!',
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Obtenir le statut des API
     */
    public function getStatus()
    {
        $status = [
            'geolocation' => [
                'google_maps' => [
                    'enabled' => config('external-services.geolocation.google_maps.enabled'),
                    'configured' => !empty(env('GOOGLE_MAPS_API_KEY')),
                    'provider' => !empty(env('GOOGLE_MAPS_API_KEY')) ? 'Google Maps' : 'OpenStreetMap',
                ],
                'openstreetmap' => [
                    'enabled' => config('external-services.geolocation.openstreetmap.enabled', true),
                    'configured' => true, // Toujours disponible
                ],
            ],
            'sms' => [
                'twilio' => [
                    'enabled' => config('external-services.notifications.twilio.enabled'),
                    'configured' => !empty(env('TWILIO_SID')) && !empty(env('TWILIO_TOKEN')),
                ],
                'africastalking' => [
                    'enabled' => config('external-services.notifications.africastalking.enabled'),
                    'configured' => !empty(env('AFRICASTALKING_API_KEY')),
                ],
                'bulkgate' => [
                    'enabled' => config('external-services.notifications.bulkgate.enabled'),
                    'configured' => !empty(env('BULKGATE_APPLICATION_ID')) && !empty(env('BULKGATE_API_KEY')),
                ],
            ],
            'mobile_money' => [
                'mtn_momo' => [
                    'enabled' => config('external-services.payments.mtn_momo.enabled'),
                    'configured' => !empty(env('MTN_MOMO_API_KEY')),
                ],
                'airtel_money' => [
                    'enabled' => config('external-services.payments.airtel_money.enabled'),
                    'configured' => !empty(env('AIRTEL_MONEY_CLIENT_ID')),
                ],
            ],
            'social_auth' => [
                'google' => [
                    'enabled' => config('external-services.social_auth.google.enabled'),
                    'configured' => !empty(env('GOOGLE_CLIENT_ID')),
                ],
                'facebook' => [
                    'enabled' => config('external-services.social_auth.facebook.enabled'),
                    'configured' => !empty(env('FACEBOOK_CLIENT_ID')),
                ],
            ],
            'email' => [
                'smtp' => [
                    'enabled' => env('MAIL_ENABLED', false),
                    'configured' => !empty(env('MAIL_HOST')) && !empty(env('MAIL_USERNAME')) && !empty(env('MAIL_PASSWORD')),
                    'host' => env('MAIL_HOST'),
                    'port' => env('MAIL_PORT', 587),
                    'from_address' => env('MAIL_FROM_ADDRESS'),
                ],
            ],
        ];
        
        return response()->json([
            'success' => true,
            'status' => $status,
        ]);
    }
    
    /**
     * Obtenir les valeurs actuelles des clés API (partiellement masquées)
     */
    public function getApiKeys()
    {
        $keys = [
            // Google Maps
            'GOOGLE_MAPS_API_KEY' => EnvConfigService::getEnvValue('GOOGLE_MAPS_API_KEY'),
            
            // Twilio
            'TWILIO_SID' => EnvConfigService::getEnvValue('TWILIO_SID'),
            'TWILIO_TOKEN' => EnvConfigService::getEnvValue('TWILIO_TOKEN'),
            'TWILIO_FROM' => EnvConfigService::getEnvValue('TWILIO_FROM'),
            'TWILIO_VERIFY_SID' => EnvConfigService::getEnvValue('TWILIO_VERIFY_SID'),
            
            // Africa's Talking
            'AFRICASTALKING_USERNAME' => EnvConfigService::getEnvValue('AFRICASTALKING_USERNAME'),
            'AFRICASTALKING_API_KEY' => EnvConfigService::getEnvValue('AFRICASTALKING_API_KEY'),
            'AFRICASTALKING_FROM' => EnvConfigService::getEnvValue('AFRICASTALKING_FROM'),
            
            // BulkGate
            'BULKGATE_APPLICATION_ID' => EnvConfigService::getEnvValue('BULKGATE_APPLICATION_ID'),
            'BULKGATE_API_KEY' => EnvConfigService::getEnvValue('BULKGATE_API_KEY'),
            'BULKGATE_SENDER_ID' => EnvConfigService::getEnvValue('BULKGATE_SENDER_ID'),
            
            // MTN MoMo
            'MTN_MOMO_API_KEY' => EnvConfigService::getEnvValue('MTN_MOMO_API_KEY'),
            'MTN_MOMO_API_USER' => EnvConfigService::getEnvValue('MTN_MOMO_API_USER'),
            'MTN_MOMO_API_SECRET' => EnvConfigService::getEnvValue('MTN_MOMO_API_SECRET'),
            'MTN_MOMO_SUBSCRIPTION_KEY' => EnvConfigService::getEnvValue('MTN_MOMO_SUBSCRIPTION_KEY'),
            
            // Airtel Money
            'AIRTEL_MONEY_CLIENT_ID' => EnvConfigService::getEnvValue('AIRTEL_MONEY_CLIENT_ID'),
            'AIRTEL_MONEY_CLIENT_SECRET' => EnvConfigService::getEnvValue('AIRTEL_MONEY_CLIENT_SECRET'),
            
            // Social Auth - Google
            'GOOGLE_CLIENT_ID' => EnvConfigService::getEnvValue('GOOGLE_CLIENT_ID'),
            'GOOGLE_CLIENT_SECRET' => EnvConfigService::getEnvValue('GOOGLE_CLIENT_SECRET'),
            
            // Social Auth - Facebook
            'FACEBOOK_CLIENT_ID' => EnvConfigService::getEnvValue('FACEBOOK_CLIENT_ID'),
            'FACEBOOK_CLIENT_SECRET' => EnvConfigService::getEnvValue('FACEBOOK_CLIENT_SECRET'),
            
            // Email SMTP
            'MAIL_MAILER' => EnvConfigService::getEnvValue('MAIL_MAILER'),
            'MAIL_HOST' => EnvConfigService::getEnvValue('MAIL_HOST'),
            'MAIL_PORT' => EnvConfigService::getEnvValue('MAIL_PORT'),
            'MAIL_USERNAME' => EnvConfigService::getEnvValue('MAIL_USERNAME'),
            'MAIL_PASSWORD' => EnvConfigService::getEnvValue('MAIL_PASSWORD'),
            'MAIL_ENCRYPTION' => EnvConfigService::getEnvValue('MAIL_ENCRYPTION'),
            'MAIL_FROM_ADDRESS' => EnvConfigService::getEnvValue('MAIL_FROM_ADDRESS'),
            'MAIL_FROM_NAME' => EnvConfigService::getEnvValue('MAIL_FROM_NAME'),
            'MAIL_ENABLED' => EnvConfigService::getEnvValue('MAIL_ENABLED'),
        ];
        
        // Masquer partiellement les valeurs sensibles
        $maskedKeys = [];
        foreach ($keys as $key => $value) {
            if (!empty($value)) {
                $maskedKeys[$key] = EnvConfigService::maskValue($value);
            } else {
                $maskedKeys[$key] = '';
            }
        }
        
        return response()->json([
            'success' => true,
            'keys' => $maskedKeys,
            'has_values' => array_filter($keys, fn($v) => !empty($v)),
        ]);
    }
    
    /**
     * Sauvegarder les clés API
     */
    public function saveApiKeys(Request $request)
    {
        $request->validate([
            'keys' => 'required|array',
        ]);
        
        try {
            $keys = $request->input('keys');
            
            // Filtrer les valeurs vides (ne pas écraser avec des chaînes vides)
            $keysToUpdate = [];
            foreach ($keys as $key => $value) {
                if (!empty($value) && $value !== '') {
                    $keysToUpdate[$key] = $value;
                }
            }
            
            if (empty($keysToUpdate)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune clé à mettre à jour',
                ], 400);
            }
            
            $result = EnvConfigService::updateEnvVariables($keysToUpdate);
            
            if ($result['success']) {
                // Vider le cache après mise à jour
                Artisan::call('config:clear');
                
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'updated' => $result['updated'] ?? [],
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Erreur lors de la sauvegarde',
            ], 500);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Obtenir le statut de la configuration email
     */
    public function getMailStatus()
    {
        $host = env('MAIL_HOST');
        $username = env('MAIL_USERNAME');
        $password = env('MAIL_PASSWORD');
        $port = env('MAIL_PORT', 587);
        $encryption = env('MAIL_ENCRYPTION', 'tls');
        $fromAddress = env('MAIL_FROM_ADDRESS');
        $fromName = env('MAIL_FROM_NAME');
        $enabled = env('MAIL_ENABLED', false);
        
        $configured = !empty($host) && !empty($username) && !empty($password);
        
        return response()->json([
            'success' => true,
            'status' => [
                'enabled' => $enabled,
                'configured' => $configured,
                'host' => $host,
                'port' => $port,
                'encryption' => $encryption,
                'from_address' => $fromAddress,
                'from_name' => $fromName,
                'username' => $username ? EnvConfigService::maskValue($username) : null,
            ],
        ]);
    }
    
    /**
     * Sauvegarder la configuration email
     */
    public function saveMailConfig(Request $request)
    {
        $request->validate([
            'MAIL_HOST' => 'required|string',
            'MAIL_PORT' => 'required|integer|min:1|max:65535',
            'MAIL_USERNAME' => 'required|string',
            'MAIL_PASSWORD' => 'required|string',
            'MAIL_ENCRYPTION' => 'nullable|string|in:tls,ssl',
            'MAIL_FROM_ADDRESS' => 'required|email',
            'MAIL_FROM_NAME' => 'nullable|string',
            'MAIL_ENABLED' => 'nullable|boolean',
        ]);
        
        try {
            $keys = [
                'MAIL_MAILER' => 'smtp',
                'MAIL_HOST' => $request->input('MAIL_HOST'),
                'MAIL_PORT' => $request->input('MAIL_PORT'),
                'MAIL_USERNAME' => $request->input('MAIL_USERNAME'),
                'MAIL_PASSWORD' => $request->input('MAIL_PASSWORD'),
                'MAIL_ENCRYPTION' => $request->input('MAIL_ENCRYPTION', 'ssl'),
                'MAIL_FROM_ADDRESS' => $request->input('MAIL_FROM_ADDRESS'),
                'MAIL_FROM_NAME' => $request->input('MAIL_FROM_NAME', ConfigService::getCompanyName()),
                'MAIL_ENABLED' => $request->input('MAIL_ENABLED', true) ? 'true' : 'false',
            ];
            
            $result = EnvConfigService::updateEnvVariables($keys);
            
            if ($result['success']) {
                Artisan::call('config:clear');
                
                return response()->json([
                    'success' => true,
                    'message' => 'Configuration email sauvegardée avec succès!',
                    'updated' => $result['updated'] ?? [],
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Erreur lors de la sauvegarde',
            ], 500);
            
        } catch (\Exception $e) {
            Log::error('Erreur sauvegarde config email', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Tester l'envoi d'email
     */
    public function testEmail(Request $request)
    {
        $request->validate([
            'to' => 'required|email',
            'subject' => 'nullable|string',
            'message' => 'nullable|string',
        ]);
        
        try {
            $to = $request->input('to');
            $companyName = ConfigService::getCompanyName();
            $subject = $request->input('subject', 'Test Email - ' . $companyName);
            $message = $request->input('message', 'Ceci est un email de test depuis ' . $companyName . '. Si vous recevez ce message, la configuration SMTP fonctionne correctement.');
            
            // Vérifier la configuration
            $host = env('MAIL_HOST');
            $username = env('MAIL_USERNAME');
            $password = env('MAIL_PASSWORD');
            
            if (empty($host) || empty($username) || empty($password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Configuration SMTP incomplète. Veuillez configurer MAIL_HOST, MAIL_USERNAME et MAIL_PASSWORD.',
                ], 400);
            }
            
            // Envoyer l'email de test
            Mail::raw($message, function ($mail) use ($to, $subject) {
                $mail->to($to)
                     ->subject($subject)
                     ->from(ConfigService::getNoreplyEmail(), ConfigService::getCompanyName());
            });
            
            return response()->json([
                'success' => true,
                'message' => 'Email de test envoyé avec succès à ' . $to,
                'result' => [
                    'to' => $to,
                    'subject' => $subject,
                    'sent_at' => now()->toDateTimeString(),
                ],
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur envoi email test', [
                'error' => $e->getMessage(),
                'to' => $request->input('to'),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi: ' . $e->getMessage(),
                'error_details' => config('app.debug') ? $e->getTraceAsString() : null,
            ], 500);
        }
    }
}

