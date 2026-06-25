<?php

namespace App\Http\Controllers;

use App\Delivery;
use App\DeliveryOffer;
use App\Driver;
use App\Services\DeliveryService;
use App\Services\OrderChatService;
use App\Services\PartnerFinancialDashboardService;
use App\Support\Auth\AuthenticatedDriverResolver;
use Illuminate\Http\Request;

/**
 * Gestion des livraisons depuis le portail web livreur.
 */
class DriverDeliveriesController extends Controller
{
    public function __construct(
        protected DeliveryService $deliveryService,
        protected AuthenticatedDriverResolver $driverResolver
    ) {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $driver = $this->driverResolver->current();

        if (! $driver) {
            if ($request->has('json')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Compte livreur approuvé introuvable.',
                ], 403);
            }

            return redirect()->route('home')->with('alert', [
                'type' => 'warning',
                'message' => 'Votre compte livreur n’est pas approuvé ou n’est pas correctement associé.',
            ]);
        }

        $deliveries = Delivery::with(['order.user', 'restaurant'])
            ->where('driver_id', $driver->id)
            ->whereIn('status', ['ASSIGNED', 'PICKED_UP', 'ON_THE_WAY'])
            ->orderByDesc('created_at')
            ->get();

        $user = auth()->user();
        $chatService = app(OrderChatService::class);
        $deliveries = $deliveries->map(function (Delivery $delivery) use ($user, $chatService) {
            if ($delivery->order) {
                $delivery->chatBadge = $chatService->badgeDataForOrder($delivery->order, $user);
                $delivery->chatData = $chatService->viewDataForOrder($delivery->order, $user, false);
            }

            return $delivery;
        });

        if ($request->has('json')) {
            $data = $deliveries->map(function (Delivery $delivery) {
                $order = $delivery->order;

                return [
                    'id' => $delivery->id,
                    'order_id' => $delivery->order_id,
                    'order_no' => $order->order_no ?? null,
                    'status' => $delivery->status,
                    'business_status' => $order && method_exists($order, 'resolveEffectiveBusinessStatus')
                        ? $order->resolveEffectiveBusinessStatus()
                        : null,
                    'restaurant' => [
                        'id' => $delivery->restaurant->id ?? null,
                        'name' => $delivery->restaurant->name ?? null,
                        'address' => $delivery->restaurant->address ?? null,
                        'phone' => $delivery->restaurant->phone ?? null,
                    ],
                    'customer' => [
                        'name' => $order->user->name ?? null,
                        'phone' => $order->user->phone ?? null,
                    ],
                    'delivery_address' => $order->delivery_address ?? null,
                    'delivery_fee' => $delivery->delivery_fee,
                    'total' => $order->total ?? null,
                    'assigned_at' => $delivery->assigned_at?->toIso8601String(),
                    'picked_up_at' => $delivery->picked_up_at?->toIso8601String(),
                    'delivered_at' => $delivery->delivered_at?->toIso8601String(),
                    'customer_confirmed_at' => $delivery->customer_confirmed_at?->toIso8601String(),
                    'delivery_otp_required' => $delivery->requiresOtp(),
                    'pickup_proof_url' => $delivery->pickup_proof_path ? asset($delivery->pickup_proof_path) : null,
                    'delivery_proof_url' => $delivery->delivery_proof_path ? asset($delivery->delivery_proof_path) : null,
                    'created_at' => $delivery->created_at->toIso8601String(),
                ];
            });

            return response()->json([
                'status' => true,
                'data' => $data,
            ]);
        }

        $financialDashboard = app(PartnerFinancialDashboardService::class)->forDeliveryDriver($driver);

        return view('driver.deliveries', compact('deliveries', 'driver', 'financialDashboard'));
    }

    public function updateStatus(Request $request, $deliveryId)
    {
        $request->validate([
            'status' => 'required|in:PICKED_UP,ON_THE_WAY,DELIVERED',
            'pickup_notes' => 'nullable|string|max:1000',
            'delivery_notes' => 'nullable|string|max:1000',
            'delivery_otp' => 'nullable|string|max:12',
            'pickup_proof' => 'nullable|file|image|max:4096',
            'delivery_proof' => 'nullable|file|image|max:4096',
            'pickup_latitude' => 'nullable|numeric|between:-90,90',
            'pickup_longitude' => 'nullable|numeric|between:-180,180',
            'delivery_latitude' => 'nullable|numeric|between:-90,90',
            'delivery_longitude' => 'nullable|numeric|between:-180,180',
            'cash_collection_outcome' => 'nullable|in:collected,collection_failed',
        ]);

        $driver = $this->driverResolver->current();
        if (! $driver) {
            return redirect()->back()->with('alert', [
                'type' => 'danger',
                'message' => 'Compte livreur non autorisé.',
            ]);
        }

        $delivery = Delivery::whereKey($deliveryId)
            ->where('driver_id', $driver->id)
            ->firstOrFail();

        try {
            $pickupProofPath = $request->hasFile('pickup_proof')
                ? $this->deliveryService->storeProofFile($request->file('pickup_proof'), 'pickup')
                : null;
            $deliveryProofPath = $request->hasFile('delivery_proof')
                ? $this->deliveryService->storeProofFile($request->file('delivery_proof'), 'delivery')
                : null;

            $this->deliveryService->updateStatus($delivery, $request->input('status'), [
                'actor_type' => 'driver',
                'actor_id' => $driver->id,
                'pickup_notes' => $request->input('pickup_notes'),
                'delivery_notes' => $request->input('delivery_notes'),
                'delivery_otp' => $request->input('delivery_otp'),
                'pickup_proof_path' => $pickupProofPath,
                'delivery_proof_path' => $deliveryProofPath,
                'pickup_latitude' => $request->input('pickup_latitude'),
                'pickup_longitude' => $request->input('pickup_longitude'),
                'delivery_latitude' => $request->input('delivery_latitude'),
                'delivery_longitude' => $request->input('delivery_longitude'),
                'cash_collection_outcome' => $request->input('cash_collection_outcome'),
            ]);

            return redirect()->back()->with('alert', [
                'type' => 'success',
                'message' => 'Statut mis à jour avec succès.',
            ]);
        } catch (\Throwable $e) {
            return redirect()->back()->with('alert', [
                'type' => 'danger',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function pollAssignments()
    {
        $driver = $this->driverResolver->current();
        if (! $driver) {
            return response()->json(['status' => false, 'count' => 0], 403);
        }

        $count = Delivery::where('driver_id', $driver->id)
            ->whereIn('status', ['ASSIGNED', 'PICKED_UP', 'ON_THE_WAY'])
            ->count();

        $newAssigned = Delivery::where('driver_id', $driver->id)
            ->where('status', 'ASSIGNED')
            ->count();

        $pendingOffer = null;

        if ($driver->status === 'online') {
            $offer = DeliveryOffer::with(['delivery.order.restaurant'])
                ->where('driver_id', $driver->id)
                ->where('status', 'pending')
                ->where('expires_at', '>', now())
                ->orderByDesc('created_at')
                ->first();

            if ($offer && $offer->delivery) {
                $order = $offer->delivery->order;
                $pendingOffer = [
                    'offer_id' => $offer->id,
                    'delivery_id' => $offer->delivery_id,
                    'order_no' => $order->order_no ?? '—',
                    'restaurant_name' => $order->restaurant->name ?? 'Restaurant',
                    'distance_km' => $offer->distance_km,
                    'expires_at' => $offer->expires_at->toIso8601String(),
                    'expires_in_sec' => max(0, (int) now()->diffInSeconds($offer->expires_at, false)),
                ];
            }
        }

        return response()->json([
            'status' => true,
            'count' => $count,
            'new_assigned' => $newAssigned,
            'pending_offer' => $pendingOffer,
        ]);
    }

    public function reportIncident(Request $request, $deliveryId)
    {
        $request->validate([
            'reason' => 'required|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        $driver = $this->driverResolver->current();
        if (! $driver) {
            return redirect()->back()->with('alert', [
                'type' => 'danger',
                'message' => 'Compte livreur non autorisé.',
            ]);
        }

        $delivery = Delivery::whereKey($deliveryId)
            ->where('driver_id', $driver->id)
            ->firstOrFail();

        try {
            $this->deliveryService->reportIncident($delivery, $request->input('reason'), [
                'actor_type' => 'driver',
                'actor_id' => $driver->id,
                'notes' => $request->input('notes'),
            ]);

            return redirect()->back()->with('alert', [
                'type' => 'warning',
                'message' => 'Incident signalé. Le support peut maintenant intervenir.',
            ]);
        } catch (\Throwable $e) {
            return redirect()->back()->with('alert', [
                'type' => 'danger',
                'message' => $e->getMessage(),
            ]);
        }
    }
}
