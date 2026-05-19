<?php

namespace App\Domain\Food\Services;

use App\Charge;
use App\Product;
use App\Restaurant;
use App\Services\ConfigService;
use App\Voucher;
use Illuminate\Support\Collection;

class OrderPricingService
{
    /**
     * @param  iterable<int, mixed>  $cartItems
     */
    public function chargeProfileForCart(iterable $cartItems): object
    {
        $cartItems = $cartItems instanceof Collection
            ? $cartItems->values()
            : collect($cartItems)->values();

        return $this->resolveChargeProfile(
            $this->resolveRestaurant($cartItems),
            $this->calculateSubTotal($cartItems)
        );
    }

    /**
     * @param  iterable<int, mixed>  $cartItems
     * @return array<string, float>
     */
    public function calculate(iterable $cartItems, array $options = []): array
    {
        $cartItems = $cartItems instanceof Collection
            ? $cartItems->values()
            : collect($cartItems)->values();

        $fulfillmentMode = $this->normalizeFulfillmentMode($options['fulfillment_mode'] ?? null);
        $subTotal = $this->calculateSubTotal($cartItems);
        $restaurant = $this->resolveRestaurant($cartItems);
        $charges = $this->resolveChargeProfile($restaurant, $subTotal);
        $baseDeliveryFee = $fulfillmentMode === 'pickup'
            ? (float) ($charges->pickup_fee ?? 0)
            : (float) ($charges->delivery_fee ?? ConfigService::getDefaultDeliveryFee());

        // T1.5 — Surcharge saison des pluies
        $weatherSurcharge = 0.0;
        $weatherSurchargeEnabled = false;
        if ($fulfillmentMode !== 'pickup') {
            $enabled = ConfigService::getConfigValue('weather_surcharge_enabled', false, 'boolean');
            if ($enabled) {
                $percent = (float) ConfigService::getConfigValue('weather_surcharge_percent', 0, 'float');
                if ($percent > 0) {
                    $weatherSurcharge        = round($baseDeliveryFee * $percent / 100);
                    $weatherSurchargeEnabled = true;
                }
            }
        }
        $deliveryFee = $baseDeliveryFee + $weatherSurcharge;

        $tax = ((float) ($charges->tax ?? 0) / 100) * $subTotal;
        $serviceFee = (($deliveryFee + $tax + $subTotal) / 100) * (float) ($charges->service_fee ?? 0);
        $driverTip = $fulfillmentMode === 'pickup' ? 0.0 : (float) ($options['driver_tip'] ?? 0);
        $discount = $this->resolveVoucherDiscount($cartItems, $options, $subTotal);
        $total = max(0.0, $subTotal + $deliveryFee + $tax + $serviceFee + $driverTip - $discount);

        return [
            'sub_total'                => $subTotal,
            'tax'                      => $tax,
            'delivery_fee'             => $deliveryFee,
            'weather_surcharge'        => $weatherSurcharge,
            'weather_surcharge_active' => $weatherSurchargeEnabled,
            'service_fee'              => $serviceFee,
            'driver_tip'               => $driverTip,
            'discount'                 => $discount,
            'total'                    => $total,
        ];
    }

    private function calculateSubTotal(Collection $cartItems): float
    {
        return (float) $cartItems->sum(function ($item) {
            $product = Product::find($item->product_id);
            if (! $product) {
                return (float) ($item->price ?? 0) * (int) ($item->qty ?? 0);
            }

            $price = $product->discount_price > 0 ? $product->discount_price : $product->price;

            return (float) $price * (int) ($item->qty ?? 0);
        });
    }

    private function resolveRestaurant(Collection $cartItems): ?Restaurant
    {
        $restaurantId = optional($cartItems->first())->restaurant_id;

        return $restaurantId ? Restaurant::find($restaurantId) : null;
    }

    private function resolveChargeProfile(?Restaurant $restaurant, float $subTotal): object
    {
        $charges = Charge::first();
        if (! $charges) {
            $charges = (object) [
                'delivery_fee' => ConfigService::getDefaultDeliveryFee(),
                'pickup_fee' => 0,
                'tax' => 5,
                'service_fee' => 2,
            ];
        }

        $base = $charges instanceof \Illuminate\Database\Eloquent\Model
            ? $charges->toArray()
            : (array) $charges;

        $normalized = (object) array_merge([
            'delivery_fee' => ConfigService::getDefaultDeliveryFee(),
            'pickup_fee' => 0,
            'tax' => 5,
            'service_fee' => 2,
        ], $base);

        // Preserve the production low-value live payment basket profile.
        if ((int) ($restaurant->id ?? 0) === 1 && $subTotal <= 100) {
            $normalized->delivery_fee = 0;
            $normalized->pickup_fee = 0;
            $normalized->tax = 0;
            $normalized->service_fee = 0;
        }

        return $normalized;
    }

    private function resolveVoucherDiscount(Collection $cartItems, array $options, float $subTotal): float
    {
        $voucherCode = trim((string) ($options['voucher_code'] ?? ''));
        $restaurantId = optional($cartItems->first())->restaurant_id;

        if ($voucherCode === '' || ! $restaurantId) {
            return 0.0;
        }

        $voucher = Voucher::where('name', $voucherCode)
            ->where('restaurant_id', $restaurantId)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->first();

        if (! $voucher) {
            return 0.0;
        }

        return ((float) $voucher->discount / 100) * $subTotal;
    }

    private function normalizeFulfillmentMode(mixed $fulfillmentMode): string
    {
        return strtolower((string) $fulfillmentMode) === 'pickup' ? 'pickup' : 'delivery';
    }
}
