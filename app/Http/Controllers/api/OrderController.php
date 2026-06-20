<?php

namespace App\Http\Controllers\api;

use App\Cart;
use App\Domain\Food\Enums\OrderPaymentStatus;
use App\Domain\Food\Services\OrderPricingService;
use App\Domain\Food\Services\PlaceOrderService;
use App\Exceptions\RestaurantClosedException;
use App\Http\Controllers\Controller;
use App\Order;
use App\Services\NotificationService;
use App\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function notification($body, $title, $deviceToken, $key, $clickAction): JsonResponse
    {
        $result = NotificationService::sendWithAction($deviceToken, $title, $body, $key, $clickAction);

        return response()->json([
            'data' => ! empty($result['success']) ? 'notification sent' : 'notification failed',
            'action' => $result['action'] ?? null,
        ], 200);
    }

    public function getOrders(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'delivery_address' => 'required|string',
            'd_lat' => 'required',
            'd_lng' => 'required',
            'driver_tip' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string',
            'voucher_code' => 'nullable|string',
            'fulfillment_mode' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $validated = $validator->validated();
        $user = User::find($validated['user_id']);
        if (! $user) {
            return $this->notFoundResponse('Utilisateur introuvable');
        }

        $cartItems = Cart::where('user_id', $user->id)->get();
        if ($cartItems->isEmpty()) {
            return $this->notFoundResponse('Panier vide');
        }

        $totals = app(OrderPricingService::class)->calculate($cartItems, [
            'fulfillment_mode' => $validated['fulfillment_mode'] ?? null,
            'driver_tip' => $validated['driver_tip'] ?? 0,
            'voucher_code' => $validated['voucher_code'] ?? null,
        ]);

        try {
            $orderNo = app(PlaceOrderService::class)->placeFromCart($user, $cartItems, [
                'order_no' => 'D-' . rand(1000000, 9999999),
                'fulfillment_mode' => $validated['fulfillment_mode'] ?? null,
                'offer_discount' => (float) ($totals['discount'] ?? 0),
                'tax' => (float) ($totals['tax'] ?? 0),
                'delivery_charges' => (float) ($totals['delivery_fee'] ?? 0),
                'sub_total' => (float) ($totals['sub_total'] ?? 0),
                'total' => (float) ($totals['total'] ?? 0),
                'driver_tip' => (float) ($totals['driver_tip'] ?? 0),
                'delivery_address' => $validated['delivery_address'],
                'd_lat' => $validated['d_lat'],
                'd_lng' => $validated['d_lng'],
                'payment_method' => $validated['payment_method'] ?? 'cash',
                'payment_status' => OrderPaymentStatus::PENDING->value,
                'status' => 'pending',
                'ordered_time' => now(),
                'delivered_time' => null,
            ]);
        } catch (RestaurantClosedException $e) {
            $msg = $e->getMessage();
            if ($e->nextOpening) {
                $msg .= ' Prochaine ouverture : ' . $e->nextOpening . '.';
            }
            return response()->json(['status' => false, 'message' => $msg], 422);
        }

        Cart::where('user_id', $user->id)->delete();

        return response()->json([
            'status' => true,
            'order_no' => $orderNo,
        ]);
    }

    /**
     * S5.1 — Commandes en cours de l'utilisateur authentifié avec pagination.
     * Ownership : renvoie uniquement les commandes de l'utilisateur connecté.
     */
    public function UserOrderHistory(Request $request, $user = null): JsonResponse
    {
        // Résolution de l'utilisateur : depuis auth() si dispo, sinon {user} en param (rétrocompat app mobile)
        $authUser = auth('api')->user();
        $userId   = $authUser ? $authUser->id : (int) $user;

        // Ownership : un user ne peut voir que ses propres commandes
        if ($authUser && $authUser->id !== $userId) {
            return response()->json(['status' => false, 'message' => 'Non autorisé'], 403);
        }

        if (!$userId || !User::whereKey($userId)->exists()) {
            return response()->json(['status' => false, 'message' => 'Utilisateur introuvable', 'data' => []], 404);
        }

        $perPage = min((int) $request->query('per_page', 15), 50);

        $orders = DB::table('orders')
            ->join('restaurants', 'restaurants.id', '=', 'orders.restaurant_id')
            ->select('orders.order_no', 'orders.id', 'orders.total', 'restaurants.name',
                     'restaurants.logo', 'orders.restaurant_id', 'orders.status', 'orders.created_at')
            ->where('orders.user_id', $userId)
            ->where(function ($q) {
                $q->where('orders.status', 'pending')->orWhere('orders.status', 'assign');
            })
            ->latest('orders.created_at')
            ->paginate($perPage);

        return response()->json([
            'status' => true,
            'data'   => collect($orders->items())->map(fn ($o) => $this->historyPayload($o))->values(),
            'meta'   => [
                'current_page' => $orders->currentPage(),
                'last_page'    => $orders->lastPage(),
                'per_page'     => $orders->perPage(),
                'total'        => $orders->total(),
            ],
        ]);
    }

    /**
     * S5.1 — Historique des commandes complétées avec pagination et ownership.
     */
    public function UserCompletedOrderHistory(Request $request, $user = null): JsonResponse
    {
        $authUser = auth('api')->user();
        $userId   = $authUser ? $authUser->id : (int) $user;

        if ($authUser && $authUser->id !== $userId) {
            return response()->json(['status' => false, 'message' => 'Non autorisé'], 403);
        }

        if (!$userId || !User::whereKey($userId)->exists()) {
            return response()->json(['status' => false, 'message' => 'Utilisateur introuvable', 'data' => []], 404);
        }

        $perPage = min((int) $request->query('per_page', 15), 50);

        $orders = DB::table('completed_orders')
            ->join('restaurants', 'restaurants.id', '=', 'completed_orders.restaurant_id')
            ->select('completed_orders.order_no', 'completed_orders.id', 'completed_orders.total',
                     'restaurants.name', 'restaurants.logo', 'completed_orders.restaurant_id',
                     'completed_orders.status', 'completed_orders.created_at', 'completed_orders.ordered_time')
            ->where('completed_orders.user_id', $userId)
            ->whereIn('completed_orders.status', ['completed', 'cancelled'])
            ->latest('completed_orders.created_at')
            ->paginate($perPage);

        return response()->json([
            'status' => true,
            'data'   => collect($orders->items())->map(fn ($o) => array_merge($this->historyPayload($o), ['ordered_time' => $o->ordered_time]))->values(),
            'meta'   => [
                'current_page' => $orders->currentPage(),
                'last_page'    => $orders->lastPage(),
                'per_page'     => $orders->perPage(),
                'total'        => $orders->total(),
            ],
        ]);
    }

    public function completeOrders(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'driver_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $validated = $validator->validated();
        if (! User::whereKey($validated['user_id'])->exists()) {
            return $this->notFoundResponse('Utilisateur introuvable');
        }

        $orders = Order::where('user_id', $validated['user_id'])
            ->where('driver_id', $validated['driver_id'])
            ->get();

        if ($orders->isEmpty()) {
            return $this->notFoundResponse('Aucune commande à clôturer');
        }

        DB::transaction(function () use ($orders): void {
            DB::table('completed_orders')->insert(
                $orders->map(fn (Order $order) => $this->completedOrderPayload($order))->all()
            );

            Order::whereIn('id', $orders->pluck('id'))->delete();
        });

        return response()->json([
            'status' => true,
        ]);
    }

    private function completedOrderPayload(Order $order): array
    {
        return [
            'order_no' => $order->order_no,
            'user_id' => $order->user_id,
            'restaurant_id' => $order->restaurant_id,
            'product_id' => $order->product_id,
            'driver_id' => $order->driver_id,
            'qty' => $order->qty,
            'price' => (float) ($order->price ?? 0),
            'latitude' => $order->latitude,
            'longitude' => $order->longitude,
            'total_items' => (int) ($order->total_items ?? 1),
            'offer_discount' => (float) ($order->offer_discount ?? 0),
            'tax' => (float) ($order->tax ?? 0),
            'delivery_charges' => (float) ($order->delivery_charges ?? 0),
            'sub_total' => (float) ($order->sub_total ?? 0),
            'total' => (float) ($order->total ?? 0),
            'admin_commission' => (float) ($order->admin_commission ?? 0),
            'restaurant_commission' => (float) ($order->restaurant_commission ?? 0),
            'driver_tip' => (float) ($order->driver_tip ?? 0),
            'status' => in_array($order->status, ['pending', 'assign', 'prepairing', 'completed', 'cancelled', 'scheduled'], true)
                ? $order->status
                : 'completed',
            'delivery_address' => $order->delivery_address,
            'scheduled_date' => $order->scheduled_date,
            'd_lat' => $order->d_lat,
            'd_lng' => $order->d_lng,
            'ordered_time' => $order->ordered_time,
            'delivered_time' => $order->delivered_time,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function historyPayload($order): array
    {
        return [
            'id' => $order->id,
            'order_no' => $order->order_no,
            'total' => $order->total,
            'restaurant_id' => $order->restaurant_id,
            'restaurant_name' => $order->name,
            'restaurant_logo_url' => $this->restaurantLogoUrl($order->logo),
            'status' => $order->status,
            'created_at' => $order->created_at,
        ];
    }

    private function restaurantLogoUrl(?string $logo): ?string
    {
        if (empty($logo)) {
            return null;
        }

        return filter_var($logo, FILTER_VALIDATE_URL)
            ? $logo
            : URL::to('/') . '/images/restaurant_images/' . $logo;
    }

    private function validationErrorResponse($validator): JsonResponse
    {
        return response()->json([
            'status' => false,
            'error_code' => 101,
            'message' => implode(',', $validator->messages()->all()),
        ], 422);
    }

    private function notFoundResponse(string $message): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => $message,
        ], 404);
    }
}
