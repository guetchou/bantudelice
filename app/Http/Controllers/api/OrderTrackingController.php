<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderTrackingController extends Controller
{
    /**
     * Suivre le statut de livraison d'une commande
     * 
     * GET /api/orders/{order}/tracking
     * 
     * @param Request $request
     * @param int|Order $order (peut être un ID ou un modèle Order selon le binding)
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $order)
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Non authentifié'
            ], 401);
        }
        
        // Si $order est un ID (string/int), récupérer le modèle
        if (!($order instanceof Order)) {
            $order = Order::with(['delivery.driver', 'restaurant', 'user'])
                ->where('id', $order)
                ->where('user_id', $user->id)
                ->firstOrFail();
        } else {
            // Si c'est déjà un modèle, charger les relations et vérifier l'appartenance
            $order->load(['delivery.driver', 'restaurant', 'user']);
            if ($order->user_id !== $user->id) {
                return response()->json([
                    'status' => false,
                    'message' => 'Accès non autorisé à cette commande'
                ], 403);
            }
        }
        
        $delivery = $order->delivery;
        
        if (!$delivery) {
            return response()->json([
                'status' => false,
                'message' => 'Aucune livraison trouvée pour cette commande',
                'data' => [
                    'order_id' => $order->id,
                    'order_no' => $order->order_no,
                    'status' => 'NO_DELIVERY',
                    'driver' => null,
                    'timestamps' => [],
                ]
            ]);
        }
        
        // Mapper les statuts pour l'affichage
        $statusLabels = [
            'PENDING' => 'En attente d\'assignation',
            'ASSIGNED' => 'Assignée à un livreur',
            'PICKED_UP' => 'Récupérée au restaurant',
            'ON_THE_WAY' => 'En route',
            'DELIVERED' => 'Livrée',
            'CANCELLED' => 'Annulée',
        ];
        
        // Récupérer la position GPS du livreur (dernière position connue)
        $driverLocation = null;
        if ($delivery->driver) {
            // D'abord essayer depuis driver_locations (historique)
            $latestLocation = \App\DriverLocation::where('driver_id', $delivery->driver->id)
                ->orderBy('timestamp', 'desc')
                ->first();
            
            if ($latestLocation) {
                $driverLocation = [
                    'latitude' => (float) $latestLocation->latitude,
                    'longitude' => (float) $latestLocation->longitude,
                    'accuracy' => $latestLocation->accuracy ? (float) $latestLocation->accuracy : null,
                    'heading' => $latestLocation->heading ? (float) $latestLocation->heading : null,
                    'speed' => $latestLocation->speed ? (float) $latestLocation->speed : null,
                    'timestamp' => $latestLocation->timestamp->toIso8601String(),
                ];
            } elseif ($delivery->driver->latitude && $delivery->driver->longitude) {
                // Fallback sur la position dans la table drivers
                $driverLocation = [
                    'latitude' => (float) $delivery->driver->latitude,
                    'longitude' => (float) $delivery->driver->longitude,
                    'accuracy' => null,
                    'heading' => null,
                    'speed' => null,
                    'timestamp' => $delivery->driver->updated_at->toIso8601String(),
                ];
            }
        }
        
        // Coordonnées du restaurant
        $restaurantLocation = null;
        if ($order->restaurant) {
            if ($order->restaurant->latitude && $order->restaurant->longitude) {
                $restaurantLocation = [
                    'latitude' => (float) $order->restaurant->latitude,
                    'longitude' => (float) $order->restaurant->longitude,
                ];
            }
        }
        
        // Coordonnées de livraison (client)
        $deliveryLocation = null;
        if ($order->d_lat && $order->d_lng) {
            $deliveryLocation = [
                'latitude' => (float) $order->d_lat,
                'longitude' => (float) $order->d_lng,
            ];
        } elseif ($order->latitude && $order->longitude) {
            $deliveryLocation = [
                'latitude' => (float) $order->latitude,
                'longitude' => (float) $order->longitude,
            ];
        }
        
        return response()->json([
            'status' => true,
            'data' => [
                'order_id' => $order->id,
                'order_no' => $order->order_no,
                'delivery_status' => $delivery->status,
                'delivery_status_label' => $statusLabels[$delivery->status] ?? $delivery->status,
                'order_status' => $order->status,
                'driver' => $delivery->driver ? [
                    'id' => $delivery->driver->id,
                    'name' => $delivery->driver->name,
                    'phone' => $delivery->driver->phone,
                    'vehicle' => $delivery->driver->vehicle ?? null,
                    'location' => $driverLocation, // Position GPS en temps réel
                ] : null,
                'restaurant' => [
                    'id' => $order->restaurant->id ?? null,
                    'name' => $order->restaurant->name ?? null,
                    'address' => $order->restaurant->address ?? null,
                    'location' => $restaurantLocation, // Position GPS
                ],
                'delivery_address' => $order->delivery_address,
                'delivery_location' => $deliveryLocation, // Position GPS de livraison
                'timestamps' => [
                    'ordered_at' => $order->ordered_time?->toIso8601String(),
                    'assigned_at' => $delivery->assigned_at?->toIso8601String(),
                    'picked_up_at' => $delivery->picked_up_at?->toIso8601String(),
                    'delivered_at' => $delivery->delivered_at?->toIso8601String(),
                ],
            ]
        ]);
    }
}

