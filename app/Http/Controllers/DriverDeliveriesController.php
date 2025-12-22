<?php

namespace App\Http\Controllers;

use App\Driver;
use App\Delivery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Controller pour la gestion des livraisons côté livreur (interface web)
 */
class DriverDeliveriesController extends Controller
{
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
        
        // Chercher le driver lié à cet utilisateur
        // Note: Adapte selon ta structure (email, phone, ou autre)
        $driver = Driver::where('email', $user->email)
            ->orWhere('phone', $user->phone)
            ->first();
        
        // Si pas de driver trouvé, essayer par nom
        if (!$driver && $user->type === 'driver') {
            $driver = Driver::where('name', $user->name)->first();
        }
        
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
        
        // Si requête JSON (AJAX), retourner JSON
        if ($request->has('json')) {
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
        
        return view('driver.deliveries', compact('deliveries', 'driver'));
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
        ]);
        
        $user = auth()->user();
        
        if (!$user) {
            return redirect()->back()->with('alert', [
                'type' => 'danger',
                'message' => 'Non authentifié'
            ]);
        }
        
        // Chercher le driver
        $driver = Driver::where('email', $user->email)
            ->orWhere('phone', $user->phone)
            ->first();
        
        if (!$driver && $user->type === 'driver') {
            $driver = Driver::where('name', $user->name)->first();
        }
        
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
            $deliveryService = new \App\Services\DeliveryService();
            $deliveryService->updateStatus($delivery, $request->input('status'));
            
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
}

