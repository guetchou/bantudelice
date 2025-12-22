<?php

namespace App\Services;

use App\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Service Mobile Money unifié pour MTN MoMo et Airtel Money
 * Adapté pour le marché congolais (Congo-Brazzaville)
 */
class MobileMoneyService
{
    const CURRENCY = 'XAF';
    const COUNTRY = 'CG'; // Congo-Brazzaville
    
    /**
     * Détecter l'opérateur à partir du numéro de téléphone
     * 
     * @param string $phone
     * @return string mtn|airtel|unknown
     */
    public static function detectOperator(string $phone): string
    {
        $phone = SmsService::normalizePhone($phone);
        
        // Préfixes MTN Congo: 04, 05, 06
        if (preg_match('/^\+242(04|05|06)/', $phone)) {
            return 'mtn';
        }
        
        // Préfixes Airtel Congo: 05, 06 (chevauchement avec MTN)
        // Note: En pratique, il faut vérifier avec les API des opérateurs
        if (preg_match('/^\+242(05|06)/', $phone)) {
            return 'airtel'; // ou demander à l'utilisateur
        }
        
        return 'unknown';
    }
    
    /**
     * Initier un paiement Mobile Money
     * 
     * @param Payment $payment
     * @param string $phone Numéro du payeur
     * @param string $operator mtn|airtel (optionnel, auto-détecté)
     * @return array
     */
    public static function initiatePayment(Payment $payment, string $phone, ?string $operator = null): array
    {
        $phone = SmsService::normalizePhone($phone);
        $operator = $operator ?? self::detectOperator($phone);
        
        switch ($operator) {
            case 'mtn':
                return self::initiateMtnPayment($payment, $phone);
            case 'airtel':
                return self::initiateAirtelPayment($payment, $phone);
            default:
                return self::initiateDemoPayment($payment, $phone);
        }
    }
    
    /**
     * Initier un paiement MTN MoMo
     */
    protected static function initiateMtnPayment(Payment $payment, string $phone): array
    {
        $config = config('external-services.payments.mtn_momo');
        
        if (!$config['enabled']) {
            Log::warning('MTN MoMo non configuré, mode démo');
            return self::initiateDemoPayment($payment, $phone, 'mtn');
        }
        
        $environment = $config['environment'];
        $baseUrl = $config['base_url'][$environment];
        
        try {
            // Obtenir un token d'accès
            $accessToken = self::getMtnAccessToken($config, $baseUrl);
            
            if (!$accessToken) {
                throw new \RuntimeException('Impossible d\'obtenir un token MTN');
            }
            
            // Générer une référence unique
            $externalId = 'BD-' . $payment->id . '-' . time();
            $referenceId = Str::uuid()->toString();
            
            // Créer la requête de paiement
            $callbackUrl = route('api.payments.callback', ['provider' => 'mtn_momo']);
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'X-Reference-Id' => $referenceId,
                'X-Target-Environment' => $environment,
                'Ocp-Apim-Subscription-Key' => $config['subscription_key'],
                'Content-Type' => 'application/json',
            ])
            ->post($baseUrl . '/collection/v1_0/requesttopay', [
                'amount' => (string)$payment->amount,
                'currency' => self::CURRENCY,
                'externalId' => $externalId,
                'payer' => [
                    'partyIdType' => 'MSISDN',
                    'partyId' => ltrim($phone, '+'),
                ],
                'payerMessage' => 'Commande ' . \App\Services\ConfigService::getCompanyName() . ' #' . ($payment->order->order_no ?? $payment->id),
                'payeeNote' => 'Paiement commande',
            ]);
            
            if ($response->status() === 202) {
                // Paiement initié avec succès
                Log::info('MTN MoMo payment initiated', [
                    'payment_id' => $payment->id,
                    'reference_id' => $referenceId,
                    'external_id' => $externalId,
                ]);
                
                return [
                    'success' => true,
                    'provider' => 'mtn_momo',
                    'provider_reference' => $referenceId,
                    'external_id' => $externalId,
                    'status' => 'PENDING',
                    'message' => 'Veuillez confirmer le paiement sur votre téléphone MTN MoMo',
                    'instructions' => [
                        'Vous allez recevoir une notification sur votre téléphone',
                        'Entrez votre code PIN MTN MoMo pour confirmer',
                        'Le paiement sera validé automatiquement',
                    ],
                ];
            }
            
