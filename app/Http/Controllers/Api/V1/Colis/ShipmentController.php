<?php

namespace App\Http\Controllers\Api\V1\Colis;

use App\Http\Controllers\Controller;
use App\Http\Requests\Colis\CreateShipmentRequest;
use App\Http\Requests\Colis\ProcessPaymentRequest;
use App\Domain\Colis\Models\Shipment;
use App\Domain\Colis\Services\ShipmentPricingService;
use App\Domain\Colis\Services\ShipmentPaymentService;
use App\Domain\Colis\Enums\ShipmentStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ShipmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request): JsonResponse
    {
        $shipments = Shipment::where('customer_id', $request->user()->id)
            ->with(['addresses', 'events'])
            ->latest()
            ->paginate($request->get('per_page', 15));

        return response()->json($shipments);
    }

    public function store(CreateShipmentRequest $request, ShipmentPricingService $pricingService): JsonResponse
    {
        return DB::transaction(function () use ($request, $pricingService) {
            $data = $request->validated();
            
            // Calculer le prix
            $pricing = $pricingService->calculate($data);
            
            // Créer le colis
            $shipment = Shipment::create([
                'uuid' => (string) Str::uuid(),
                'tracking_number' => $this->generateTrackingNumber(),
                'customer_id' => $request->user()->id,
                'status' => ShipmentStatus::CREATED,
                'service_level' => $data['service_level'],
                'pickup_type' => $data['pickup_type'] ?? 'door',
                'dropoff_type' => $data['dropoff_type'] ?? 'door',
                'declared_value' => $data['declared_value'] ?? 0,
                'cod_amount' => $data['cod_amount'] ?? 0,
                'weight_kg' => $data['weight_kg'],
                'price_breakdown' => $pricing['price_breakdown'],
                'total_price' => $pricing['total_price'],
                'payment_status' => 'unpaid',
            ]);

            // Ajouter les adresses
            $shipment->addresses()->createMany([
                array_merge($data['pickup_address'], ['type' => 'pickup']),
                array_merge($data['dropoff_address'], ['type' => 'dropoff']),
            ]);

            // Créer l'événement initial
            $shipment->events()->create([
                'status' => ShipmentStatus::CREATED,
                'actor_type' => 'customer',
                'actor_id' => $request->user()->id,
                'notes' => 'Colis créé par le client.',
            ]);

            return response()->json($shipment->load('addresses'), 201);
        });
    }

    public function show(Shipment $shipment): JsonResponse
    {
        $this->authorize('view', $shipment);
        return response()->json($shipment->load(['addresses', 'events', 'proofs']));
    }

    public function cancel(Shipment $shipment): JsonResponse
    {
        $this->authorize('update', $shipment);
        
        if (!in_array($shipment->status, [ShipmentStatus::CREATED, ShipmentStatus::PRICED, ShipmentStatus::PAID])) {
            return response()->json(['message' => 'Annulation impossible à ce stade.'], 422);
        }

        $shipment->update(['status' => ShipmentStatus::CANCELED]);
        $shipment->events()->create([
            'status' => ShipmentStatus::CANCELED,
            'actor_type' => 'customer',
            'actor_id' => auth()->id(),
            'notes' => 'Annulé par le client.',
        ]);

        return response()->json(['message' => 'Envoi annulé avec succès.']);
    }

    public function processPayment(
        ProcessPaymentRequest $request, 
        Shipment $shipment, 
        ShipmentPaymentService $paymentService
    ): JsonResponse {
        $this->authorize('update', $shipment);

        if ($shipment->payment_status === 'paid') {
            return response()->json(['message' => 'Ce colis est déjà payé.'], 422);
        }

        $provider = $request->provider;

        if ($provider === 'cod') {
            $paymentService->handleCOD($shipment);
            return response()->json([
                'message' => 'Option Paiement à la livraison enregistrée.',
                'status' => 'cod_pending'
            ]);
        }

        $paymentInfo = $paymentService->initiatePayment($shipment, $provider);
        return response()->json($paymentInfo);
    }

    public function paymentStatus(Shipment $shipment): JsonResponse
    {
        $this->authorize('view', $shipment);
        return response()->json([
            'payment_status' => $shipment->payment_status,
            'total_price' => $shipment->total_price,
            'currency' => $shipment->currency
        ]);
    }

    protected function generateTrackingNumber(): string
    {
        return 'BD-CG-' . now()->format('Ym') . '-' . strtoupper(Str::random(5));
    }
}

