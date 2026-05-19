<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Order;
use App\Services\DeliveryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class OrderTrackingController extends Controller
{
    public function __construct(
        protected DeliveryService $deliveryService
    ) {}
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
        $order = $this->resolveOwnedOrder($order, $user->id, ['delivery.driver', 'restaurant', 'user']);

        if (!$order) {
            return response()->json([
                'status' => false,
                'message' => 'Commande introuvable'
            ], 404);
        }
        
        $delivery = $order->delivery;

        if ($delivery) {
            $this->deliveryService->ensureDeliveryOtp($delivery);
            $delivery = $delivery->fresh(['driver']);
            $order->setRelation('delivery', $delivery);
        }

        $delivery = $order->delivery;
        $businessStatus = $order->resolveEffectiveBusinessStatus();
        $trackingStatus = $order->resolveTrackingStatus();
        
        if (!$delivery) {
            return response()->json([
                'status' => false,
                'message' => 'Aucune livraison trouvée pour cette commande',
                'data' => [
                    'order_id' => $order->id,
                    'order_no' => $order->order_no,
                    'status' => 'NO_DELIVERY',
                    'business_status' => $businessStatus,
                    'tracking_status' => $trackingStatus,
                    'driver' => null,
                    'timestamps' => [],
                ]
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private')
              ->header('Pragma', 'no-cache')
              ->header('Expires', '0');
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
            $latestLocation = \Illuminate\Support\Facades\Schema::hasTable('driver_locations')
                ? \App\DriverLocation::where('driver_id', $delivery->driver->id)
                    ->orderBy('timestamp', 'desc')
                    ->first()
                : null;
            
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
                'business_status' => $businessStatus,
                'tracking_status' => $trackingStatus,
                'delivery_otp_required' => $delivery->requiresOtp(),
                'customer_confirmed_at' => $delivery->customer_confirmed_at?->toIso8601String(),
                'delivery_confirmation_method' => $delivery->delivery_confirmation_method,
                'incident_status' => $delivery->incident_status,
                'incident_reason' => $delivery->incident_reason,
                'incident_notes' => $delivery->incident_notes,
                'failed_attempts' => (int) ($delivery->failed_attempts ?? 0),
                'support_status' => $delivery->support_status,
                'redelivery_requested_at' => $delivery->redelivery_requested_at?->toIso8601String(),
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
                    'ordered_at' => $order->ordered_time ? Carbon::parse($order->ordered_time)->toIso8601String() : null,
                    'assigned_at' => $delivery->assigned_at ? Carbon::parse($delivery->assigned_at)->toIso8601String() : null,
                    'picked_up_at' => $delivery->picked_up_at ? Carbon::parse($delivery->picked_up_at)->toIso8601String() : null,
                    'delivered_at' => $delivery->delivered_at ? Carbon::parse($delivery->delivered_at)->toIso8601String() : null,
                ],
            ]
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private')
          ->header('Pragma', 'no-cache')
          ->header('Expires', '0');
    }

    public function confirmDelivery(Request $request, $order)
    {
        $request->validate([
            'delivery_otp' => 'nullable|string|max:12',
            'customer_confirmed' => 'nullable|boolean',
        ]);

        $user = Auth::user();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Non authentifié'], 401);
        }

        $order = $this->resolveOwnedOrder($order, $user->id, ['delivery']);

        if (!$order) {
            return response()->json([
                'status' => false,
                'message' => 'Commande introuvable'
            ], 404);
        }

        if (!$order->delivery) {
            return response()->json(['status' => false, 'message' => 'Aucune livraison associée'], 404);
        }

        $delivery = $order->delivery;

        if ($delivery->requiresOtp() && !$this->deliveryService->verifyDeliveryOtp($delivery, $request->input('delivery_otp'))) {
            return response()->json(['status' => false, 'message' => 'OTP invalide'], 422);
        }

        $delivery->update([
            'customer_confirmed_at' => now(),
            'otp_verified_at' => $request->filled('delivery_otp') ? now() : $delivery->otp_verified_at,
            'delivery_confirmation_method' => $request->filled('delivery_otp') ? 'otp' : 'customer_button',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Réception confirmée',
            'data' => [
                'customer_confirmed_at' => $delivery->fresh()->customer_confirmed_at?->toIso8601String(),
                'delivery_confirmation_method' => $delivery->fresh()->delivery_confirmation_method,
            ],
        ]);
    }

    public function reportIncident(Request $request, $order)
    {
        $request->validate([
            'reason' => 'required|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        $user = Auth::user();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Non authentifié'], 401);
        }

        $order = $this->resolveOwnedOrder($order, $user->id, ['delivery']);

        if (!$order) {
            return response()->json([
                'status' => false,
                'message' => 'Commande introuvable'
            ], 404);
        }

        if (!$order->delivery) {
            return response()->json(['status' => false, 'message' => 'Aucune livraison associée'], 404);
        }

        $delivery = $this->deliveryService->reportIncident($order->delivery, $request->input('reason'), [
            'actor_type' => 'customer',
            'actor_id' => $user->id,
            'notes' => $request->input('notes'),
            'support_status' => 'open',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Incident signalé',
            'data' => [
                'incident_status' => $delivery->incident_status,
                'incident_reason' => $delivery->incident_reason,
                'support_status' => $delivery->support_status,
            ],
        ]);
    }

    public function requestRedelivery(Request $request, $order)
    {
        $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $user = Auth::user();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Non authentifié'], 401);
        }

        $order = $this->resolveOwnedOrder($order, $user->id, ['delivery']);

        if (!$order) {
            return response()->json([
                'status' => false,
                'message' => 'Commande introuvable'
            ], 404);
        }

        if (!$order->delivery) {
            return response()->json(['status' => false, 'message' => 'Aucune livraison associée'], 404);
        }

        $delivery = $this->deliveryService->requestRedelivery($order->delivery, [
            'actor_type' => 'customer',
            'actor_id' => $user->id,
            'notes' => $request->input('notes'),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Re-livraison demandée',
            'data' => [
                'status' => $delivery->status,
                'support_status' => $delivery->support_status,
                'redelivery_requested_at' => $delivery->redelivery_requested_at?->toIso8601String(),
            ],
        ]);
    }

    private function resolveOwnedOrder($order, int $userId, array $relations = []): ?Order
    {
        if ($order instanceof Order) {
            $order->load($relations);

            return $order->user_id === $userId ? $order : null;
        }

        return Order::with($relations)
            ->where('id', $order)
            ->where('user_id', $userId)
            ->first();
    }
}
