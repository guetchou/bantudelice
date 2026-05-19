<?php

namespace App\Http\Controllers\Api\V1\Colis;

use App\Http\Controllers\Controller;
use App\Http\Requests\Colis\CreateShipmentRequest;
use App\Http\Requests\Colis\ProcessPaymentRequest;
use App\Domain\Colis\Models\Shipment;
use App\Domain\Colis\Services\ShipmentAssignmentService;
use App\Domain\Colis\Services\ShipmentPricingService;
use App\Domain\Colis\Services\ShipmentPaymentService;
use App\Domain\Colis\Services\ShipmentStateMachine;
use App\Domain\Colis\Enums\ShipmentStatus;
use App\Payment;
use App\Services\PaymentExperienceService;
use App\Services\PaymentReconciliationService;
use App\Services\AddressQualityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ShipmentController extends Controller
{
    public function __construct(
        protected PaymentExperienceService $paymentExperienceService,
        protected AddressQualityService $addressQualityService
    )
    {
        $this->middleware('auth:api');
    }

    public function index(Request $request): JsonResponse
    {
        $shipments = Shipment::where('customer_id', $request->user()->id)
            ->with(['addresses', 'events'])
            ->latest()
            ->paginate($request->get('per_page', 15));

        return response()->json($shipments);
    }

    public function store(
        CreateShipmentRequest $request,
        ShipmentPricingService $pricingService,
        ShipmentAssignmentService $assignmentService
    ): JsonResponse
    {
        return DB::transaction(function () use ($request, $pricingService, $assignmentService) {
            $data = $request->validated();

            $pickupLat = (float) data_get($data, 'pickup_address.lat');
            $pickupLng = (float) data_get($data, 'pickup_address.lng');
            $dropoffLat = (float) data_get($data, 'dropoff_address.lat');
            $dropoffLng = (float) data_get($data, 'dropoff_address.lng');
            $pickupAddressQuality = $this->addressQualityService->forShipmentAddress($data['pickup_address'], 'pickup');
            $dropoffAddressQuality = $this->addressQualityService->forShipmentAddress($data['dropoff_address'], 'dropoff');
            $data['distance_km'] = round($this->distanceKm($pickupLat, $pickupLng, $dropoffLat, $dropoffLng), 2);

            if (($data['service_level'] ?? 'standard') === 'express'
                && in_array($pickupAddressQuality['level'], ['district', 'area', 'blind'], true)) {
                return response()->json([
                    'message' => 'Adresse de ramassage trop imprecise pour un envoi express.',
                    'address_quality' => [
                        'pickup' => $pickupAddressQuality,
                        'dropoff' => $dropoffAddressQuality,
                    ],
                ], 422);
            }

            if (
                $this->addressQualityService->needsExplicitConfirmation($pickupAddressQuality)
                && ! (bool) ($data['pickup_address_confirmed'] ?? false)
            ) {
                return response()->json([
                    'message' => 'Confirmez precisement le point de ramassage avant de continuer.',
                    'address_quality' => [
                        'pickup' => $pickupAddressQuality,
                        'dropoff' => $dropoffAddressQuality,
                    ],
                    'errors' => [
                        'pickup_address_confirmed' => ['Confirmation precise du ramassage requise'],
                    ],
                ], 422);
            }

            if (
                $this->addressQualityService->needsExplicitConfirmation($dropoffAddressQuality)
                && ! (bool) ($data['dropoff_address_confirmed'] ?? false)
            ) {
                return response()->json([
                    'message' => 'Confirmez precisement le point de livraison avant de continuer.',
                    'address_quality' => [
                        'pickup' => $pickupAddressQuality,
                        'dropoff' => $dropoffAddressQuality,
                    ],
                    'errors' => [
                        'dropoff_address_confirmed' => ['Confirmation precise de la livraison requise'],
                    ],
                ], 422);
            }

            // Calculer le prix
            $pricing = $pricingService->calculate($data);

            $availableCouriers = $assignmentService->countAvailableCouriersNear($pickupLat, $pickupLng);
            $serviceable = $availableCouriers > 0;
            $bestCourier = $assignmentService->bestAvailableCourierNear($pickupLat, $pickupLng);
            $assignmentStatus = 'queued_for_dispatch';
            $capacityState = $this->capacityState($availableCouriers);
            $dispatchWindow = $this->dispatchWindowMinutes($data['distance_km'], (string) ($data['service_level'] ?? 'standard'));
            $retryAfterMinutes = $serviceable ? 0 : ($data['service_level'] === 'express' ? 8 : 15);

            if (($data['service_level'] ?? 'standard') === 'express' && ! $serviceable) {
                return response()->json([
                    'message' => 'Aucun coursier disponible autour du point de ramassage pour le moment.',
                    'available_couriers_count' => 0,
                    'serviceable' => false,
                    'capacity_state' => $capacityState,
                    'dispatch_retry_after_minutes' => $retryAfterMinutes,
                ], 409);
            }
            
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
                'distance_km' => $data['distance_km'],
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

            $assignedCourier = $serviceable ? $assignmentService->autoAssign($shipment) : null;
            if ($assignedCourier) {
                $assignmentStatus = 'assigned_now';
            } elseif (($data['service_level'] ?? 'standard') === 'express') {
                $assignmentStatus = 'dispatch_unavailable';
            }
            $shipment = $shipment->fresh()->load('addresses');

            return response()->json($shipment->toArray() + [
                'address_quality' => [
                    'pickup' => $pickupAddressQuality,
                    'dropoff' => $dropoffAddressQuality,
                ],
                'serviceability' => [
                    'available_couriers_count' => $availableCouriers,
                    'serviceable' => $serviceable,
                    'capacity_state' => $capacityState,
                    'assignment_status' => $assignmentStatus,
                    'dispatch_mode' => $assignedCourier ? 'immediate' : 'queued',
                    'dispatch_retry_after_minutes' => $retryAfterMinutes,
                    'dispatch_window_minutes' => $dispatchWindow,
                    'auto_assigned_courier_id' => $assignedCourier?->id,
                    'best_courier' => $this->courierPayload($bestCourier, $pickupLat, $pickupLng),
                    'assigned_courier' => $this->courierPayload($assignedCourier, $pickupLat, $pickupLng),
                    'pickup_window_minutes' => $this->pickupWindowMinutes($bestCourier, $pickupLat, $pickupLng),
                    'delivery_window_minutes' => $this->deliveryWindowMinutes(
                        $data['distance_km'],
                        (string) ($data['service_level'] ?? 'standard')
                    ),
                ],
            ], 201);
        });
    }

    public function show(Shipment $shipment): JsonResponse
    {
        $this->authorize('view', $shipment);
        return response()->json($shipment->load(['addresses', 'events', 'proofs']));
    }

    public function cancel(Shipment $shipment, ShipmentStateMachine $stateMachine): JsonResponse
    {
        $this->authorize('update', $shipment);
        
        if (!in_array($shipment->status, [ShipmentStatus::CREATED, ShipmentStatus::PRICED, ShipmentStatus::PAID])) {
            return response()->json(['message' => 'Annulation impossible à ce stade.'], 422);
        }

        $stateMachine->transitionTo($shipment, ShipmentStatus::CANCELED, [
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

        $paymentInfo = $paymentService->initiatePayment($shipment, $provider, [
            'phone' => $request->input('phone'),
        ]);
        return response()->json($paymentInfo);
    }

    public function paymentStatus(Shipment $shipment): JsonResponse
    {
        $this->authorize('view', $shipment);

        $payment = Payment::where('shipment_id', $shipment->id)
            ->latest('id')
            ->first();

        $reconciliation = null;

        if ($payment && $payment->status === 'PENDING') {
            try {
                $reconciliation = app(PaymentReconciliationService::class)->reconcile($payment);
            } catch (\Throwable $e) {
                Log::warning('Erreur de réconciliation paiement colis pendant polling', [
                    'shipment_id' => $shipment->id,
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $shipment = $shipment->fresh();
            $payment = $payment->fresh();
        }

        return response()->json([
            'payment_status' => $shipment->payment_status,
            'total_price' => $shipment->total_price,
            'currency' => $shipment->currency,
            'payment' => $payment ? [
                'id' => $payment->id,
                'status' => $payment->status,
                'provider' => $payment->provider,
                'provider_reference' => $payment->provider_reference,
                'updated_at' => optional($payment->updated_at)->toIso8601String(),
            ] : null,
            'payment_experience' => $this->paymentExperienceService->describe($payment),
            'reconciliation' => $reconciliation,
        ]);
    }

    protected function generateTrackingNumber(): string
    {
        return 'BD-CG-' . now()->format('Ym') . '-' . strtoupper(Str::random(5));
    }

    protected function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLng / 2) * sin($dLng / 2);

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    protected function courierPayload($courier, float $pickupLat, float $pickupLng): ?array
    {
        if (! $courier) {
            return null;
        }

        $distanceKm = $this->distanceKm(
            (float) $courier->latitude,
            (float) $courier->longitude,
            $pickupLat,
            $pickupLng
        );

        return [
            'id' => $courier->id,
            'name' => $courier->name,
            'phone' => $courier->phone,
            'distance_to_pickup_km' => round($distanceKm, 2),
            'pickup_eta_minutes' => (int) max(2, ceil(($distanceKm / 28) * 60)),
        ];
    }

    protected function pickupWindowMinutes($courier, float $pickupLat, float $pickupLng): ?array
    {
        if (! $courier) {
            return null;
        }

        $distanceKm = $this->distanceKm(
            (float) $courier->latitude,
            (float) $courier->longitude,
            $pickupLat,
            $pickupLng
        );

        $min = (int) max(5, ceil(($distanceKm / 28) * 60));
        $max = $min + max(6, (int) ceil($min * 0.35));

        return [
            'min' => $min,
            'max' => $max,
        ];
    }

    protected function deliveryWindowMinutes(float $distanceKm, string $serviceLevel): array
    {
        $speedKmh = $serviceLevel === 'express' ? 26 : 20;
        $baseBuffer = $serviceLevel === 'express' ? 25 : 90;
        $min = (int) max($baseBuffer, ceil(($distanceKm / $speedKmh) * 60) + $baseBuffer);
        $max = $min + ($serviceLevel === 'express' ? 30 : 120);

        return [
            'min' => $min,
            'max' => $max,
        ];
    }

    protected function dispatchWindowMinutes(float $distanceKm, string $serviceLevel): array
    {
        $base = $serviceLevel === 'express' ? 8 : 20;
        $variable = (int) ceil($distanceKm * ($serviceLevel === 'express' ? 0.8 : 1.25));
        $min = max($base, $base + $variable);
        $max = $min + ($serviceLevel === 'express' ? 10 : 20);

        return [
            'min' => $min,
            'max' => $max,
        ];
    }

    protected function capacityState(int $availableCouriers): string
    {
        return match (true) {
            $availableCouriers < 1 => 'unavailable',
            $availableCouriers === 1 => 'tight',
            $availableCouriers <= 3 => 'steady',
            default => 'healthy',
        };
    }
}
