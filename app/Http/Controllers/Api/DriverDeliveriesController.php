<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Delivery;
use App\Order;
use App\Services\DeliveryService;
use App\Support\Auth\AuthenticatedDriverResolver;
use Illuminate\Http\Request;

class DriverDeliveriesController extends Controller
{
    public function __construct(
        protected DeliveryService $deliveryService,
        protected AuthenticatedDriverResolver $authenticatedDriverResolver
    ) {
        $this->middleware('auth:driver_api');
    }

    /**
     * Liste des livraisons actives pour le livreur connecté.
     *
     * GET /api/driver/deliveries
     */
    public function index(Request $request)
    {
        $driver = $this->authenticatedDriverResolver->current();

        if (! $driver) {
            return response()->json([
                'status' => false,
                'message' => 'Non authentifié',
            ], 401);
        }

        $deliveries = $this->deliveryService->getActiveDeliveriesForDriver($driver);

        $data = $deliveries->map(function ($delivery) {
            $order = $delivery->order;

            return [
                'id' => $delivery->id,
                'order_id' => $delivery->order_id,
                'order_no' => $order->order_no ?? null,
                'status' => $delivery->status,
                'business_status' => $order && method_exists($order, 'resolveEffectiveBusinessStatus')
                    ? $order->resolveEffectiveBusinessStatus()
                    : null,
                'payment_method' => $order->payment_method ?? null,
                'payment_status' => $order->payment_status ?? null,
                'cash_collection_status' => $order->cash_collection_status ?? null,
                'cash_collection' => $this->cashCollectionPayload($order),
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
                'delivery_otp_required' => method_exists($delivery, 'requiresOtp') ? $delivery->requiresOtp() : false,
                'incident_status' => $delivery->incident_status,
                'incident_reason' => $delivery->incident_reason,
                'incident_notes' => $delivery->incident_notes,
                'support_status' => $delivery->support_status,
                'failed_attempts' => (int) ($delivery->failed_attempts ?? 0),
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

    /**
     * Mettre à jour le statut d'une livraison.
     *
     * PATCH /api/driver/deliveries/{delivery}/status
     */
    public function updateStatus(Request $request, $delivery)
    {
        $request->validate([
            'status' => 'required|in:PICKED_UP,ON_THE_WAY,DELIVERED,CANCELLED',
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
            'cash_collection_outcome' => 'nullable|string|in:collected,collection_failed',
        ]);

        $driver = $this->authenticatedDriverResolver->current();

        if (! $driver) {
            return response()->json([
                'status' => false,
                'message' => 'Non authentifié',
            ], 401);
        }

        $delivery = $this->resolveDriverDelivery($delivery, $driver->id);

        if (! $delivery) {
            return response()->json([
                'status' => false,
                'message' => 'Livraison introuvable',
            ], 404);
        }

        try {
            $pickupProofPath = $request->hasFile('pickup_proof')
                ? $this->deliveryService->storeProofFile($request->file('pickup_proof'), 'pickup')
                : null;
            $deliveryProofPath = $request->hasFile('delivery_proof')
                ? $this->deliveryService->storeProofFile($request->file('delivery_proof'), 'delivery')
                : null;
            $updatedDelivery = $this->deliveryService->updateStatus($delivery, $request->input('status'), [
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
                'cash_collection_outcome' => $request->input('cash_collection_outcome'),
            ]);

            $updatedDelivery->loadMissing('order');

            return response()->json([
                'status' => true,
                'message' => 'Statut mis à jour avec succès',
                'data' => [
                    'id' => $updatedDelivery->id,
                    'status' => $updatedDelivery->status,
                    'picked_up_at' => $updatedDelivery->picked_up_at?->toIso8601String(),
                    'delivered_at' => $updatedDelivery->delivered_at?->toIso8601String(),
                    'customer_confirmed_at' => $updatedDelivery->customer_confirmed_at?->toIso8601String(),
                    'delivery_confirmation_method' => $updatedDelivery->delivery_confirmation_method,
                    'payment_method' => $updatedDelivery->order->payment_method ?? null,
                    'cash_collection_status' => $updatedDelivery->order->cash_collection_status ?? null,
                    'cash_collection' => $this->cashCollectionPayload($updatedDelivery->order),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function reportIncident(Request $request, $delivery)
    {
        $request->validate([
            'reason' => 'required|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        $driver = $this->authenticatedDriverResolver->current();

        if (! $driver) {
            return response()->json([
                'status' => false,
                'message' => 'Non authentifié',
            ], 401);
        }

        $delivery = $this->resolveDriverDelivery($delivery, $driver->id);

        if (! $delivery) {
            return response()->json([
                'status' => false,
                'message' => 'Livraison introuvable',
            ], 404);
        }

        try {
            $updatedDelivery = $this->deliveryService->reportIncident($delivery, $request->input('reason'), [
                'actor_type' => 'driver',
                'actor_id' => $driver->id,
                'notes' => $request->input('notes'),
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Incident signalé avec succès',
                'data' => [
                    'id' => $updatedDelivery->id,
                    'incident_status' => $updatedDelivery->incident_status,
                    'incident_reason' => $updatedDelivery->incident_reason,
                    'support_status' => $updatedDelivery->support_status,
                    'failed_attempts' => (int) ($updatedDelivery->failed_attempts ?? 0),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    private function cashCollectionPayload(?Order $order): array
    {
        if (! $order || strtolower((string) $order->payment_method) !== 'cash') {
            return [
                'required' => false,
                'status' => null,
                'amount' => 0,
                'badge' => null,
                'attention_required' => false,
            ];
        }

        $status = $order->cash_collection_status ?: 'pending_collection';
        $badge = match ($status) {
            'collected' => 'Espèces collectées',
            'collection_failed' => 'Collecte échouée',
            'disputed' => 'Collecte contestée',
            default => 'Espèces à collecter',
        };

        return [
            'required' => $status !== 'collected',
            'status' => $status,
            'amount' => (float) ($order->total ?? 0),
            'badge' => $badge,
            'attention_required' => in_array($status, ['pending_collection', 'collection_failed', 'disputed'], true),
        ];
    }

    private function resolveDriverDelivery($delivery, int $driverId): ?Delivery
    {
        if ($delivery instanceof Delivery) {
            return $delivery->driver_id === $driverId ? $delivery : null;
        }

        return Delivery::where('id', $delivery)
            ->where('driver_id', $driverId)
            ->first();
    }
}
