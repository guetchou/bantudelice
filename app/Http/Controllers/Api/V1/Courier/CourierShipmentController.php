<?php

namespace App\Http\Controllers\Api\V1\Courier;

use App\Http\Controllers\Controller;
use App\Http\Requests\Colis\CourierEventRequest;
use App\Http\Requests\Colis\DeliverRequest;
use App\Domain\Colis\Models\Shipment;
use App\Domain\Colis\Services\ShipmentStateMachine;
use App\Domain\Colis\Enums\ShipmentStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CourierShipmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function assigned(Request $request): JsonResponse
    {
        $shipments = Shipment::where('assigned_courier_id', $request->user()->id)
            ->whereNotIn('status', [ShipmentStatus::DELIVERED, ShipmentStatus::CANCELED, ShipmentStatus::RETURNED])
            ->with(['addresses', 'events'])
            ->get();

        return response()->json($shipments);
    }

    public function pushEvent(CourierEventRequest $request, Shipment $shipment, ShipmentStateMachine $stateMachine): JsonResponse
    {
        $this->authorize('updateAsCourier', $shipment);

        $stateMachine->transitionTo($shipment, ShipmentStatus::from($request->status), [
            'actor_type' => 'courier',
            'actor_id' => auth()->id(),
            'notes' => $request->notes,
            'meta' => $request->meta,
        ]);

        return response()->json(['message' => 'Statut mis à jour.']);
    }

    public function uploadProof(Request $request, Shipment $shipment, \App\Domain\Colis\Services\ShipmentProofService $proofService): JsonResponse
    {
        $this->authorize('updateAsCourier', $shipment);

        $request->validate([
            'type' => 'required|in:photo,signature',
            'file' => 'required|image|max:5120', // 5MB max
        ]);

        $proof = $proofService->storeProof($shipment, $request->file('file'), $request->type);

        return response()->json([
            'message' => 'Preuve téléchargée avec succès.',
            'proof_id' => $proof->id
        ]);
    }

    public function deliver(DeliverRequest $request, Shipment $shipment, ShipmentStateMachine $stateMachine, \App\Domain\Colis\Services\ShipmentProofService $proofService): JsonResponse
    {
        $this->authorize('updateAsCourier', $shipment);

        if (!$proofService->verifyOTP($shipment, $request->otp)) {
            return response()->json(['message' => 'Code OTP invalide.'], 422);
        }
        
        $stateMachine->transitionTo($shipment, ShipmentStatus::DELIVERED, [
            'actor_type' => 'courier',
            'actor_id' => auth()->id(),
            'notes' => 'Livré avec succès après validation OTP.',
            'meta' => ['otp_verified' => true],
        ]);

        return response()->json(['message' => 'Colis marqué comme livré.']);
    }
}

