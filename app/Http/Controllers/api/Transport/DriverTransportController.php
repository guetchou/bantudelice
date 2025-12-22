<?php

namespace App\Http\Controllers\api\Transport;

use App\Http\Controllers\Controller;
use App\Domain\Transport\Models\TransportBooking;
use App\Domain\Transport\Enums\TransportStatus;
use Illuminate\Http\Request;

class DriverTransportController extends Controller
{
    protected $transportService;

    public function __construct(\App\Domain\Transport\Services\TransportService $transportService)
    {
        $this->transportService = $transportService;
    }

    public function nearby(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'radius' => 'sometimes|numeric'
        ]);

        $lat = (float) $request->lat;
        $lng = (float) $request->lng;
        $radius = (float) ($request->radius ?? 5); // km

        // Bounding box calculation in PHP
        $latDelta = $radius / 111;
        $lngDelta = $radius / (111 * cos(deg2rad($lat)));

        $requests = TransportBooking::where('status', TransportStatus::REQUESTED)
            ->whereNull('driver_id')
            ->whereBetween('pickup_lat', [$lat - $latDelta, $lat + $latDelta])
            ->whereBetween('pickup_lng', [$lng - $lngDelta, $lng + $lngDelta])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($requests);
    }

    public function accept($id)
    {
        $driver = auth()->user();
        $booking = TransportBooking::where('uuid', $id)->firstOrFail();
        
        if ($booking->driver_id) {
            return response()->json(['error' => 'Cette demande a déjà été acceptée'], 400);
        }

        // Check if driver is online
        if ($driver->status !== 'online') {
            return response()->json(['error' => 'Vous devez être en ligne pour accepter des courses'], 403);
        }

        // Check if driver has an active and approved vehicle
        $vehicle = $driver->activeTransportVehicle;
        if (!$vehicle) {
            return response()->json(['error' => 'Vous devez avoir un véhicule actif pour accepter des courses'], 403);
        }

        if ($vehicle->status !== 'active') {
            return response()->json(['error' => 'Votre véhicule n\'est pas encore approuvé par l\'administration'], 403);
        }

        $booking->update([
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id
        ]);
        
        $this->transportService->updateStatus($booking, TransportStatus::ASSIGNED);

        return response()->json([
            'message' => 'Demande acceptée',
            'booking' => $booking
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $booking = TransportBooking::where('uuid', $id)->firstOrFail();
        
        $request->validate([
            'status' => 'required|string'
        ]);

        $newStatus = TransportStatus::from($request->status);
        $this->transportService->updateStatus($booking, $newStatus);

        return response()->json(['message' => 'Statut mis à jour']);
    }

    public function updateLocation(Request $request, $id)
    {
        $booking = TransportBooking::where('uuid', $id)->firstOrFail();
        
        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'speed' => 'sometimes|numeric',
        ]);

        $booking->trackingPoints()->create([
            'lat' => $request->lat,
            'lng' => $request->lng,
            'speed' => $request->speed,
            'recorded_at' => now()
        ]);

        // Broadcast for real-time tracking
        event(new \App\Domain\Transport\Events\TransportTrackingUpdated($booking, $request->lat, $request->lng, $request->speed));

        return response()->json(['message' => 'Position mise à jour']);
    }
}