            Log::error('MTN MoMo payment failed', [
                'payment_id' => $payment->id,
                'status' => $response->status(),
                'response' => $response->json(),
            ]);
            
            return [
                'success' => false,
                'provider' => 'mtn_momo',
                'error' => 'Erreur lors de l\'initiation du paiement MTN',
                'details' => $response->json(),
            ];
            
        } catch (\Exception $e) {
            Log::error('MTN MoMo exception', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'provider' => 'mtn_momo',
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Obtenir un token d'accès MTN
     */
    protected static function getMtnAccessToken(array $config, string $baseUrl): ?string
    {
        $cacheKey = 'mtn_momo_access_token';
        
        // Vérifier le cache
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        
        try {
            $response = Http::withBasicAuth($config['api_user'], $config['api_key'])
                ->withHeaders([
                    'Ocp-Apim-Subscription-Key' => $config['subscription_key'],
                ])
                ->post($baseUrl . '/collection/token/');
            
            if ($response->successful()) {
                $data = $response->json();
                $token = $data['access_token'];
                $expiresIn = $data['expires_in'] ?? 3600;
                
                // Mettre en cache avec une marge de sécurité
                Cache::put($cacheKey, $token, now()->addSeconds($expiresIn - 60));
                
                return $token;
            }
            
            Log::error('MTN access token failed', ['response' => $response->json()]);
            return null;
            
        } catch (\Exception $e) {
            Log::error('MTN access token exception', ['error' => $e->getMessage()]);
            return null;
        }
    }
    
    /**
     * Initier un paiement Airtel Money
     */
    protected static function initiateAirtelPayment(Payment $payment, string $phone): array
    {
        $config = config('external-services.payments.airtel_money');
        
        if (!$config['enabled']) {
            Log::warning('Airtel Money non configuré, mode démo');
            return self::initiateDemoPayment($payment, $phone, 'airtel');
        }
        
        $environment = $config['environment'];
        $baseUrl = $config['base_url'][$environment];
        
        try {
            // Obtenir un token d'accès
            $accessToken = self::getAirtelAccessToken($config, $baseUrl);
            
            if (!$accessToken) {
                throw new \RuntimeException('Impossible d\'obtenir un token Airtel');
            }
            
            // Référence unique
            $reference = 'BD-' . $payment->id . '-' . time();
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
                'X-Country' => $config['country'],
                'X-Currency' => $config['currency'],
            ])
            ->post($baseUrl . '/merchant/v1/payments/', [
                'reference' => $reference,
                'subscriber' => [
                    'country' => $config['country'],
                    'currency' => $config['currency'],
                    'msisdn' => ltrim($phone, '+'),
                ],
                'transaction' => [
                    'amount' => $payment->amount,
                    'country' => $config['country'],
                    'currency' => $config['currency'],
                    'id' => $reference,
                ],
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['status']['success'] ?? false) {
                    Log::info('Airtel Money payment initiated', [
                        'payment_id' => $payment->id,
                        'reference' => $reference,
                    ]);
                    
                    return [
                        'success' => true,
                        'provider' => 'airtel_money',
                        'provider_reference' => $data['data']['transaction']['id'] ?? $reference,
                        'status' => 'PENDING',
                        'message' => 'Veuillez confirmer le paiement sur votre téléphone Airtel Money',
                        'instructions' => [
                            'Composez *143# sur votre téléphone Airtel',
                            'Sélectionnez "Approuver un paiement"',
                            'Entrez votre code PIN pour confirmer',
                        ],
                    ];
                }
            }
            
            Log::error('Airtel Money payment failed', [
                'payment_id' => $payment->id,
                'response' => $response->json(),
            ]);
            
            return [
                'success' => false,
                'provider' => 'airtel_money',
                'error' => 'Erreur lors de l\'initiation du paiement Airtel',
            ];
            
        } catch (\Exception $e) {
            Log::error('Airtel Money exception', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'provider' => 'airtel_money',
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Obtenir un token d'accès Airtel
     */
    protected static function getAirtelAccessToken(array $config, string $baseUrl): ?string
    {
        $cacheKey = 'airtel_money_access_token';
        
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        
        try {
            $response = Http::asForm()
                ->post($baseUrl . '/auth/oauth2/token', [
                    'client_id' => $config['client_id'],
                    'client_secret' => $config['client_secret'],
                    'grant_type' => 'client_credentials',
                ]);
            
            if ($response->successful()) {
                $data = $response->json();
                $token = $data['access_token'];
                $expiresIn = $data['expires_in'] ?? 3600;
                
                Cache::put($cacheKey, $token, now()->addSeconds($expiresIn - 60));
                
                return $token;
            }
            
            return null;
            
        } catch (\Exception $e) {
            Log::error('Airtel access token exception', ['error' => $e->getMessage()]);
            return null;
        }
    }
    
    /**
     * Vérifier le statut d'un paiement
     * 
     * @param string $provider mtn_momo|airtel_money
     * @param string $reference Référence du paiement
     * @return array
     */
    public static function checkPaymentStatus(string $provider, string $reference): array
    {
        switch ($provider) {
            case 'mtn_momo':
                return self::checkMtnPaymentStatus($reference);
            case 'airtel_money':
                return self::checkAirtelPaymentStatus($reference);
            default:
                return ['status' => 'UNKNOWN', 'error' => 'Provider non supporté'];
        }
    }
    
    /**
     * Vérifier le statut MTN
     */
    protected static function checkMtnPaymentStatus(string $referenceId): array
    {
        $config = config('external-services.payments.mtn_momo');
        
        if (!$config['enabled']) {
            return ['status' => 'DEMO', 'message' => 'Mode démo'];
        }
        
        $environment = $config['environment'];
        $baseUrl = $config['base_url'][$environment];
        
        try {
            $accessToken = self::getMtnAccessToken($config, $baseUrl);
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'X-Target-Environment' => $environment,
                'Ocp-Apim-Subscription-Key' => $config['subscription_key'],
            ])
            ->get($baseUrl . '/collection/v1_0/requesttopay/' . $referenceId);
            
            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'status' => $data['status'], // PENDING, SUCCESSFUL, FAILED
                    'provider' => 'mtn_momo',
                    'amount' => $data['amount'] ?? null,
                    'payer' => $data['payer']['partyId'] ?? null,
                    'reason' => $data['reason'] ?? null,
                ];
            }
            
            return ['status' => 'ERROR', 'error' => 'Impossible de vérifier le statut'];
            
        } catch (\Exception $e) {
            return ['status' => 'ERROR', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Vérifier le statut Airtel
     */
    protected static function checkAirtelPaymentStatus(string $transactionId): array
    {
        $config = config('external-services.payments.airtel_money');
        
        if (!$config['enabled']) {
            return ['status' => 'DEMO', 'message' => 'Mode démo'];
        }
        
        $environment = $config['environment'];
        $baseUrl = $config['base_url'][$environment];
        
        try {
            $accessToken = self::getAirtelAccessToken($config, $baseUrl);
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'X-Country' => $config['country'],
                'X-Currency' => $config['currency'],
            ])
            ->get($baseUrl . '/standard/v1/payments/' . $transactionId);
            
            if ($response->successful()) {
                $data = $response->json();
                
                $statusMap = [
                    'TS' => 'SUCCESSFUL',
                    'TF' => 'FAILED',
                    'TA' => 'PENDING',
                    'TIP' => 'PENDING',
                ];
                
                return [
                    'status' => $statusMap[$data['data']['transaction']['status']] ?? 'UNKNOWN',
                    'provider' => 'airtel_money',
                    'message' => $data['data']['transaction']['message'] ?? null,
                ];
            }
            
            return ['status' => 'ERROR', 'error' => 'Impossible de vérifier le statut'];
            
        } catch (\Exception $e) {
            return ['status' => 'ERROR', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Paiement en mode démo (pour tests)
     */
    protected static function initiateDemoPayment(Payment $payment, string $phone, string $provider = 'demo'): array
    {
        $reference = 'DEMO-' . $provider . '-' . $payment->id . '-' . time();
        
        Log::info('Mobile Money payment (DEMO MODE)', [
            'payment_id' => $payment->id,
            'phone' => $phone,
            'provider' => $provider,
            'reference' => $reference,
        ]);
        
        return [
            'success' => true,
            'demo' => true,
            'provider' => $provider,
            'provider_reference' => $reference,
            'status' => 'PENDING',
            'message' => 'Mode démo: Aucun paiement réel effectué',
            'instructions' => [
                'Le paiement est simulé en mode démo',
                'Configurez les clés API dans .env pour activer les paiements réels',
                'Variables: MTN_MOMO_* ou AIRTEL_MONEY_*',
            ],
            'note' => 'Configurez MTN_MOMO_ENABLED=true ou AIRTEL_MONEY_ENABLED=true dans .env',
        ];
    }
    
    /**
     * Traiter un callback Mobile Money
     * 
     * @param string $provider
     * @param array $payload
     * @return array
     */
    public static function handleCallback(string $provider, array $payload): array
    {
        Log::info('Mobile Money callback received', [
            'provider' => $provider,
            'payload' => $payload,
        ]);
        
        switch ($provider) {
            case 'mtn_momo':
                return self::handleMtnCallback($payload);
            case 'airtel_money':
                return self::handleAirtelCallback($payload);
            default:
                return ['success' => false, 'error' => 'Provider non supporté'];
        }
    }
    
    /**
     * Traiter callback MTN
     */
    protected static function handleMtnCallback(array $payload): array
    {
        $referenceId = $payload['referenceId'] ?? $payload['externalId'] ?? null;
        $status = $payload['status'] ?? null;
        
        if (!$referenceId) {
            return ['success' => false, 'error' => 'Référence manquante'];
        }
        
        // Trouver le paiement
        $payment = Payment::where('provider_reference', $referenceId)->first();
        
        if (!$payment) {
            return ['success' => false, 'error' => 'Paiement non trouvé'];
        }
        
        // Mettre à jour le statut
        $paymentService = new PaymentService();
        
        if ($status === 'SUCCESSFUL') {
            $paymentService->markPaymentAsPaid($payment, $payload);
            return ['success' => true, 'status' => 'PAID'];
        } elseif ($status === 'FAILED') {
            $payment->update([
                'status' => 'FAILED',
                'meta' => array_merge($payment->meta ?? [], ['callback' => $payload]),
            ]);
            return ['success' => true, 'status' => 'FAILED'];
        }
        
        return ['success' => true, 'status' => 'PENDING'];
    }
    
    /**
     * Traiter callback Airtel
     */
    protected static function handleAirtelCallback(array $payload): array
    {
        $transactionId = $payload['transaction']['id'] ?? null;
        $status = $payload['transaction']['status'] ?? null;
        
        if (!$transactionId) {
            return ['success' => false, 'error' => 'ID transaction manquant'];
        }
        
        $payment = Payment::where('provider_reference', $transactionId)->first();
        
        if (!$payment) {
            return ['success' => false, 'error' => 'Paiement non trouvé'];
        }
        
        $paymentService = new PaymentService();
        
        if ($status === 'TS') { // Transaction Successful
            $paymentService->markPaymentAsPaid($payment, $payload);
            return ['success' => true, 'status' => 'PAID'];
        } elseif (in_array($status, ['TF', 'TIP'])) { // Failed or In Progress
            $payment->update([
                'status' => $status === 'TF' ? 'FAILED' : 'PENDING',
                'meta' => array_merge($payment->meta ?? [], ['callback' => $payload]),
            ]);
            return ['success' => true, 'status' => $status === 'TF' ? 'FAILED' : 'PENDING'];
        }
        
        return ['success' => true, 'status' => 'UNKNOWN'];
    }
}

