<?php

namespace App\Http\Controllers\api\Transport;

use App\Driver;
use App\Http\Controllers\Controller;
use App\Domain\Transport\Events\TransportMissionPresenceUpdated;
use App\Domain\Transport\Models\TransportBooking;
use App\Domain\Transport\Enums\TransportStatus;
use App\Support\Auth\AuthenticatedDriverResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DriverTransportController extends Controller
{
    public function __construct(
        protected \App\Domain\Transport\Services\TransportService $transportService,
        protected AuthenticatedDriverResolver $authenticatedDriverResolver
    )
    {
    }

    public function nearby(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'radius' => 'sometimes|numeric',
        ]);

        $lat = (float) $request->lat;
        $lng = (float) $request->lng;
        $radius = (float) ($request->radius ?? 5);

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
        $driver = $this->resolveDriverFromAuthUser();

        if (! $driver) {
            return response()->json(['error' => 'Aucun profil chauffeur associé à votre session'], 403);
        }

        if ($driver->status !== 'online') {
            return response()->json(['error' => 'Vous devez être en ligne pour accepter des courses'], 403);
        }

        $vehicle = $driver->activeTransportVehicle;
        if (! $vehicle) {
            return response()->json(['error' => 'Vous devez avoir un véhicule actif pour accepter des courses'], 403);
        }

        if ($vehicle->status !== 'active') {
            return response()->json(['error' => 'Votre véhicule n\'est pas encore approuvé par l\'administration'], 403);
        }

        try {
            $booking = DB::transaction(function () use ($id, $driver, $vehicle) {
                $booking = TransportBooking::query()
                    ->where('uuid', $id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($booking->driver_id || $booking->status !== TransportStatus::REQUESTED) {
                    return null;
                }

                $booking->update([
                    'driver_id' => $driver->id,
                    'vehicle_id' => $vehicle->id,
                ]);

                return $this->transportService->updateStatus($booking->fresh(), TransportStatus::ASSIGNED);
            });
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $exception) {
            return response()->json(['error' => 'Course introuvable'], 404);
        }

        if (! $booking) {
            return response()->json(['error' => 'Cette demande a déjà été prise par un autre chauffeur'], 409);
        }

        return response()->json([
            'message' => 'Demande acceptée',
            'booking' => $booking->fresh(['driver', 'vehicle']),
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $driver = $this->resolveDriverFromAuthUser();
        $booking = TransportBooking::where('uuid', $id)->firstOrFail();

        if (! $driver || (int) $booking->driver_id !== (int) $driver->id) {
            return response()->json(['error' => 'Vous ne pouvez pas modifier cette course'], 403);
        }

        $request->validate([
            'status' => [
                'required',
                'string',
                Rule::in(['driver_arriving', 'picked_up', 'in_progress', 'completed', 'cancelled', 'closed']),
            ],
        ]);

        $newStatus = TransportStatus::from($request->status);
        $this->transportService->updateStatus($booking, $newStatus);

        return response()->json(['message' => 'Statut mis à jour']);
    }

    public function updateLocation(Request $request, $id)
    {
        $driver = $this->resolveDriverFromAuthUser();
        $booking = TransportBooking::where('uuid', $id)->firstOrFail();

        if (! $driver || (int) $booking->driver_id !== (int) $driver->id) {
            return response()->json(['error' => 'Vous ne pouvez pas mettre à jour cette course'], 403);
        }

        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'speed' => 'sometimes|numeric',
        ]);

        $driver->update([
            'latitude' => $request->lat,
            'longitude' => $request->lng,
        ]);

        $booking->trackingPoints()->create([
            'lat' => $request->lat,
            'lng' => $request->lng,
            'speed' => $request->speed,
            'recorded_at' => now(),
        ]);

        event(new \App\Domain\Transport\Events\TransportTrackingUpdated($booking, $request->lat, $request->lng, $request->speed));
        event(new TransportMissionPresenceUpdated($booking->fresh(['driver', 'vehicle', 'trackingPoints'])));

        return response()->json(['message' => 'Position mise à jour']);
    }

    protected function resolveDriverFromAuthUser(): ?Driver
    {
        return $this->authenticatedDriverResolver->current();
    }
}
