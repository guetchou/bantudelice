<?php

namespace App\Services;

use App\Cart;
use App\Charge;
use App\Order;
use App\Payment;
use App\Product;
use App\Voucher;
use App\Services\ConfigService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service pour gérer le processus de checkout
 */
class CheckoutService
{
    public function __construct(
        protected DeliveryService $deliveryService,
        protected PaymentService $paymentService
    ) {}

    /**
     * Démarrer le processus de checkout
     * 
     * @param \App\User $user
     * @param string $paymentMethod
     * @param array $checkoutData (delivery_address, d_lat, d_lng, driver_tip, voucher_code, etc.)
     * @return array
     */
    public function startCheckout($user, string $paymentMethod, array $checkoutData = []): array
    {
        // 1. Récupérer le panier
        $cartItems = Cart::where('user_id', $user->id)->get();

        if ($cartItems->isEmpty()) {
            throw new \RuntimeException("Le panier est vide.");
        }

        // 2. Calculer les totaux
        $totals = $this->calculateTotals($cartItems, $checkoutData);
        $amount = (int) $totals['total'];

        return DB::transaction(function () use ($user, $paymentMethod, $amount, $cartItems, $checkoutData, $totals) {
            // 3. Créer le paiement
            $payment = Payment::create([
                'user_id'  => $user->id,
                'amount'   => $amount,
                'currency' => 'XAF',
                'status'   => 'PENDING',
                'provider' => $paymentMethod, // ex: "momo", "cash", "paypal"
            ]);

            // 4. Traiter selon le mode de paiement
            if ($paymentMethod === 'cash') {
                // Cash à la livraison : créer immédiatement la commande
                $orderNo = $this->createOrderFromCart($user, $cartItems, $checkoutData, $totals, $payment);
                $orders = Order::where('order_no', $orderNo)->get();
                
                // Créer les livraisons
                foreach ($orders as $order) {
                    try {
                        $this->deliveryService->createForOrder($order);
                    } catch (\Exception $e) {
                        Log::error('Erreur création livraison', [
                            'order_id' => $order->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }

                // Marquer le paiement comme payé (à encaisser à la livraison)
                $payment->update([
                    'status' => 'PAID',
                    'order_id' => $orders->first()->id,
                    'meta' => ['cash_on_delivery' => true]
                ]);

                // Vider le panier
                Cart::where('user_id', $user->id)->delete();

                return [
                    'payment' => $payment,
                    'order'   => $orders->first(),
                    'order_no' => $orderNo,
                    'requires_external_payment' => false,
                ];
            }

            // 5. Mode paiement en ligne (MoMo, PayPal, etc.)
            // Stocker les données de checkout dans les métadonnées pour pouvoir créer la commande après paiement
            $payment->update([
                'meta' => [
                    'checkout_data' => $checkoutData,
                    'totals' => $totals,
                ]
            ]);
            
            $paymentInit = $this->paymentService->initiateExternalPayment($payment, $cartItems, $checkoutData);

            // Mettre à jour le paiement avec les métadonnées du PSP
            $payment->update([
                'provider_reference' => $paymentInit['provider_reference'] ?? null,
                'meta' => array_merge($payment->meta ?? [], $paymentInit['meta'] ?? []),
            ]);

            return [
                'payment' => $payment,
                'order'   => null,
                'requires_external_payment' => true,
                'payment_payload' => $paymentInit,
            ];
        });
    }

    /**
     * Créer une commande depuis le panier
     * 
     * @param \App\User $user
     * @param \Illuminate\Database\Eloquent\Collection $cartItems
     * @param array $checkoutData
     * @param array $totals
     * @param Payment $payment
     * @return string Numéro de commande
     */
    protected function createOrderFromCart($user, $cartItems, array $checkoutData, array $totals, Payment $payment): string
    {
        // Générer le numéro de commande
        $orderNo = 'TD-' . date('Ymd') . '-' . rand(1000, 9999);

        // Appliquer le voucher si présent
        $discount = 0;
        if (isset($checkoutData['voucher_code']) && $checkoutData['voucher_code']) {
            $restaurantId = $cartItems->first()->restaurant_id;
            $voucher = Voucher::where('name', $checkoutData['voucher_code'])
                              ->where('restaurant_id', $restaurantId)
                              ->where('start_date', '<=', now())
                              ->where('end_date', '>=', now())
                              ->first();
            if ($voucher) {
                $discount = ($voucher->discount / 100) * $totals['sub_total'];
            }
        }

        // Créer les commandes
        foreach ($cartItems as $item) {
            $product = Product::find($item->product_id);
            $price = $product ? ($product->discount_price > 0 ? $product->discount_price : $product->price) : $item->price;
            
            DB::table('orders')->insert([
                'user_id' => $user->id,
                'restaurant_id' => $item->restaurant_id,
                'product_id' => $item->product_id,
                'qty' => $item->qty,
                'price' => $price,
                'driver_id' => null,
                'order_no' => $orderNo,
                'offer_discount' => $discount,
                'tax' => $totals['tax'],
                'delivery_charges' => $totals['delivery_fee'],
                'sub_total' => $totals['sub_total'],
                'total' => $totals['total'],
                'admin_commission' => 2,
                'restaurant_commission' => 4,
                'driver_tip' => $totals['driver_tip'] ?? 0,
                'delivery_address' => $checkoutData['delivery_address'] ?? '',
                'latitude' => $checkoutData['d_lat'] ?? null,
                'longitude' => $checkoutData['d_lng'] ?? null,
                'd_lat' => $checkoutData['d_lat'] ?? '-4.2767',
                'd_lng' => $checkoutData['d_lng'] ?? '15.2832',
                'payment_method' => $payment->provider,
                'payment_status' => $payment->status === 'PAID' ? 'paid' : 'pending',
                'status' => 'pending',
                'ordered_time' => now(),
                'delivered_time' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $orderNo;
    }

    /**
     * Calculer les totaux
     * 
     * @param \Illuminate\Database\Eloquent\Collection $cartItems
     * @param array $options
     * @return array
     */
    protected function calculateTotals($cartItems, array $options = []): array
    {
        $charges = Charge::first();
        if (!$charges) {
            $defaultDeliveryFee = ConfigService::getDefaultDeliveryFee();
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
        
        // Appliquer le voucher si présent
        $discount = 0;
        if (isset($options['voucher_code']) && $options['voucher_code']) {
            $restaurantId = $cartItems->first()->restaurant_id;
            $voucher = Voucher::where('name', $options['voucher_code'])
                              ->where('restaurant_id', $restaurantId)
                              ->where('start_date', '<=', now())
                              ->where('end_date', '>=', now())
                              ->first();
            if ($voucher) {
                $discount = ($voucher->discount / 100) * $subTotal;
                $total -= $discount;
            }
        }
        
        return [
            'sub_total' => $subTotal,
            'tax' => $tax,
            'delivery_fee' => $charges->delivery_fee,
            'service_fee' => $serviceFee,
            'driver_tip' => $driverTip,
            'discount' => $discount,
            'total' => $total
        ];
    }
}

