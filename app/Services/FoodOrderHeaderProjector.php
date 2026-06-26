<?php

namespace App\Services;

use App\FoodOrderHeader;
use App\Order;
use Illuminate\Support\Facades\Schema;

class FoodOrderHeaderProjector
{
    public function project(string $orderNo): ?FoodOrderHeader
    {
        if (! Schema::hasTable('food_order_headers')) {
            return null;
        }

        $lines = Order::where('order_no', $orderNo)->orderBy('id')->get();
        $first = $lines->first();

        if (! $first) {
            return null;
        }

        return FoodOrderHeader::updateOrCreate(
            ['order_no' => $orderNo],
            [
                'restaurant_id' => $first->restaurant_id,
                'user_id' => $first->user_id,
                'primary_order_id' => $first->id,
                'items_count' => $lines->count(),
                'total_quantity' => $lines->sum('qty'),
                'total' => (float) $first->total,
                'currency' => 'XAF',
                'fulfillment_mode' => (string) ($first->fulfillment_mode ?: 'delivery'),
                'business_status' => (string) ($first->business_status ?: $first->status),
                'payment_status' => (string) $first->payment_status,
                'scheduled_at' => $first->scheduled_date,
                'source_created_at' => $first->created_at,
            ]
        );
    }
}
