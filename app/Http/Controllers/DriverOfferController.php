<?php

namespace App\Http\Controllers;

use App\Delivery;
use App\DeliveryOffer;
use App\Services\DeliveryService;
use App\Support\Auth\AuthenticatedDriverResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DriverOfferController extends Controller
{
    public function __construct(
        protected DeliveryService $deliveryService,
        protected AuthenticatedDriverResolver $driverResolver
    ) {
        $this->middleware('auth');
    }

    public function accept(Request $request, int $deliveryId)
    {
        $driver = $this->driverResolver->current();
        if (! $driver) {
            return $this->respond($request, false, 'Compte livreur approuvé introuvable.', 403);
        }

        try {
            $accepted = DB::transaction(function () use ($deliveryId, $driver): bool {
                $delivery = Delivery::query()->lockForUpdate()->find($deliveryId);
                if (! $delivery || $delivery->status !== 'PENDING') {
                    return false;
                }

                $offer = DeliveryOffer::query()
                    ->where('delivery_id', $deliveryId)
                    ->where('driver_id', $driver->id)
                    ->where('status', 'pending')
                    ->lockForUpdate()
                    ->first();

                if (! $offer || ! $offer->expires_at || $offer->expires_at->isPast()) {
                    if ($offer) {
                        $offer->update([
                            'status' => 'expired',
                            'responded_at' => now(),
                        ]);
                    }
                    return false;
                }

                $this->deliveryService->assignDriver($delivery, $driver);

                $offer->update([
                    'status' => 'accepted',
                    'responded_at' => now(),
                ]);

                DeliveryOffer::where('delivery_id', $deliveryId)
                    ->where('id', '!=', $offer->id)
                    ->where('status', 'pending')
                    ->update([
                        'status' => 'expired',
                        'responded_at' => now(),
                    ]);

                return true;
            }, 3);
        } catch (\Throwable $e) {
            Log::warning('DriverOffer: acceptation refusée', [
                'delivery_id' => $deliveryId,
                'driver_id' => $driver->id,
                'error' => $e->getMessage(),
            ]);

            return $this->respond($request, false, $e->getMessage(), 422);
        }

        if (! $accepted) {
            return $this->respond($request, false, 'Offre expirée ou mission déjà attribuée.', 409);
        }

        Log::info('DriverOffer: offre acceptée', [
            'delivery_id' => $deliveryId,
            'driver_id' => $driver->id,
        ]);

        return $this->respond($request, true, 'Mission acceptée. Rendez-vous au restaurant.');
    }

    public function decline(Request $request, int $deliveryId)
    {
        $request->validate([
            'reason' => 'nullable|string|max:100',
        ]);

        $driver = $this->driverResolver->current();
        if (! $driver) {
            return $this->respond($request, false, 'Compte livreur approuvé introuvable.', 403);
        }

        DeliveryOffer::where('delivery_id', $deliveryId)
            ->where('driver_id', $driver->id)
            ->where('status', 'pending')
            ->update([
                'status' => 'declined',
                'responded_at' => now(),
                'decline_reason' => $request->input('reason', 'driver_declined'),
            ]);

        return $this->respond($request, true, 'Mission refusée.');
    }

    protected function respond(Request $request, bool $ok, string $message, int $status = 200)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => $ok,
                'message' => $message,
            ], $status);
        }

        return redirect()->route('driver.deliveries')
            ->with($ok ? 'success' : 'error', $message);
    }
}
