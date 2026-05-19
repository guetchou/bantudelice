<?php

namespace App\Http\Controllers;

use App\Driver;
use App\Delivery;
use App\DeliveryOffer;
use App\Services\DeliveryService;
use App\Services\OrderChatService;
use App\Services\PartnerFinancialDashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * Controller pour la gestion des livraisons côté livreur (interface web)
 */
class DriverDeliveriesController extends Controller
{
    public function __construct(
        protected DeliveryService $deliveryService
    ) {}

    /**
     * S3.4 — Résolution Driver depuis l'User connecté.
     * Priorité : user_id (si colonne existe) → email → phone → name.
     */
    private function resolveDriverFromUser($user): ?Driver
    {
        if (Schema::hasColumn('drivers', 'user_id')) {
            $d = Driver::where('user_id', $user->id)->first();
            if ($d) return $d;
        }
        $d = Driver::where('email', $user->email)
                   ->orWhere('phone', $user->phone)
                   ->first();
        if (!$d && $user->type === 'driver') {
            $d = Driver::where('name', $user->name)->first();
        }
        return $d;
    }

    /**
     * Afficher la page des livraisons pour le livreur
     * 
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Vérifier que l'utilisateur est connecté
        if (!auth()->check()) {
            if ($request->has('json')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Non authentifié'
                ], 401);
            }
            return redirect()->route('user.login')->with('alert', [
                'type' => 'warning',
                'message' => 'Veuillez vous connecter pour accéder à cette page'
            ]);
        }
        
        $user = auth()->user();
        
        $driver = $this->resolveDriverFromUser($user);
        
        if (!$driver) {
            if ($request->has('json')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Aucun compte livreur associé à votre profil'
                ], 404);
            }
            return redirect()->route('home')->with('alert', [
                'type' => 'warning',
                'message' => 'Aucun compte livreur associé à votre profil'
            ]);
        }
        
        // Récupérer les livraisons actives
        $deliveries = Delivery::with(['order.user', 'restaurant'])
            ->where('driver_id', $driver->id)
            ->whereIn('status', ['ASSIGNED', 'PICKED_UP', 'ON_THE_WAY'])
            ->orderBy('created_at', 'desc')
            ->get();

        $chatService = app(OrderChatService::class);
        $deliveries = $deliveries->map(function ($delivery) use ($user, $chatService) {
            if ($delivery->order) {
                $delivery->chatBadge = $chatService->badgeDataForOrder($delivery->order, $user);
                $delivery->chatData = $chatService->viewDataForOrder($delivery->order, $user, false);
            }

            return $delivery;
        });
        
        // Si requête JSON (AJAX), retourner JSON
        if ($request->has('json')) {
            $data = $deliveries->map(function($delivery) {
                return [
                    'id' => $delivery->id,
                    'order_id' => $delivery->order_id,
                    'order_no' => $delivery->order->order_no ?? null,
                    'status' => $delivery->status,
                    'business_status' => method_exists($delivery->order, 'resolveEffectiveBusinessStatus') ? $delivery->order->resolveEffectiveBusinessStatus() : null,
                    'restaurant' => [
                        'id' => $delivery->restaurant->id ?? null,
                        'name' => $delivery->restaurant->name ?? null,
                        'address' => $delivery->restaurant->address ?? null,
                        'phone' => $delivery->restaurant->phone ?? null,
                    ],
                    'customer' => [
                        'name' => $delivery->order->user->name ?? null,
                        'phone' => $delivery->order->user->phone ?? null,
                    ],
                    'delivery_address' => $delivery->order->delivery_address ?? null,
                    'delivery_fee' => $delivery->delivery_fee,
                    'total' => $delivery->order->total ?? null,
                    'assigned_at' => $delivery->assigned_at?->toIso8601String(),
                    'picked_up_at' => $delivery->picked_up_at?->toIso8601String(),
                    'delivered_at' => $delivery->delivered_at?->toIso8601String(),
                    'customer_confirmed_at' => $delivery->customer_confirmed_at?->toIso8601String(),
                    'delivery_otp_required' => method_exists($delivery, 'requiresOtp') ? $delivery->requiresOtp() : false,
                    'delivery_otp_code' => $delivery->delivery_otp_code,
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
    
    /**
     * Mettre à jour le statut d'une livraison (route web)
     * 
     * @param Request $request
     * @param int $deliveryId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateStatus(Request $request, $deliveryId)
    {
        $request->validate([
            'status' => 'required|in:PICKED_UP,ON_THE_WAY,DELIVERED',
            'pickup_notes' => 'nullable|string|max:1000',
            'delivery_notes' => 'nullable|string|max:1000',
            'customer_confirmed' => 'nullable|boolean',
            'delivery_otp' => 'nullable|string|max:12',
            'pickup_proof' => 'nullable|file|image|max:4096',
            'delivery_proof' => 'nullable|file|image|max:4096',
            'pickup_latitude' => 'nullable|numeric',
            'pickup_longitude' => 'nullable|numeric',
            'delivery_latitude' => 'nullable|numeric',
            'delivery_longitude' => 'nullable|numeric',
        ]);
        
        $user = auth()->user();
        
        if (!$user) {
            return redirect()->back()->with('alert', [
                'type' => 'danger',
                'message' => 'Non authentifié'
            ]);
        }
        
        $driver = $this->resolveDriverFromUser($user);
        if (!$driver) {
            return redirect()->back()->with('alert', [
                'type' => 'danger',
                'message' => 'Aucun compte livreur associé'
            ]);
        }
        
        $delivery = Delivery::where('id', $deliveryId)
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
                'pickup_notes' => $request->input('pickup_notes'),
                'delivery_notes' => $request->input('delivery_notes'),
                'customer_confirmed' => $request->boolean('customer_confirmed'),
                'delivery_otp' => $request->input('delivery_otp'),
                'pickup_proof_path' => $pickupProofPath,
                'delivery_proof_path' => $deliveryProofPath,
                'pickup_latitude' => $request->input('pickup_latitude'),
                'pickup_longitude' => $request->input('pickup_longitude'),
                'delivery_latitude' => $request->input('delivery_latitude'),
                'delivery_longitude' => $request->input('delivery_longitude'),
            ]);
            
            return redirect()->back()->with('alert', [
                'type' => 'success',
                'message' => 'Statut mis à jour avec succès'
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('alert', [
                'type' => 'danger',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Endpoint AJAX de polling pour le livreur : retourne le nombre de livraisons ASSIGNED en attente.
     */
    public function pollAssignments()
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['status' => false], 401);
        }

        $driver = $this->resolveDriverFromUser($user);
        if (!$driver) {
            return response()->json(['status' => false, 'count' => 0]);
        }

        $count = Delivery::where('driver_id', $driver->id)
            ->whereIn('status', ['ASSIGNED', 'PICKED_UP', 'ON_THE_WAY'])
            ->count();

        $newAssigned = Delivery::where('driver_id', $driver->id)
            ->where('status', 'ASSIGNED')
            ->count();

        // Offre pendante pour ce livreur (broadcast offer model)
        $pendingOffer = null;
        if (\Illuminate\Support\Facades\Schema::hasTable('delivery_offers')) {
            $offer = DeliveryOffer::with(['delivery.order.restaurant'])
                ->where('driver_id', $driver->id)
                ->where('status', 'pending')
                ->where('expires_at', '>', now())
                ->orderBy('created_at', 'desc')
                ->first();

            if ($offer && $offer->delivery) {
                $order = $offer->delivery->order;
                $pendingOffer = [
                    'offer_id'        => $offer->id,
                    'delivery_id'     => $offer->delivery_id,
                    'order_no'        => $order->order_no ?? '—',
                    'restaurant_name' => $order->restaurant->name ?? 'Restaurant',
                    'distance_km'     => $offer->distance_km,
                    'expires_at'      => $offer->expires_at->toIso8601String(),
                    'expires_in_sec'  => max(0, (int) now()->diffInSeconds($offer->expires_at, false)),
                ];
            }
        }

        return response()->json([
            'status'       => true,
            'count'        => $count,
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

        $user = auth()->user();
        if (!$user) {
            return redirect()->back()->with('alert', [
                'type' => 'danger',
                'message' => 'Non authentifié'
            ]);
        }

        $driver = $this->resolveDriverFromUser($user);
        if (!$driver) {
            return redirect()->back()->with('alert', [
                'type' => 'danger',
                'message' => 'Aucun compte livreur associé'
            ]);
        }

        $delivery = Delivery::where('id', $deliveryId)
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
                'message' => 'Incident signalé. Le support peut maintenant intervenir.'
            ]);
        } catch (\Throwable $e) {
            return redirect()->back()->with('alert', [
                'type' => 'danger',
                'message' => $e->getMessage()
            ]);
        }
    }
}
