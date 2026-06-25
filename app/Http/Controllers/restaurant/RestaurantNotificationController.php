<?php

namespace App\Http\Controllers\restaurant;

use App\Http\Controllers\Controller;
use App\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RestaurantNotificationController extends Controller
{
    public function __invoke(Request $request)
    {
        $restaurant = auth()->user()?->restaurant;
        if (! $restaurant) {
            return response()->json([
                'status' => false,
                'count' => 0,
                'orders' => [],
                'new' => false,
                'next_cursor' => 0,
            ], 403);
        }

        $afterId = max(0, (int) $request->query('after_id', 0));

        $orders = Order::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('business_status', 'pending_restaurant_acceptance')
            ->selectRaw('order_no, MAX(id) as cursor_id, MIN(created_at) as created_at')
            ->groupBy('order_no')
            ->orderByDesc('cursor_id')
            ->get()
            ->map(function ($order) {
                $order->time = Carbon::parse($order->created_at)->diffForHumans();
                return $order;
            });

        $nextCursor = max($afterId, (int) ($orders->max('cursor_id') ?? 0));

        return response()->json([
            'status' => true,
            'orders' => $orders,
            'count' => $orders->count(),
            'new' => $orders->contains(fn ($order) => (int) $order->cursor_id > $afterId),
            'next_cursor' => $nextCursor,
        ]);
    }
}
