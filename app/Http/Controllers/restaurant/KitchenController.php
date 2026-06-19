<?php

namespace App\Http\Controllers\restaurant;

use App\Http\Controllers\Controller;
use App\Order;
use App\Services\FoodOrderStateMachineService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class KitchenController extends Controller
{
    public function __construct(
        protected FoodOrderStateMachineService $foodOrderStateMachine
    ) {}

    public function index()
    {
        $restaurant = auth()->user()->restaurant;
        if (!$restaurant) {
            return redirect()->route('restaurant.dashboard')->with('alert', [
                'type' => 'danger',
                'message' => "Aucun restaurant n'est associé à votre compte."
            ]);
        }

        return view('restaurant.order.kitchen', compact('restaurant'));
    }

    public function orders(Request $request)
    {
        $restaurant = auth()->user()->restaurant;
        if (!$restaurant) {
            return response()->json(['message' => "Aucun restaurant n'est associé à votre compte."], 422);
        }

        $allowedStatuses = ['pending', 'prepairing', 'assign', 'completed', 'cancelled', 'scheduled', 'accepted', 'accepted_awaiting_payment', 'confirmed', 'in_kitchen', 'ready_for_pickup', 'dispatching', 'driver_assigned', 'picked_up', 'out_for_delivery', 'customer_arrived', 'picked_up_by_customer', 'no_show'];
        $statuses = $request->get('status', ['pending', 'accepted_awaiting_payment', 'prepairing', 'assign', 'customer_arrived']);
        if (!is_array($statuses)) {
            $statuses = [$statuses];
        }
        $statuses = array_values(array_intersect($statuses, $allowedStatuses));
        if (empty($statuses)) {
            $statuses = ['pending', 'prepairing', 'assign', 'customer_arrived'];
        }

        $q = Order::query()
            ->where('restaurant_id', $restaurant->id)
            ->with([
                'user:id,name,phone',
                'product:id,name,image',
            ])
            ->orderBy('created_at', 'desc');

        $q->where(function ($query) use ($statuses) {
            $query->whereIn('status', $statuses);
            if (\Illuminate\Support\Facades\Schema::hasColumn('orders', 'business_status')) {
                $query->orWhereIn('business_status', $statuses);
            }
        });

        if ($request->filled('updated_after')) {
            try {
                $dt = Carbon::parse($request->updated_after);
                $q->where('updated_at', '>', $dt);
            } catch (\Exception $e) {
                // ignorer
            }
        }

        $orders = $q->get();

        $groups = $orders->groupBy('order_no')->map(function ($rows, $orderNo) {
            $first = $rows->first();
            $items = $rows->map(function ($row) {
                $img = $row->product->image ?? null;
                $imgUrl = null;
                if ($img) {
                    $imgUrl = strpos($img, 'http') === 0 ? $img : url('images/product_images/' . $img);
                }
                return [
                    'id' => $row->id,
                    'product_id' => $row->product_id,
                    'product_name' => $row->product->name ?? 'Produit',
                    'product_image' => $imgUrl,
                    'qty' => (int)($row->qty ?? 1),
                    'price' => (float)($row->price ?? 0),
                    'line_total' => (float)(($row->price ?? 0) * ($row->qty ?? 1)),
                ];
            })->values();

            $total = $rows->sum(function ($r) {
                return (float)(($r->price ?? 0) * ($r->qty ?? 1));
            });

            return [
                'order_no' => $orderNo,
                'status' => $first->status,
                'business_status' => $this->foodOrderStateMachine->resolveCurrentBusinessStatus($first),
                'technical_status' => $first->technical_status,
                'created_at' => optional($first->created_at)->toIso8601String(),
                'updated_at' => optional($rows->max('updated_at'))->toIso8601String(),
                'customer' => [
                    'id' => $first->user_id,
                    'name' => $first->user->name ?? 'Client',
                    'phone' => $first->user->phone ?? null,
                ],
                'delivery_address' => $first->delivery_address ?? null,
                'fulfillment_mode' => $first->fulfillment_mode ?? 'delivery',
                'pickup_code' => $first->pickup_code ?? null,
                'items' => $items,
                'items_count' => $items->count(),
                'total' => (float)$total,
            ];
        })->values();

        return response()->json([
            'message' => 'OK',
            'data' => $groups,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    public function updateStatus(Request $request, $orderNo)
    {
        $restaurant = auth()->user()->restaurant;
        if (!$restaurant) {
            return response()->json(['message' => "Aucun restaurant n'est associé à votre compte."], 422);
        }

        $request->validate([
            'status' => ['required', 'in:pending,accepted,accepted_awaiting_payment,confirmed,prepairing,in_kitchen,assign,ready_for_pickup,dispatching,completed,delivered,cancelled,customer_arrived,picked_up_by_customer,no_show'],
            'pickup_code' => ['nullable', 'string', 'max:12'],
        ]);

        $orders = Order::where('restaurant_id', $restaurant->id)
            ->where('order_no', $orderNo)
            ->get();

        if ($orders->isEmpty()) {
            return response()->json(['message' => 'Commande introuvable'], 404);
        }

        $firstOrder = $orders->first();
        if (($firstOrder->fulfillment_mode ?? 'delivery') === 'pickup' && $request->status === 'picked_up_by_customer' && !empty($firstOrder->pickup_code)) {
            if (trim((string) $request->input('pickup_code')) !== (string) $firstOrder->pickup_code) {
                return response()->json(['message' => 'Code de retrait invalide'], 422);
            }
        }

        $updatedOrders = $this->foodOrderStateMachine->transitionOrders($orders, $request->status, [
            'actor_type' => 'restaurant_kitchen',
            'actor_id' => auth()->id(),
            'notes' => $request->input('notes'),
        ]);

        return response()->json([
            'message' => 'Statut mis à jour',
            'order_no' => $orderNo,
            'status' => $updatedOrders->first()->status,
            'business_status' => $updatedOrders->first()->business_status,
        ]);
    }
}
