<?php

namespace App\Services;

use App\Order;
use App\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FinancialEventService
{
    public function record(array $payload): void
    {
        if (!Schema::hasTable('financial_events')) {
            return;
        }

        DB::table('financial_events')->insert([
            'order_no' => $payload['order_no'] ?? null,
            'order_id' => $payload['order_id'] ?? null,
            'payment_id' => $payload['payment_id'] ?? null,
            'user_id' => $payload['user_id'] ?? null,
            'event_type' => $payload['event_type'] ?? 'unknown',
            'provider' => $payload['provider'] ?? null,
            'amount' => $payload['amount'] ?? 0,
            'currency' => $payload['currency'] ?? 'XAF',
            'status' => $payload['status'] ?? null,
            'meta' => !empty($payload['meta']) ? json_encode($payload['meta']) : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function recordForPayment(Payment $payment, string $eventType, array $meta = []): void
    {
        $this->record([
            'order_no' => optional($payment->order)->order_no,
            'order_id' => $payment->order_id,
            'payment_id' => $payment->id,
            'user_id' => $payment->user_id,
            'event_type' => $eventType,
            'provider' => $payment->provider,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'status' => $payment->status,
            'meta' => $meta,
        ]);
    }

    public function recordForOrder(Order $order, string $eventType, array $meta = []): void
    {
        $this->record([
            'order_no' => $order->order_no,
            'order_id' => $order->id,
            'payment_id' => optional($order->payment)->id,
            'user_id' => $order->user_id,
            'event_type' => $eventType,
            'provider' => optional($order->payment)->provider ?? $order->payment_method,
            'amount' => $order->total,
            'currency' => 'XAF',
            'status' => $order->payment_status,
            'meta' => $meta,
        ]);
    }
}
