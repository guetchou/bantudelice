<?php

namespace App\Http\Controllers\restaurant;

use App\Http\Controllers\Controller;
use App\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;

class KitchenController extends Controller
{
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

        $allowedStatuses = ['pending', 'prepairing', 'assign', 'completed', 'cancelled', 'scheduled'];
        $statuses = $request->get('status', ['pending', 'prepairing', 'assign']);
        if (!is_array($statuses)) {
            $statuses = [$statuses];
        }
        $statuses = array_values(array_intersect($statuses, $allowedStatuses));
        if (empty($statuses)) {
            $statuses = ['pending', 'prepairing', 'assign'];
        }

        $q = Order::query()
            ->where('restaurant_id', $restaurant->id)
            ->whereIn('status', $statuses)
            ->with([
                'user:id,name,phone',
                'product:id,name,image',
            ])
            ->orderBy('created_at', 'desc');

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
                'created_at' => optional($first->created_at)->toIso8601String(),
                'updated_at' => optional($rows->max('updated_at'))->toIso8601String(),
                'customer' => [
                    'id' => $first->user_id,
                    'name' => $first->user->name ?? 'Client',
                    'phone' => $first->user->phone ?? null,
                ],
                'delivery_address' => $first->delivery_address ?? null,
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
            'status' => ['required', 'in:pending,prepairing,assign,completed,cancelled'],
        ]);

        $payload = [
            'status' => $request->status,
            'updated_at' => now(),
        ];
        // delivered_time est NOT NULL sur ce projet: on ne le touche que lors de "completed"
        if ($request->status === 'completed') {
            $payload['delivered_time'] = now();
        }

        $updated = Order::where('restaurant_id', $restaurant->id)
            ->where('order_no', $orderNo)
            ->update($payload);

        if ($updated <= 0) {
            return response()->json(['message' => 'Commande introuvable'], 404);
        }

        return response()->json([
            'message' => 'Statut mis à jour',
            'order_no' => $orderNo,
            'status' => $request->status,
        ]);
    }
}


