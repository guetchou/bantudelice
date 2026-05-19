<?php

namespace App\Http\Controllers;

use App\Delivery;
use App\DeliveryOffer;
use App\Services\DeliveryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DriverOfferController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:driver');
    }

    /**
     * Accepter une offre de livraison.
     */
    public function accept(Request $request, int $deliveryId)
    {
        $driver   = auth('driver')->user();
        $delivery = Delivery::find($deliveryId);

        if (!$delivery || $delivery->status !== 'PENDING') {
            return $this->respond($request, false, 'Cette livraison n\'est plus disponible.');
        }

        $offer = DeliveryOffer::where('delivery_id', $deliveryId)
            ->where('driver_id', $driver->id)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->first();

        if (!$offer) {
            return $this->respond($request, false, 'Offre expirée ou introuvable.');
        }

        // Verrouillage pessimiste : on vérifie qu'aucun autre livreur n'a déjà accepté
        $locked = \Illuminate\Support\Facades\DB::transaction(function () use ($delivery, $driver, $offer) {
            $fresh = Delivery::lockForUpdate()->find($delivery->id);
            if ($fresh->status !== 'PENDING') return false;

            // Marquer l'offre acceptée
            $offer->update(['status' => 'accepted', 'responded_at' => now()]);

            // Expirer les autres offres pending pour cette livraison
            DeliveryOffer::where('delivery_id', $delivery->id)
                ->where('id', '!=', $offer->id)
                ->where('status', 'pending')
                ->update(['status' => 'expired']);

            // Assigner le livreur
            app(DeliveryService::class)->assignDriver($fresh, $driver);

            return true;
        });

        if (!$locked) {
            return $this->respond($request, false, 'Un autre livreur a déjà accepté cette mission.');
        }

        Log::info('DriverOffer: offre acceptée', ['delivery_id' => $deliveryId, 'driver_id' => $driver->id]);
        return $this->respond($request, true, 'Mission acceptée ! Rendez-vous au restaurant.');
    }

    /**
     * Refuser une offre de livraison.
     */
    public function decline(Request $request, int $deliveryId)
    {
        $driver = auth('driver')->user();

        $offer = DeliveryOffer::where('delivery_id', $deliveryId)
            ->where('driver_id', $driver->id)
            ->where('status', 'pending')
            ->first();

        if ($offer) {
            $offer->update([
                'status'         => 'declined',
                'responded_at'   => now(),
                'decline_reason' => $request->input('reason', 'driver_declined'),
            ]);
        }

        return $this->respond($request, true, 'Mission refusée.');
    }

    /**
     * Retourne JSON pour AJAX ou redirect pour web.
     */
    protected function respond(Request $request, bool $ok, string $message)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['status' => $ok, 'message' => $message]);
        }
        return redirect()->route('driver.deliveries')
            ->with($ok ? 'success' : 'error', $message);
    }
}
