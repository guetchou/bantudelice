<?php

namespace App\Http\Controllers;

use App\Order;
use App\Services\OrderTrackingTokenService;
use Illuminate\Http\Request;

class GuestOrderTrackingController extends Controller
{
    public function show(Request $request, string $token, OrderTrackingTokenService $tokens)
    {
        $order = $tokens->resolveValidToken($token);

        if (! $order) {
            abort(404);
        }

        $order->load(['delivery', 'restaurant']);
        $order->tracking_status = $order->resolveTrackingStatus();
        $order->effective_business_status = $order->resolveEffectiveBusinessStatus();
        $order->tracking_progress = $order->resolveTrackingProgress();

        $orderItems = Order::where('order_no', $order->order_no)
            ->with(['product', 'restaurant'])
            ->get();

        $delivery = $order->delivery;
        $estimatedTime = $this->resolveEstimatedTime($order);
        $remainingMinutes = $this->resolveRemainingMinutes($order, $delivery, $estimatedTime);

        return response()
            ->view('frontend.track_order_guest', compact('order', 'orderItems', 'estimatedTime', 'remainingMinutes', 'delivery'))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    private function resolveEstimatedTime(Order $order): int
    {
        $estimatedTime = 30;
        if (! $order->restaurant || ! $order->restaurant->avg_delivery_time) {
            return $estimatedTime;
        }

        $rawEstimatedTime = trim((string) $order->restaurant->avg_delivery_time);
        if (is_numeric($rawEstimatedTime)) {
            return (int) $rawEstimatedTime;
        }

        if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $rawEstimatedTime, $matches)) {
            $estimatedTime = ((int) $matches[1] * 60) + (int) $matches[2];
            if ($estimatedTime === 0 && ! empty($matches[3])) {
                return (int) $matches[3];
            }

            return $estimatedTime;
        }

        if (preg_match('/(\d+)/', $rawEstimatedTime, $matches)) {
            return (int) $matches[1];
        }

        return $estimatedTime;
    }

    private function resolveRemainingMinutes(Order $order, $delivery, int $estimatedTime): int
    {
        $businessStatus = $order->effective_business_status;

        if (in_array($businessStatus, ['pending_restaurant_acceptance', 'accepted_awaiting_payment', 'confirmed'], true)) {
            return 0;
        }

        if (in_array($businessStatus, ['in_kitchen', 'ready_for_pickup'], true)) {
            $etaStartedAt = $order->preparation_started_at ?? $order->accepted_at ?? $order->created_at;
            return max(0, $estimatedTime - now()->diffInMinutes($etaStartedAt));
        }

        if (in_array($businessStatus, ['driver_assigned', 'dispatching', 'driver_arrived_at_restaurant'], true)) {
            $etaStartedAt = $delivery?->assigned_at ?? $order->ready_at ?? $order->preparation_started_at ?? $order->created_at;
            return max(0, $estimatedTime - now()->diffInMinutes($etaStartedAt));
        }

        if (in_array($businessStatus, ['picked_up', 'out_for_delivery', 'delivery_attempt_failed', 'incident_open'], true)) {
            $etaStartedAt = $delivery?->picked_up_at ?? $delivery?->assigned_at ?? $order->created_at;
            return max(0, $estimatedTime - now()->diffInMinutes($etaStartedAt));
        }

        return 0;
    }
}
