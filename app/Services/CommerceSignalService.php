<?php

namespace App\Services;

use App\CommerceSignal;
use App\Delivery;
use App\Order;
use App\User;
use Illuminate\Support\Facades\Schema;

class CommerceSignalService
{
    public function emit(string $signalType, array $payload = []): ?CommerceSignal
    {
        if (!Schema::hasTable('commerce_signals')) {
            return null;
        }

        return CommerceSignal::create([
            'signal_type' => $signalType,
            'domain' => $payload['domain'] ?? 'commerce',
            'module' => $payload['module'] ?? 'global',
            'severity' => $payload['severity'] ?? 'info',
            'order_id' => $payload['order_id'] ?? null,
            'order_no' => $payload['order_no'] ?? null,
            'user_id' => $payload['user_id'] ?? null,
            'restaurant_id' => $payload['restaurant_id'] ?? null,
            'driver_id' => $payload['driver_id'] ?? null,
            'subject_type' => $payload['subject_type'] ?? null,
            'subject_id' => $payload['subject_id'] ?? null,
            'payload' => $payload,
        ]);
    }

    public function emitOrder(Order $order, string $signalType, array $payload = []): ?CommerceSignal
    {
        $payload['module'] = $payload['module'] ?? 'food';
        $payload['order_id'] = $payload['order_id'] ?? $order->id;
        $payload['order_no'] = $payload['order_no'] ?? $order->order_no;
        $payload['user_id'] = $payload['user_id'] ?? $order->user_id;
        $payload['restaurant_id'] = $payload['restaurant_id'] ?? $order->restaurant_id;

        return $this->emit($signalType, $payload);
    }

    public function emitDelivery(Delivery $delivery, string $signalType, array $payload = []): ?CommerceSignal
    {
        $payload['module'] = $payload['module'] ?? 'food';
        $payload['order_id'] = $payload['order_id'] ?? $delivery->order_id;
        $payload['order_no'] = $payload['order_no'] ?? optional($delivery->order)->order_no;
        $payload['restaurant_id'] = $payload['restaurant_id'] ?? $delivery->restaurant_id;
        $payload['driver_id'] = $payload['driver_id'] ?? $delivery->driver_id;

        return $this->emit($signalType, $payload);
    }

    public function emitUser(User $user, string $signalType, array $payload = []): ?CommerceSignal
    {
        $payload['module'] = $payload['module'] ?? 'global';
        $payload['user_id'] = $payload['user_id'] ?? $user->id;

        return $this->emit($signalType, $payload);
    }
}
