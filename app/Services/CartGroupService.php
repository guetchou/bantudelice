<?php

namespace App\Services;

use App\Product;
use App\Restaurant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CartGroupService
{
    public function cartItemsForUser(int $userId): Collection
    {
        return DB::table('carts')
            ->join('products', 'products.id', '=', 'carts.product_id')
            ->leftJoin('restaurants', 'restaurants.id', '=', 'carts.restaurant_id')
            ->select([
                'carts.id as cart_id',
                'carts.user_id',
                'carts.product_id',
                'carts.restaurant_id',
                'carts.qty',
                'carts.price as cart_price',
                'carts.sub_total as cart_sub_total',
                'carts.description',
                'products.name as product_name',
                'products.image as product_image',
                'products.description as product_description',
                'products.price as product_price',
                'products.discount_price',
                'restaurants.name as restaurant_name',
                'restaurants.logo as restaurant_logo',
                'restaurants.delivery_charges as restaurant_delivery_charges',
                'restaurants.tax as restaurant_tax',
                'restaurants.latitude as restaurant_latitude',
                'restaurants.longitude as restaurant_longitude',
            ])
            ->where('carts.user_id', $userId)
            ->orderBy('carts.restaurant_id')
            ->orderBy('carts.id')
            ->get();
    }

    public function groupByRestaurant(Collection $items, array $context = []): Collection
    {
        $deliveryFee = (float) ($context['delivery_fee'] ?? 0);
        $pickupFee = (float) ($context['pickup_fee'] ?? 0);
        $taxRate = (float) ($context['tax_rate'] ?? 0);
        $serviceFeeRate = (float) ($context['service_fee_rate'] ?? 0);
        $isPickup = (bool) ($context['is_pickup'] ?? false);

        return $items
            ->groupBy(fn ($item) => (int) ($item->restaurant_id ?? 0))
            ->map(function (Collection $groupItems, $restaurantId) use ($deliveryFee, $pickupFee, $taxRate, $serviceFeeRate, $isPickup) {
                $restaurant = null;
                if ($restaurantId) {
                    $restaurant = Restaurant::find($restaurantId);
                }

                $subTotal = $groupItems->sum(function ($item) {
                    $basePrice = (float) ($item->cart_price ?? $item->price ?? $item->product_price ?? 0);
                    $discountPrice = (float) ($item->discount_price ?? 0);
                    $price = ($discountPrice > 0 && $discountPrice < $basePrice) ? $discountPrice : $basePrice;

                    return $price * (int) ($item->qty ?? 1);
                });

                $groupDeliveryFee = $isPickup ? $pickupFee : $deliveryFee;
                $taxBase = $groupItems->sum(function ($item) {
                    $basePrice = (float) ($item->cart_price ?? $item->price ?? $item->product_price ?? 0);
                    $discountPrice = (float) ($item->discount_price ?? 0);
                    $price = ($discountPrice > 0 && $discountPrice < $basePrice) ? $discountPrice : $basePrice;

                    return $price * (int) ($item->qty ?? 1);
                });
                $tax = (($taxBase + $groupDeliveryFee) * $taxRate) / 100;
                $serviceFee = (($subTotal + $groupDeliveryFee + $tax) * $serviceFeeRate) / 100;

                return (object) [
                    'restaurant_id' => (int) $restaurantId,
                    'restaurant' => $restaurant,
                    'items' => $groupItems->values(),
                    'sub_total' => round($subTotal, 2),
                    'delivery_fee' => round($groupDeliveryFee, 2),
                    'tax' => round($tax, 2),
                    'service_fee' => round($serviceFee, 2),
                    'total_before_discount' => round($subTotal + $groupDeliveryFee + $tax + $serviceFee, 2),
                ];
            })
            ->values();
    }

    public function allocateAmountAcrossGroups(Collection $groups, float $amount): Collection
    {
        $total = max(0.0, (float) $groups->sum('sub_total'));
        if ($total <= 0.0) {
            return $groups->map(function ($group) {
                $group->allocated_amount = 0.0;
                return $group;
            });
        }

        $remaining = round($amount, 2);

        return $groups->values()->map(function ($group, $index) use ($groups, $amount, $total, &$remaining) {
            if ($index === $groups->count() - 1) {
                $group->allocated_amount = round(max(0, $remaining), 2);
                return $group;
            }

            $share = round(($group->sub_total / $total) * $amount, 2);
            $remaining -= $share;
            $group->allocated_amount = max(0, $share);

            return $group;
        });
    }

    public function buildOrderNo(string $baseOrderNo, int $restaurantId, int $index = 1): string
    {
        return sprintf('%s-R%d-%d', $baseOrderNo, $restaurantId, $index);
    }
}
