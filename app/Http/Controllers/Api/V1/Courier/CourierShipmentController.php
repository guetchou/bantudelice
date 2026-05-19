<?php

namespace App\Http\Controllers\Api\V1\Courier;

use App\Http\Controllers\Controller;
use App\Http\Requests\Colis\CourierEventRequest;
use App\Http\Requests\Colis\DeliverRequest;
use App\Domain\Colis\Models\Shipment;
use App\Domain\Colis\Services\ShipmentStateMachine;
use App\Domain\Colis\Services\ShipmentProofService;
use App\Domain\Colis\Services\ShipmentPaymentService;
use App\Domain\Colis\Enums\ShipmentStatus;
use App\Support\Auth\AuthenticatedDriverResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourierShipmentController extends Controller
{
    public function __construct(
        protected AuthenticatedDriverResolver $authenticatedDriverResolver
    )
    {
        $this->middleware('auth:driver_api');
    }

    public function assigned(Request $request): JsonResponse
    {
        $courier = $this->authenticatedDriverResolver->current();

        if (! $courier) {
            return response()->json(['message' => 'Non authentifié'], 401);
        }

        $shipments = Shipment::where('assigned_courier_id', $courier->id)
            ->whereNotIn('status', [ShipmentStatus::DELIVERED, ShipmentStatus::CANCELED, ShipmentStatus::RETURNED])
            ->with(['addresses', 'events'])
            ->get();

        return response()->json($shipments);
    }

    public function pushEvent(CourierEventRequest $request, Shipment $shipment, ShipmentStateMachine $stateMachine): JsonResponse
    {
        $courier = $this->authenticatedDriverResolver->current();
        if (! $courier) {
            return response()->json(['message' => 'Non authentifié'], 401);
        }

        $this->authorize('updateAsCourier', $shipment);

        $stateMachine->transitionTo($shipment, ShipmentStatus::from($request->status), [
            'actor_type' => 'courier',
            'actor_id' => $courier->id,
            'notes' => $request->notes,
            'meta' => $request->meta,
        ]);

        return response()->json(['message' => 'Statut mis à jour.']);
    }

    public function uploadProof(Request $request, Shipment $shipment, ShipmentProofService $proofService): JsonResponse
    {
        $this->authorize('updateAsCourier', $shipment);

        $request->validate([
            'type' => 'required|in:photo,signature',
            'file' => 'required|image|max:5120',
        ]);

        $proof = $proofService->storeProof($shipment, $request->file('file'), $request->type);

        return response()->json([
            'message' => 'Preuve téléchargée avec succès.',
            'proof_id' => $proof->id,
        ]);
    }

    public function deliver(
        DeliverRequest $request,
        Shipment $shipment,
        ShipmentStateMachine $stateMachine,
        ShipmentProofService $proofService,
        ShipmentPaymentService $paymentService
    ): JsonResponse {
        $courier = $this->authenticatedDriverResolver->current();
        if (! $courier) {
            return response()->json(['message' => 'Non authentifié'], 401);
        }

        $this->authorize('updateAsCourier', $shipment);

        $otpValid = $proofService->verifyOTP($shipment, $request->otp);
        $proof = null;

        if ($request->hasFile('file')) {
            $proof = $proofService->storeProof($shipment, $request->file('file'), $request->type ?: 'photo');
        }

        if (!$otpValid && !$proof) {
            return response()->json(['message' => 'Un OTP valide ou une preuve de remise est obligatoire.'], 422);
        }

        $stateMachine->transitionTo($shipment, ShipmentStatus::DELIVERED, [
            'actor_type' => 'courier',
            'actor_id' => $courier->id,
            'notes' => $request->notes ?: 'Colis livré avec succès.',
            'meta' => [
                'otp_verified' => $otpValid,
                'proof_id' => $proof?->id,
                'delivery_latitude' => $request->latitude,
                'delivery_longitude' => $request->longitude,
            ],
        ]);

        if ($shipment->payment_status === 'cod_pending' && (float) $shipment->cod_amount > 0) {
            enqueue_job('colis', 'finalize_shipment_cod_collection', [
                'shipment_id' => $shipment->id,
                'context' => [
                    'courier_id' => $courier->id,
                ],
            ]);
        }

        return response()->json([
            'message' => 'Colis marqué comme livré.',
            'otp_verified' => $otpValid,
            'proof_id' => $proof?->id,
        ]);
    }
}
