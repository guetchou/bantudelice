<?php

namespace App\Http\Controllers\Api;

use App\Delivery;
use App\DeliveryOffer;
use App\Http\Controllers\Controller;
use App\Services\DeliveryService;
use App\Support\Auth\AuthenticatedDriverResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DriverOfferController extends Controller
{
    public function __construct(
        private DeliveryService $deliveryService,
        private AuthenticatedDriverResolver $driverResolver
    ) {
        $this->middleware('auth:driver_api');
    }

    public function index()
    {
        $driver = $this->driverResolver->current();
        if (! $driver) {
            return response()->json(['status' => false, 'message' => 'Compte livreur non autorisé.'], 403);
        }

        $offers = DeliveryOffer::query()
            ->with(['delivery.order.restaurant'])
            ->where('driver_id', $driver->id)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->orderByDesc('created_at')
            ->get()
            ->filter(fn (DeliveryOffer $offer) => $offer->delivery && $offer->delivery->status === 'PENDING')
            ->values()
            ->map(function (DeliveryOffer $offer) {
                $order = $offer->delivery->order;

                return [
                    'id' => $offer->id,
                    'delivery_id' => $offer->delivery_id,
                    'order_no' => $order->order_no ?? null,
                    'restaurant' => [
                        'id' => $order->restaurant->id ?? null,
                        'name' => $order->restaurant->name ?? null,
                        'address' => $order->restaurant->address ?? null,
                    ],
                    'distance_km' => $offer->distance_km,
                    'driver_score' => $offer->driver_score,
                    'offer_rank' => $offer->offer_rank,
                    'expires_at' => $offer->expires_at?->toIso8601String(),
                    'expires_in_seconds' => $offer->expires_at
                        ? max(0, now()->diffInSeconds($offer->expires_at, false))
                        : 0,
                ];
            });

        return response()->json(['status' => true, 'data' => $offers]);
    }

    public function accept(Request $request, int $deliveryId)
    {
        $driver = $this->driverResolver->current();
        if (! $driver) {
            return response()->json(['status' => false, 'message' => 'Compte livreur non autorisé.'], 403);
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
                        $offer->update(['status' => 'expired', 'responded_at' => now()]);
                    }
                    return false;
                }

                $this->deliveryService->assignDriver($delivery, $driver);
                $offer->update(['status' => 'accepted', 'responded_at' => now()]);

                DeliveryOffer::where('delivery_id', $deliveryId)
                    ->where('id', '!=', $offer->id)
                    ->where('status', 'pending')
                    ->update(['status' => 'expired', 'responded_at' => now()]);

                return true;
            }, 3);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        if (! $accepted) {
            return response()->json([
                'status' => false,
                'message' => 'Offre expirée ou mission déjà attribuée.',
            ], 409);
        }

        return response()->json([
            'status' => true,
            'message' => 'Mission acceptée.',
        ]);
    }

    public function decline(Request $request, int $deliveryId)
    {
        $request->validate(['reason' => 'nullable|string|max:100']);

        $driver = $this->driverResolver->current();
        if (! $driver) {
            return response()->json(['status' => false, 'message' => 'Compte livreur non autorisé.'], 403);
        }

        $updated = DeliveryOffer::where('delivery_id', $deliveryId)
            ->where('driver_id', $driver->id)
            ->where('status', 'pending')
            ->update([
                'status' => 'declined',
                'responded_at' => now(),
                'decline_reason' => $request->input('reason', 'driver_declined'),
            ]);

        return response()->json([
            'status' => true,
            'message' => $updated ? 'Mission refusée.' : 'Aucune offre active.',
        ]);
    }
}
