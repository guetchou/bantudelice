<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Domain\Colis\Models\Shipment;
use App\Domain\Colis\Services\ShipmentStateMachine;
use App\Domain\Colis\Enums\ShipmentStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminShipmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request): JsonResponse
    {
        $query = Shipment::with(['customer', 'courier', 'addresses']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('tracking_number')) {
            $query->where('tracking_number', 'like', '%' . $request->tracking_number . '%');
        }

        return response()->json($query->latest()->paginate(20));
    }

    public function assign(Request $request, Shipment $shipment, \App\Domain\Colis\Services\ShipmentAssignmentService $assignmentService): JsonResponse
    {
        $request->validate([
            'courier_id' => 'required|exists:drivers,id',
        ]);

        $assignmentService->assignToCourier($shipment, $request->courier_id);

        return response()->json(['message' => 'Coursier assigné avec succès.']);
    }

    public function autoAssign(Shipment $shipment, \App\Domain\Colis\Services\ShipmentAssignmentService $assignmentService): JsonResponse
    {
        $courier = $assignmentService->autoAssign($shipment);

        if ($courier) {
            return response()->json(['message' => "Colis assigné automatiquement à {$courier->name}."]);
        }

        return response()->json(['message' => "Aucun coursier disponible pour l'assignation automatique."], 404);
    }

    public function overrideStatus(Request $request, Shipment $shipment, ShipmentStateMachine $stateMachine): JsonResponse
    {
        $request->validate([
            'status' => 'required|string',
            'reason' => 'required|string',
        ]);

        // Override direct sans passer par les transitions autorisées (avec audit)
        $newStatus = ShipmentStatus::from($request->status);
        $shipment->update(['status' => $newStatus]);

        $shipment->events()->create([
            'status' => $newStatus,
            'actor_type' => 'admin',
            'actor_id' => auth()->id(),
            'notes' => "OVERRIDE ADMIN : " . $request->reason,
        ]);

        return response()->json(['message' => 'Statut forcé avec succès. Audit log créé.']);
    }
}

