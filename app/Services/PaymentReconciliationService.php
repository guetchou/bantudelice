<?php

namespace App\Services;

use App\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

/**
 * Service de réconciliation automatique des paiements
 * 
 * Vérifie que les paiements en DB correspondent aux paiements réels chez le PSP
 */
class PaymentReconciliationService
{
    /**
     * Réconcilier un paiement avec le provider
     * 
     * @param Payment $payment
     * @return array ['reconciled' => bool, 'status' => string, 'message' => string]
     */
    public function reconcile(Payment $payment): array
    {
        if ($payment->status === 'PAID') {
            // Si déjà payé, vérifier que c'est toujours valide
            return $this->verifyPaidPayment($payment);
        }

        if ($payment->status === 'PENDING') {
            // Si en attente, vérifier auprès du provider
            return $this->checkPendingPayment($payment);
        }

        return [
            'reconciled' => false,
            'status' => $payment->status,
            'message' => 'Statut non réconciliable: ' . $payment->status
        ];
    }

    /**
     * Vérifier un paiement déjà marqué comme payé
     * 
     * @param Payment $payment
     * @return array
     */
    protected function verifyPaidPayment(Payment $payment): array
    {
        try {
            $providerStatus = $this->getProviderStatus($payment);
            
            if ($providerStatus['status'] === 'PAID' || $providerStatus['status'] === 'SUCCESS') {
                // Cohérent
                $this->logReconciliation($payment, 'VERIFIED', 'Paiement confirmé auprès du provider');
                return [
                    'reconciled' => true,
                    'status' => 'VERIFIED',
                    'message' => 'Paiement confirmé'
                ];
            } elseif ($providerStatus['status'] === 'FAILED' || $providerStatus['status'] === 'CANCELLED') {
                // Incohérence : en DB = PAID, chez provider = FAILED
                Log::warning('Incohérence détectée : paiement PAID en DB mais FAILED chez provider', [
                    'payment_id' => $payment->id,
                    'provider_status' => $providerStatus['status']
                ]);
                
                $this->logReconciliation($payment, 'INCONSISTENT', 'Paiement PAID en DB mais FAILED chez provider');
                
                // Optionnel : marquer comme FAILED (à décider selon la politique)
                // $payment->update(['status' => 'FAILED']);
                
                return [
                    'reconciled' => false,
                    'status' => 'INCONSISTENT',
                    'message' => 'Incohérence détectée : PAID en DB mais FAILED chez provider'
                ];
            } else {
                // Statut inconnu
                return [
                    'reconciled' => false,
                    'status' => 'UNKNOWN',
                    'message' => 'Statut provider inconnu: ' . $providerStatus['status']
                ];
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de la vérification du paiement', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'reconciled' => false,
                'status' => 'ERROR',
                'message' => 'Erreur lors de la vérification: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Vérifier un paiement en attente
     * 
     * @param Payment $payment
     * @return array
     */
    protected function checkPendingPayment(Payment $payment): array
    {
        try {
            $providerStatus = $this->getProviderStatus($payment);
            
            if ($providerStatus['status'] === 'PAID' || $providerStatus['status'] === 'SUCCESS') {
                // Le paiement a été payé mais pas encore mis à jour en DB
                Log::info('Paiement PENDING en DB mais PAID chez provider, mise à jour', [
                    'payment_id' => $payment->id
                ]);
                
                // Mettre à jour le statut
                $paymentService = new PaymentService();
                $paymentService->markPaymentAsPaid($payment, $providerStatus['data'] ?? []);
                
                $this->logReconciliation($payment, 'RECONCILED', 'Paiement PENDING mis à jour vers PAID');
                
                return [
                    'reconciled' => true,
                    'status' => 'RECONCILED',
                    'message' => 'Paiement réconcilié et mis à jour'
                ];
            } elseif ($providerStatus['status'] === 'FAILED' || $providerStatus['status'] === 'CANCELLED') {
                // Le paiement a échoué
                $payment->update([
                    'status' => 'FAILED',
                    'meta' => array_merge($payment->meta ?? [], [
                        'reconciled_at' => now()->toIso8601String(),
                        'reconciliation_status' => 'FAILED',
                        'failure_reason' => $providerStatus['message'] ?? 'Paiement échoué'
                    ])
                ]);
                
                $this->logReconciliation($payment, 'FAILED', 'Paiement échoué chez provider');
                
                return [
                    'reconciled' => true,
                    'status' => 'FAILED',
                    'message' => 'Paiement échoué'
                ];
            } else {
                // Toujours en attente
                return [
                    'reconciled' => false,
                    'status' => 'PENDING',
                    'message' => 'Paiement toujours en attente'
                ];
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de la vérification du paiement en attente', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'reconciled' => false,
                'status' => 'ERROR',
                'message' => 'Erreur lors de la vérification: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Récupérer le statut depuis le provider
     * 
     * @param Payment $payment
     * @return array ['status' => string, 'data' => array, 'message' => string]
     */
    protected function getProviderStatus(Payment $payment): array
    {
        switch ($payment->provider) {
            case 'momo':
                return $this->getMoMoStatus($payment);
            case 'paypal':
                return $this->getPayPalStatus($payment);
            case 'cash':
                // Paiement cash : pas de vérification possible
                return [
                    'status' => 'UNKNOWN',
                    'data' => [],
                    'message' => 'Paiement cash, pas de vérification provider'
                ];
            default:
                return [
                    'status' => 'UNKNOWN',
                    'data' => [],
                    'message' => 'Provider non supporté pour réconciliation: ' . $payment->provider
                ];
        }
    }

    /**
     * Récupérer le statut MoMo
     * 
     * @param Payment $payment
     * @return array
     */
    protected function getMoMoStatus(Payment $payment): array
    {
        $apiKey = env('MOMO_API_KEY');
        $apiSecret = env('MOMO_API_SECRET');
        $apiUrl = env('MOMO_API_URL', 'https://api.momo.cg/v1');
        
        if (!$apiKey || !$apiSecret) {
            return [
                'status' => 'UNKNOWN',
                'data' => [],
                'message' => 'Configuration MoMo manquante'
            ];
        }

        try {
            $reference = $payment->provider_reference;
            if (!$reference) {
                return [
                    'status' => 'UNKNOWN',
                    'data' => [],
                    'message' => 'Référence provider manquante'
                ];
            }

            // Appel API MoMo pour vérifier le statut
            $url = $apiUrl . '/payments/' . $reference . '/status';
            $response = $this->callMoMoAPI($url, 'GET', null, $apiKey, $apiSecret);

            if ($response && isset($response['status'])) {
                return [
                    'status' => $response['status'],
                    'data' => $response,
                    'message' => $response['message'] ?? 'Statut récupéré'
                ];
            }

            return [
                'status' => 'UNKNOWN',
                'data' => $response ?? [],
                'message' => 'Réponse provider invalide'
            ];
        } catch (\Exception $e) {
            Log::error('Erreur récupération statut MoMo', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'status' => 'ERROR',
                'data' => [],
                'message' => 'Erreur API: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Récupérer le statut PayPal
     * 
     * @param Payment $payment
     * @return array
     */
    protected function getPayPalStatus(Payment $payment): array
    {
        // TODO: Implémenter selon l'API PayPal
        return [
            'status' => 'UNKNOWN',
            'data' => [],
            'message' => 'Réconciliation PayPal non implémentée'
        ];
    }

    /**
     * Appeler l'API MoMo (GET)
     * 
     * @param string $url
     * @param string $method
     * @param array|null $data
     * @param string $apiKey
     * @param string $apiSecret
     * @return array|null
     */
    protected function callMoMoAPI(string $url, string $method = 'GET', ?array $data = null, string $apiKey = '', string $apiSecret = ''): ?array
    {
        $timestamp = time();
        $nonce = bin2hex(random_bytes(16));
        
        $signatureString = $apiKey . $timestamp . $nonce . ($data ? json_encode($data) : '');
        $signature = hash_hmac('sha256', $signatureString, $apiSecret);
        
        $headers = [
            'Content-Type: application/json',
            'X-API-Key: ' . $apiKey,
            'X-Timestamp: ' . $timestamp,
            'X-Nonce: ' . $nonce,
            'X-Signature: ' . $signature,
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            Log::error('Erreur cURL MoMo (GET)', ['error' => $error]);
            return null;
        }

        if ($httpCode !== 200) {
            Log::error('Erreur HTTP MoMo (GET)', ['http_code' => $httpCode, 'response' => $response]);
            return null;
        }

        return json_decode($response, true);
    }

    /**
     * Logger une réconciliation
     * 
     * @param Payment $payment
     * @param string $status
     * @param string $message
     * @return void
     */
    protected function logReconciliation(Payment $payment, string $status, string $message): void
    {
        try {
            DB::table('payment_reconciliation_logs')->insert([
                'payment_id' => $payment->id,
                'status' => $status,
                'message' => $message,
                'provider' => $payment->provider,
                'provider_reference' => $payment->provider_reference,
                'amount' => $payment->amount,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Si la table n'existe pas encore, juste logger
            Log::info('Réconciliation', [
                'payment_id' => $payment->id,
                'status' => $status,
                'message' => $message
            ]);
        }
    }

    /**
     * Réconcilier tous les paiements en attente (pour scheduler)
     * 
     * @param int $limit
     * @return array ['processed' => int, 'reconciled' => int, 'failed' => int]
     */
    public function reconcilePendingPayments(int $limit = 50): array
    {
        $pendingPayments = Payment::where('status', 'PENDING')
            ->where('created_at', '>=', now()->subDays(7)) // Seulement les 7 derniers jours
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        $processed = 0;
        $reconciled = 0;
        $failed = 0;

        foreach ($pendingPayments as $payment) {
            $processed++;
            $result = $this->reconcile($payment);
            
            if ($result['reconciled']) {
                $reconciled++;
            } elseif ($result['status'] === 'FAILED') {
                $failed++;
            }
        }

        return [
            'processed' => $processed,
            'reconciled' => $reconciled,
            'failed' => $failed
        ];
    }
}

