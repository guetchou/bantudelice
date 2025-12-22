<?php

namespace App\Services;

use App\Cart;
use App\Order;
use App\Payment;
use App\Charge;
use App\Voucher;
use App\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class PaymentService
{
    /**
     * Initier un paiement externe (MoMo, PayPal, etc.)
     * 
     * @param \App\Payment $payment
     * @param \Illuminate\Database\Eloquent\Collection $cartItems
     * @param array $checkoutData
     * @return array
     */
    public function initiateExternalPayment($payment, $cartItems, array $checkoutData = []): array
    {
        $provider = $payment->provider;
        
        switch ($provider) {
            case 'momo':
                return $this->initiateMoMoPayment($payment, $checkoutData);
            case 'paypal':
                return $this->initiatePayPalPayment($payment, $checkoutData);
            default:
                Log::warning('Provider de paiement non reconnu', ['provider' => $provider]);
                return [
                    'provider_reference' => 'DEMO-' . time() . '-' . rand(1000, 9999),
                    'meta' => [
                        'demo' => true,
                        'provider' => $provider,
                        'note' => 'Provider non implémenté',
                    ],
                    'redirect_url' => null,
                ];
        }
    }

    /**
     * Initier un paiement Mobile Money
     * 
     * @param \App\Payment $payment
     * @param array $checkoutData
     * @return array
     */
    protected function initiateMoMoPayment($payment, array $checkoutData = []): array
    {
        $momoApiKey = env('MOMO_API_KEY');
        $momoApiSecret = env('MOMO_API_SECRET');
        $momoApiUrl = env('MOMO_API_URL', 'https://api.momo.cg/v1');
        $momoEnvironment = env('MOMO_ENVIRONMENT', 'sandbox');
        
        // Si pas de configuration, mode démo
        if (!$momoApiKey || !$momoApiSecret) {
            Log::warning('Configuration MoMo manquante, mode démo activé');
            return [
                'provider_reference' => 'MOMO-DEMO-' . $payment->id . '-' . time(),
                'meta' => [
                    'demo' => true,
                    'provider' => 'momo',
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'phone' => $payment->user->phone ?? null,
                    'instructions' => 'En mode démo. Configurez MOMO_API_KEY et MOMO_API_SECRET dans .env pour activer les paiements réels.',
                ],
                'redirect_url' => null,
            ];
        }

        try {
            $user = $payment->user;
            $reference = 'MOMO-' . $payment->id . '-' . time();
            
            $callbackUrl = route('api.payments.callback', ['provider' => 'momo']);
            $returnUrl = url('/checkout/success?payment=' . $payment->id);
            
            $requestData = [
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'reference' => $reference,
                'payer' => [
                    'phone' => $user->phone,
                    'name' => $user->name,
                    'email' => $user->email ?? null,
                ],
                'callback_url' => $callbackUrl,
                'return_url' => $returnUrl,
                'description' => 'Commande ' . \App\Services\ConfigService::getCompanyName() . ' #' . $payment->id,
            ];

            $response = $this->callMoMoAPI($momoApiUrl . '/payments', $requestData, $momoApiKey, $momoApiSecret);

            if ($response && isset($response['status']) && $response['status'] === 'success') {
                return [
                    'provider_reference' => $response['reference'] ?? $reference,
                    'meta' => [
                        'momo_reference' => $response['reference'] ?? $reference,
                        'payment_url' => $response['payment_url'] ?? null,
                        'qr_code' => $response['qr_code'] ?? null,
                        'ussd_code' => $response['ussd_code'] ?? null,
                        'amount' => $payment->amount,
                        'currency' => $payment->currency,
                    ],
                    'redirect_url' => $response['payment_url'] ?? null,
                ];
            } else {
                throw new \RuntimeException('Erreur lors de l\'initiation du paiement MoMo: ' . ($response['message'] ?? 'Erreur inconnue'));
            }
        } catch (\Exception $e) {
            Log::error('Erreur initiation paiement MoMo', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            
            throw new \RuntimeException('Impossible d\'initier le paiement Mobile Money. Veuillez réessayer ou choisir un autre mode de paiement.');
        }
    }

    /**
     * Appeler l'API MoMo
     * 
     * @param string $url
     * @param array $data
     * @param string $apiKey
     * @param string $apiSecret
     * @return array|null
     */
    protected function callMoMoAPI(string $url, array $data, string $apiKey, string $apiSecret): ?array
    {
        $timestamp = time();
        $nonce = bin2hex(random_bytes(16));
        
        // Créer la signature (adaptez selon la méthode de votre API MoMo)
        $signatureString = $apiKey . $timestamp . $nonce . json_encode($data);
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
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            Log::error('Erreur cURL MoMo', ['error' => $error]);
            return null;
        }

        if ($httpCode !== 200) {
            Log::error('Erreur HTTP MoMo', ['http_code' => $httpCode, 'response' => $response]);
            return null;
        }

        $decoded = json_decode($response, true);
        return $decoded;
    }

    /**
     * Initier un paiement PayPal
     * 
     * @param \App\Payment $payment
     * @param array $checkoutData
     * @return array
     */
    protected function initiatePayPalPayment($payment, array $checkoutData = []): array
    {
        $paypalClientId = env('PAYPAL_CLIENT_ID');
        $paypalClientSecret = env('PAYPAL_CLIENT_SECRET');
        $paypalMode = env('PAYPAL_MODE', 'sandbox');
        
        if (!$paypalClientId || !$paypalClientSecret) {
            Log::warning('Configuration PayPal manquante, mode démo activé');
            return [
                'provider_reference' => 'PAYPAL-DEMO-' . time() . '-' . rand(1000, 9999),
                'meta' => [
                    'demo' => true,
                    'provider' => 'paypal',
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                ],
                'redirect_url' => null,
            ];
        }

        // TODO: Implémenter l'intégration PayPal réelle
        return [
            'provider_reference' => 'PAYPAL-' . time() . '-' . rand(1000, 9999),
            'meta' => [
                'provider' => 'paypal',
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'note' => 'Intégration PayPal à compléter',
            ],
            'redirect_url' => null,
        ];
    }

    /**
     * Gérer le callback d'un PSP
     * 
     * @param string $provider
     * @param array $payload
     * @return void
     */
    public function handleCallback(string $provider, array $payload): void
    {
        Log::info('Payment callback reçu', ['provider' => $provider, 'payload' => $payload]);
        
        // 1. Retrouver le Payment à partir de la référence
        $providerRef = $payload['reference'] ?? $payload['transaction_id'] ?? $payload['id'] ?? $payload['external_id'] ?? null;
        
        if (!$providerRef) {
            Log::error('Payment callback sans référence', ['provider' => $provider, 'payload' => $payload]);
            throw new \RuntimeException('Référence de paiement manquante dans le callback');
        }
        
        $payment = Payment::where('provider_reference', $providerRef)
            ->where('provider', $provider)
            ->first();
        
        if (!$payment) {
            Log::error('Payment non trouvé pour la référence', [
                'provider' => $provider,
                'reference' => $providerRef,
                'payload' => $payload
            ]);
            throw new \RuntimeException('Paiement non trouvé pour la référence: ' . $providerRef);
        }
        
        // 2. Vérifier la signature selon le provider
        if (!$this->verifyCallbackSignature($provider, $payload)) {
            Log::error('Signature callback invalide', [
                'provider' => $provider,
                'payment_id' => $payment->id
            ]);
            throw new \RuntimeException('Signature de callback invalide');
        }
        
        // 3. Déterminer le statut
        $status = $this->extractStatus($provider, $payload);
        
        // 4. Mettre à jour le statut du paiement
        if (in_array(strtoupper($status), ['SUCCESS', 'PAID', 'COMPLETED', 'CAPTURED', 'APPROVED'])) {
            $this->markPaymentAsPaid($payment, $payload);
            Log::info('Paiement marqué comme payé', ['payment_id' => $payment->id]);
        } elseif (in_array(strtoupper($status), ['FAILED', 'CANCELLED', 'REJECTED', 'DECLINED'])) {
            $payment->update([
                'status' => 'FAILED',
                'meta' => array_merge($payment->meta ?? [], [
                    'callback' => $payload,
                    'failed_at' => now()->toIso8601String(),
                    'failure_reason' => $payload['message'] ?? $payload['reason'] ?? null,
                ])
            ]);
            Log::info('Paiement marqué comme échoué', ['payment_id' => $payment->id, 'status' => $status]);
        } else {
            // Statut inconnu ou en attente
            $payment->update([
                'meta' => array_merge($payment->meta ?? [], [
                    'callback' => $payload,
                    'last_callback_at' => now()->toIso8601String(),
                ])
            ]);
            Log::warning('Statut de paiement non géré', ['payment_id' => $payment->id, 'status' => $status]);
        }
    }

    /**
     * Vérifier la signature du callback
     * 
     * @param string $provider
     * @param array $payload
     * @return bool
     */
    protected function verifyCallbackSignature(string $provider, array $payload): bool
    {
        switch ($provider) {
            case 'momo':
                return $this->verifyMoMoSignature($payload);
            case 'paypal':
                return $this->verifyPayPalSignature($payload);
            default:
                // Pour les providers non implémentés, accepter par défaut (à sécuriser en production)
                Log::warning('Vérification de signature non implémentée pour le provider', ['provider' => $provider]);
                return true;
        }
    }

    /**
     * Vérifier la signature MoMo
     * 
     * @param array $payload
     * @return bool
     */
    protected function verifyMoMoSignature(array $payload): bool
    {
        $apiSecret = env('MOMO_API_SECRET');
        if (!$apiSecret) {
            Log::warning('MOMO_API_SECRET non configuré, signature non vérifiée');
            return true; // En mode démo, accepter
        }

        $receivedSignature = $payload['signature'] ?? null;
        if (!$receivedSignature) {
            return false;
        }

        // Reconstruire la signature (adaptez selon la méthode de votre API MoMo)
        $signatureString = ($payload['reference'] ?? '') . 
                          ($payload['amount'] ?? '') . 
                          ($payload['status'] ?? '');
        $expectedSignature = hash_hmac('sha256', $signatureString, $apiSecret);

        return hash_equals($expectedSignature, $receivedSignature);
    }

    /**
     * Vérifier la signature PayPal
     * 
     * @param array $payload
     * @return bool
     */
    protected function verifyPayPalSignature(array $payload): bool
    {
        // TODO: Implémenter la vérification PayPal selon leur documentation
        return true;
    }

    /**
     * Extraire le statut depuis le payload selon le provider
     * 
     * @param string $provider
     * @param array $payload
     * @return string
     */
    protected function extractStatus(string $provider, array $payload): string
    {
        switch ($provider) {
            case 'momo':
                return $payload['status'] ?? $payload['transaction_status'] ?? 'FAILED';
            case 'paypal':
                return $payload['status'] ?? $payload['state'] ?? 'FAILED';
            default:
                return $payload['status'] ?? $payload['state'] ?? 'FAILED';
        }
    }

    /**
     * Marquer un paiement comme payé et créer la commande si nécessaire
     * 
     * @param \App\Payment $payment
     * @param array $callbackData
     * @return void
     */
    public function markPaymentAsPaid($payment, array $callbackData = []): void
    {
        DB::transaction(function () use ($payment, $callbackData) {
            $payment->update([
                'status' => 'PAID',
                'meta' => array_merge($payment->meta ?? [], [
                    'callback' => $callbackData,
                    'paid_at' => now()->toIso8601String(),
                ])
            ]);

            // 1. Gérer le paiement pour une réservation de transport
            if ($payment->transport_booking_id) {
                $booking = $payment->transportBooking;
                if ($booking) {
                    $booking->update(['payment_status' => 'paid']);
                    // On peut aussi déclencher une notification spécifique ici
                }
                return;
            }

            // 2. Gérer le paiement pour un colis
            if ($payment->shipment_id) {
                $shipment = $payment->shipment;
                if ($shipment) {
                    $shipment->update(['payment_status' => 'paid']);
                }
                return;
            }

            // 3. Gérer le paiement pour une commande (Food Delivery)
            if (!$payment->order_id) {
                $checkoutService = new \App\Services\CheckoutService(
                    new \App\Services\DeliveryService(),
                    $this
                );
                
                $user = $payment->user;
                $cartItems = \App\Cart::where('user_id', $user->id)->get();
                
                if (!$cartItems->isEmpty()) {
                    // Récupérer les données de checkout depuis les métadonnées
                    $checkoutData = $payment->meta['checkout_data'] ?? [];
                    $totals = $checkoutService->calculateTotals($cartItems, $checkoutData);
                    
                    $orderNo = $checkoutService->createOrderFromCart($user, $cartItems, $checkoutData, $totals, $payment);
                    $orders = \App\Order::where('order_no', $orderNo)->get();
                    
                    // Créer les livraisons
                    $deliveryService = new \App\Services\DeliveryService();
                    foreach ($orders as $order) {
                        try {
                            $deliveryService->createForOrder($order);
                        } catch (\Exception $e) {
                            Log::error('Erreur création livraison depuis callback', [
                                'order_id' => $order->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                    
                    // Mettre à jour le paiement avec l'ID de commande
                    $payment->update(['order_id' => $orders->first()->id]);
                    
                    // Vider le panier
                    \App\Cart::where('user_id', $user->id)->delete();
                }
            } else {
                // Mettre à jour le statut de la commande existante
                $payment->order->update(['payment_status' => 'paid']);
            }
        });
    }

    /**
     * Créer une commande depuis le panier (méthode statique conservée pour compatibilité)
     * 
     * @param array $orderData
     * @return string Numéro de commande
     */
    public static function createOrderFromCart($orderData)
    {
        $userId = auth()->user()->id;
        $cartItems = Cart::where('user_id', $userId)->get();
        
        if ($cartItems->isEmpty()) {
            throw new Exception('Le panier est vide');
        }
        
        // Récupérer les frais
        $charges = Charge::first();
        if (!$charges) {
            $defaultDeliveryFee = \App\Services\ConfigService::getDefaultDeliveryFee();
            $charges = Charge::create([
                'delivery_fee' => $defaultDeliveryFee,
                'tax' => 5,
                'service_fee' => 2
            ]);
        }
        
        // Calcul des totaux
        $subTotal = 0;
        foreach ($cartItems as $item) {
            $product = Product::find($item->product_id);
            if ($product) {
                $price = $product->discount_price > 0 ? $product->discount_price : $product->price;
                $subTotal += $price * $item->qty;
            }
        }
        
        $tax = ($charges->tax / 100) * $subTotal;
        $serviceFee = (($charges->delivery_fee + $tax + $subTotal) / 100) * $charges->service_fee;
        $driverTip = $orderData['driver_tip'] ?? 0;
        $total = $subTotal + $charges->delivery_fee + $tax + $serviceFee + $driverTip;
        
        // Appliquer le voucher si présent
        $discount = 0;
        if (isset($orderData['voucher_code']) && $orderData['voucher_code']) {
            $restaurantId = $cartItems->first()->restaurant_id;
            $voucher = Voucher::where('name', $orderData['voucher_code'])
                              ->where('restaurant_id', $restaurantId)
                              ->first();
            if ($voucher) {
                $discount = ($voucher->discount / 100) * $subTotal;
                $total -= $discount;
            }
        }
        
        // Générer le numéro de commande
        $orderNo = 'TD-' . date('Ymd') . '-' . rand(1000, 9999);
        
        // Créer les commandes
        DB::beginTransaction();
        try {
            foreach ($cartItems as $item) {
                $product = Product::find($item->product_id);
                $price = $product ? ($product->discount_price > 0 ? $product->discount_price : $product->price) : $item->price;
                
                DB::table('orders')->insert([
                    'user_id' => $userId,
                    'restaurant_id' => $item->restaurant_id,
                    'product_id' => $item->product_id,
                    'qty' => $item->qty,
                    'price' => $price,
                    'driver_id' => null,
                    'order_no' => $orderNo,
                    'offer_discount' => $discount,
                    'tax' => $tax,
                    'delivery_charges' => $charges->delivery_fee,
                    'sub_total' => $subTotal,
                    'total' => $total,
                    'admin_commission' => 2,
                    'restaurant_commission' => 4,
                    'driver_tip' => $driverTip,
                    'delivery_address' => $orderData['delivery_address'],
                    'latitude' => $orderData['d_lat'] ?? null,
                    'longitude' => $orderData['d_lng'] ?? null,
                    'd_lat' => $orderData['d_lat'] ?? '-4.2767',
                    'd_lng' => $orderData['d_lng'] ?? '15.2832',
                    'payment_method' => $orderData['payment_method'] ?? 'cash',
                    'payment_status' => 'pending',
                    'status' => 'pending',
                    'ordered_time' => now(),
                    'delivered_time' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            
            // Vider le panier
            Cart::where('user_id', $userId)->delete();
            
            DB::commit();
            
            Log::info('Order created successfully', ['order_no' => $orderNo]);
            
            return $orderNo;
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Order creation failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    /**
     * Calculer les totaux d'une commande
     * 
     * @param array $cartItems
     * @param array $options
     * @return array
     */
    public static function calculateTotals($cartItems, $options = [])
    {
        $charges = Charge::first();
        if (!$charges) {
            $defaultDeliveryFee = \App\Services\ConfigService::getDefaultDeliveryFee();
            $charges = (object)[
                'delivery_fee' => $defaultDeliveryFee,
                'tax' => 5,
                'service_fee' => 2
            ];
        }
        
        $subTotal = 0;
        foreach ($cartItems as $item) {
            $product = Product::find($item->product_id);
            if ($product) {
                $price = $product->discount_price > 0 ? $product->discount_price : $product->price;
                $subTotal += $price * $item->qty;
            }
        }
        
        $tax = ($charges->tax / 100) * $subTotal;
        $serviceFee = (($charges->delivery_fee + $tax + $subTotal) / 100) * $charges->service_fee;
        $driverTip = $options['driver_tip'] ?? 0;
        $total = $subTotal + $charges->delivery_fee + $tax + $serviceFee + $driverTip;
        
        return [
            'sub_total' => $subTotal,
            'tax' => $tax,
            'delivery_fee' => $charges->delivery_fee,
            'service_fee' => $serviceFee,
            'driver_tip' => $driverTip,
            'total' => $total
        ];
    }
}

