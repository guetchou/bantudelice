<?php

namespace App\Http\Controllers;

use App\Driver;
use App\Delivery;
use App\DeliveryOffer;
use App\Services\DeliveryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DriverOfferController extends Controller
{
    public function __construct()
    {
        // Route web : le livreur est connecté via session (guard 'web'), pas 'driver' (Passport mobile)
        $this->middleware('auth');
    }

    /**
     * Résoudre le Driver depuis l'utilisateur connecté (session web).
     */
    private function resolveDriver(): ?Driver
    {
        $user = auth()->user();
        if (!$user) return null;

        if (true) {
            $d = Driver::where('user_id', $user->id)->first();
            if ($d) return $d;
        }
        return Driver::where('email', $user->email)
            ->orWhere('phone', $user->phone)
            ->first();
    }

    /**
     * Accepter une offre de livraison.
     */
    public function accept(Request $request, int $deliveryId)
    {
        $driver = $this->resolveDriver();
        if (!$driver) {
            return $this->respond($request, false, 'Compte livreur introuvable.');
        }

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

        // Verrouillage pessimiste — race condition multi-driver
        $locked = DB::transaction(function () use ($delivery, $driver, $offer) {
            $fresh = Delivery::lockForUpdate()->find($delivery->id);
            if ($fresh->status !== 'PENDING') return false;

            $offer->update(['status' => 'accepted', 'responded_at' => now()]);

            DeliveryOffer::where('delivery_id', $delivery->id)
                ->where('id', '!=', $offer->id)
                ->where('status', 'pending')
                ->update(['status' => 'expired']);

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
        $driver = $this->resolveDriver();
        if (!$driver) {
            return $this->respond($request, false, 'Compte livreur introuvable.');
        }

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

    protected function respond(Request $request, bool $ok, string $message)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['status' => $ok, 'message' => $message]);
        }
        return redirect()->route('driver.deliveries')
            ->with($ok ? 'success' : 'error', $message);
    }
}
