<?php

namespace App\Domain\Checkout\Contracts;

/**
 * Couture d'orchestration du checkout food.
 *
 * Utilisée par FoodOrderPaymentConfirmed (et tout futur appelant)
 * pour calculer les totaux et créer une commande depuis le panier,
 * sans dépendre de l'implémentation concrète de CheckoutService.
 *
 * Seules les deux méthodes utilisées hors du chemin HTTP sont exposées ici.
 * startCheckout() reste sur CheckoutService — c'est un point d'entrée HTTP,
 * pas une couture de domaine.
 */
interface CheckoutOrchestratorInterface
{
    /**
     * Calculer les totaux à partir des articles du panier.
     *
     * @param  \Illuminate\Support\Collection $cartItems
     * @param  array                          $options   fulfillment_mode, voucher_code, driver_tip, etc.
     * @return array{total: float, sub_total: float, tax: float, delivery_fee: float, discount: float, driver_tip: float}
     */
    public function calculateTotals($cartItems, array $options = []): array;

    /**
     * Créer une commande depuis le contenu du panier.
     *
     * @param  mixed       $user
     * @param  mixed       $cartItems
     * @param  array       $checkoutData
     * @param  array       $totals            Résultat de calculateTotals()
     * @param  string      $paymentMethod      cash, momo, paypal...
     * @param  array|null  $checkoutSnapshot   Capture checkoutData/totals/payment_method à rejouer
     *                                         plus tard, à l'acceptation restaurant.
     * @return string                          Numéro de commande (order_no)
     */
    public function createOrderFromCart($user, $cartItems, array $checkoutData, array $totals, string $paymentMethod, ?array $checkoutSnapshot = null): string;
}
