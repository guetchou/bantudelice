<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Delivery;
use App\Driver;
use App\Services\DeliveryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DriverDeliveriesController extends Controller
{
    public function __construct(
        protected DeliveryService $deliveryService
    ) {}
    
    /**
     * Liste des livraisons actives pour le livreur connecté
     * 
     * GET /api/driver/deliveries
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Récupérer le livreur depuis l'utilisateur connecté
        // Note: Adapte selon ta structure (user->driver ou user->type='driver')
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Non authentifié'
            ], 401);
        }
        
        // Chercher le driver lié à cet utilisateur
        // Option 1: Si driver est lié à user_id
        $driver = Driver::where('email', $user->email)
            ->orWhere('phone', $user->phone)
            ->first();
        
        // Option 2: Si user a un type='driver' et un driver_id
        if (!$driver && $user->type === 'driver') {
            // Chercher par nom ou autre identifiant
            $driver = Driver::where('name', $user->name)->first();
        }
        
        if (!$driver) {
            return response()->json([
                'status' => false,
                'message' => 'Aucun livreur associé à ce compte'
            ], 404);
        }
        
        $deliveries = $this->deliveryService->getActiveDeliveriesForDriver($driver);
        
        $data = $deliveries->map(function($delivery) {
            return [
                'id' => $delivery->id,
                'order_id' => $delivery->order_id,
                'order_no' => $delivery->order->order_no ?? null,
                'status' => $delivery->status,
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
                'created_at' => $delivery->created_at->toIso8601String(),
            ];
        });
        
        return response()->json([
            'status' => true,
            'data' => $data,
        ]);
    }
    
    /**
     * Mettre à jour le statut d'une livraison
     * 
     * PATCH /api/driver/deliveries/{delivery}/status
     * 
     * @param Request $request
     * @param int|Delivery $delivery (peut être un ID ou un modèle Delivery selon le binding)
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request, $delivery)
    {
        $request->validate([
            'status' => 'required|in:PICKED_UP,ON_THE_WAY,DELIVERED,CANCELLED',
        ]);
        
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Non authentifié'
            ], 401);
        }
        
        // Chercher le driver
        $driver = Driver::where('email', $user->email)
            ->orWhere('phone', $user->phone)
            ->first();
        
        if (!$driver && $user->type === 'driver') {
            $driver = Driver::where('name', $user->name)->first();
        }
        
        if (!$driver) {
            return response()->json([
                'status' => false,
                'message' => 'Aucun livreur associé à ce compte'
            ], 404);
        }
        
        // Si $delivery est un ID (string/int), récupérer le modèle
        if (!($delivery instanceof Delivery)) {
            $delivery = Delivery::where('id', $delivery)
                ->where('driver_id', $driver->id)
                ->firstOrFail();
        } else {
            // Si c'est déjà un modèle, vérifier l'appartenance
            if ($delivery->driver_id !== $driver->id) {
                return response()->json([
                    'status' => false,
                    'message' => 'Accès non autorisé à cette livraison'
                ], 403);
            }
        }
        
        try {
            $updatedDelivery = $this->deliveryService->updateStatus($delivery, $request->input('status'));
            
            return response()->json([
                'status' => true,
                'message' => 'Statut mis à jour avec succès',
                'data' => [
                    'id' => $updatedDelivery->id,
                    'status' => $updatedDelivery->status,
                    'picked_up_at' => $updatedDelivery->picked_up_at?->toIso8601String(),
                    'delivered_at' => $updatedDelivery->delivered_at?->toIso8601String(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }
}

