<?php

namespace App\Domain\Food\Services;

use App\Domain\Food\Enums\OrderPaymentStatus;
use App\Product;
use App\Restaurant;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PlaceOrderService
{
    private const DEFAULT_ADMIN_COMMISSION = 2.0;
    private const DEFAULT_RESTAURANT_COMMISSION = 4.0;
    private const DEFAULT_FALLBACK_LAT = '-4.2767';
    private const DEFAULT_FALLBACK_LNG = '15.2832';

    private ?array $orderColumnMap = null;

    /**
     * @param  iterable<int, mixed>  $cartItems
     */
    public function placeFromCart(User $user, iterable $cartItems, array $context = []): string
    {
        $cartItems = $cartItems instanceof Collection
            ? $cartItems->values()
            : collect($cartItems)->values();

        if ($cartItems->isEmpty()) {
            throw new \RuntimeException('Le panier est vide.');
        }

        $restaurant = $context['restaurant'] ?? $this->resolveRestaurant($cartItems);
        $fulfillmentMode = $this->normalizeFulfillmentMode($context['fulfillment_mode'] ?? null);
        $scheduledAt = $this->resolveScheduledAt($context['scheduled_at'] ?? ($context['scheduled_date'] ?? null));
        $orderNo = $this->resolveOrderNo($context['order_no'] ?? null);
        $pickupCode = $this->resolvePickupCode($fulfillmentMode, $context);
        $deliveryAddress = $this->resolveDeliveryAddress($restaurant, $context, $fulfillmentMode);
        $orderedTime = $context['ordered_time'] ?? now();
        $deliveredTime = array_key_exists('delivered_time', $context) ? $context['delivered_time'] : null;
        $createdAt = $context['created_at'] ?? now();
        $updatedAt = $context['updated_at'] ?? $createdAt;
        $schema = $this->orderColumnMap();
        $totalItems = max(1, (int) ($context['total_items'] ?? $cartItems->sum('qty')));

        DB::transaction(function () use (
            $user,
            $cartItems,
            $context,
            $restaurant,
            $fulfillmentMode,
            $scheduledAt,
            $orderNo,
            $pickupCode,
            $deliveryAddress,
            $orderedTime,
            $deliveredTime,
            $createdAt,
            $updatedAt,
            $schema,
            $totalItems
        ): void {
            foreach ($cartItems as $item) {
                $payload = [
                    'user_id' => $user->id,
                    'restaurant_id' => $item->restaurant_id,
                    'product_id' => $item->product_id,
                    'qty' => (int) $item->qty,
                    'price' => $this->resolveItemPrice($item),
                    'driver_id' => $context['driver_id'] ?? null,
                    'total_items' => $totalItems,
                    'order_no' => $orderNo,
                    'offer_discount' => (float) ($context['offer_discount'] ?? 0),
                    'tax' => (float) ($context['tax'] ?? 0),
                    'delivery_charges' => (float) ($context['delivery_charges'] ?? 0),
                    'sub_total' => (float) ($context['sub_total'] ?? 0),
                    'total' => (float) ($context['total'] ?? 0),
                    'admin_commission' => (float) ($context['admin_commission'] ?? self::DEFAULT_ADMIN_COMMISSION),
                    'restaurant_commission' => (float) ($context['restaurant_commission'] ?? self::DEFAULT_RESTAURANT_COMMISSION),
                    'driver_tip' => (float) ($context['driver_tip'] ?? 0),
                    'status' => (string) ($context['status'] ?? ($scheduledAt ? 'scheduled' : 'pending')),
                    'delivery_address' => $deliveryAddress,
                    'd_lat' => $this->resolveDropoffLatitude($context, $restaurant, $fulfillmentMode),
                    'd_lng' => $this->resolveDropoffLongitude($context, $restaurant, $fulfillmentMode),
                    'ordered_time' => $orderedTime,
                    'delivered_time' => $deliveredTime,
                    'created_at' => $createdAt,
                    'updated_at' => $updatedAt,
                ];

                if ($schema['latitude']) {
                    $payload['latitude'] = $this->resolveLatitude($context, $restaurant, $fulfillmentMode);
                }

                if ($schema['longitude']) {
                    $payload['longitude'] = $this->resolveLongitude($context, $restaurant, $fulfillmentMode);
                }

                if ($schema['payment_method']) {
                    $payload['payment_method'] = (string) ($context['payment_method'] ?? 'cash');
                }

                if ($schema['payment_status']) {
                    $payload['payment_status'] = (string) ($context['payment_status'] ?? OrderPaymentStatus::PENDING->value);
                }

                if ($schema['business_status']) {
                    $payload['business_status'] = (string) ($context['business_status'] ?? 'pending_restaurant_acceptance');
                }

                if ($schema['scheduled_date'] && $scheduledAt) {
                    $payload['scheduled_date'] = $scheduledAt;
                }

                if ($schema['fulfillment_mode']) {
                    $payload['fulfillment_mode'] = $fulfillmentMode;
                }

                if ($schema['pickup_code']) {
                    $payload['pickup_code'] = $pickupCode;
                }

                DB::table('orders')->insert($payload);
            }
        });

        return $orderNo;
    }

    private function resolveOrderNo(?string $orderNo): string
    {
        $orderNo = trim((string) $orderNo);

        return $orderNo !== ''
            ? $orderNo
            : 'TD-' . date('Ymd') . '-' . random_int(1000, 9999);
    }

    private function normalizeFulfillmentMode(?string $fulfillmentMode): string
    {
        return strtolower((string) $fulfillmentMode) === 'pickup' ? 'pickup' : 'delivery';
    }

    private function resolveScheduledAt(mixed $scheduledAt): ?Carbon
    {
        if ($scheduledAt instanceof Carbon) {
            return $scheduledAt;
        }

        if (empty($scheduledAt)) {
            return null;
        }

        try {
            return Carbon::parse($scheduledAt);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function resolvePickupCode(string $fulfillmentMode, array $context): ?string
    {
        if (array_key_exists('pickup_code', $context)) {
            $pickupCode = trim((string) $context['pickup_code']);

            return $pickupCode !== '' ? $pickupCode : null;
        }

        return $fulfillmentMode === 'pickup'
            ? (string) random_int(1000, 9999)
            : null;
    }

    private function resolveRestaurant(Collection $cartItems): ?Restaurant
    {
        $restaurantId = optional($cartItems->first())->restaurant_id;

        return $restaurantId ? Restaurant::find($restaurantId) : null;
    }

    private function resolveDeliveryAddress(?Restaurant $restaurant, array $context, string $fulfillmentMode): string
    {
        $deliveryAddress = trim((string) ($context['delivery_address'] ?? ''));

        if ($deliveryAddress !== '') {
            return $deliveryAddress;
        }

        if ($fulfillmentMode !== 'pickup') {
            return '';
        }

        return trim(implode(' | ', array_filter([
            'Retrait sur place',
            $restaurant?->name,
            $restaurant?->address,
            !empty($context['pickup_note']) ? 'Note: ' . trim((string) $context['pickup_note']) : null,
        ])));
    }

    private function resolveItemPrice(mixed $item): float
    {
        $product = Product::find($item->product_id);

        if (! $product) {
            return (float) ($item->price ?? 0);
        }

        return (float) ($product->discount_price > 0 ? $product->discount_price : $product->price);
    }

    private function resolveLatitude(array $context, ?Restaurant $restaurant, string $fulfillmentMode): ?string
    {
        if (array_key_exists('latitude', $context)) {
            return $this->stringOrNull($context['latitude']);
        }

        if ($fulfillmentMode === 'pickup') {
            return $this->stringOrNull($restaurant?->latitude);
        }

        return $this->stringOrNull($context['d_lat'] ?? null);
    }

    private function resolveLongitude(array $context, ?Restaurant $restaurant, string $fulfillmentMode): ?string
    {
        if (array_key_exists('longitude', $context)) {
            return $this->stringOrNull($context['longitude']);
        }

        if ($fulfillmentMode === 'pickup') {
            return $this->stringOrNull($restaurant?->longitude);
        }

        return $this->stringOrNull($context['d_lng'] ?? null);
    }

    private function resolveDropoffLatitude(array $context, ?Restaurant $restaurant, string $fulfillmentMode): string
    {
        if (array_key_exists('d_lat', $context) && $this->stringOrNull($context['d_lat']) !== null) {
            return (string) $context['d_lat'];
        }

        if ($fulfillmentMode === 'pickup' && $this->stringOrNull($restaurant?->latitude) !== null) {
            return (string) $restaurant->latitude;
        }

        return self::DEFAULT_FALLBACK_LAT;
    }

    private function resolveDropoffLongitude(array $context, ?Restaurant $restaurant, string $fulfillmentMode): string
    {
        if (array_key_exists('d_lng', $context) && $this->stringOrNull($context['d_lng']) !== null) {
            return (string) $context['d_lng'];
        }

        if ($fulfillmentMode === 'pickup' && $this->stringOrNull($restaurant?->longitude) !== null) {
            return (string) $restaurant->longitude;
        }

        return self::DEFAULT_FALLBACK_LNG;
    }

    private function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private function orderColumnMap(): array
    {
        if ($this->orderColumnMap !== null) {
            return $this->orderColumnMap;
        }

        $columns = [
            'latitude',
            'longitude',
            'payment_method',
            'payment_status',
            'business_status',
            'scheduled_date',
            'fulfillment_mode',
            'pickup_code',
        ];

        $this->orderColumnMap = [];
        foreach ($columns as $column) {
            $this->orderColumnMap[$column] = Schema::hasColumn('orders', $column);
        }

        return $this->orderColumnMap;
    }
}
