<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RemembersFrontendBrand;
use App\Domain\Colis\Models\Shipment;
use App\Domain\Colis\Services\ShipmentStateMachine;
use App\Domain\Colis\Enums\ShipmentStatus;
use App\Services\PaymentExperienceService;
use Illuminate\Http\Request;

class ColisCustomerController extends Controller
{
    use RemembersFrontendBrand;

    public function myShipments()
    {
        if (!auth()->check()) {
            return redirect()->route('user.login');
        }

        $shipments = \App\Domain\Colis\Models\Shipment::where('customer_id', auth()->id())
            ->latest()
            ->get();

        return view('frontend.colis.index', compact('shipments'));
    }

    public function showShipment($id)
    {
        if (!auth()->check()) {
            return redirect()->route('user.login');
        }

        $shipment = \App\Domain\Colis\Models\Shipment::where('customer_id', auth()->id())
            ->with(['addresses', 'events', 'payments' => function ($query) {
                $query->latest('id');
            }])
            ->findOrFail($id);

        $paymentExperience = app(\App\Services\PaymentExperienceService::class)->describe($shipment->payments->first());

        return response()
            ->view('frontend.colis.show', compact('shipment', 'paymentExperience'))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    public function cancelShipment($id, \App\Domain\Colis\Services\ShipmentStateMachine $stateMachine)
    {
        if (!auth()->check()) {
            return redirect()->route('user.login');
        }

        $shipment = \App\Domain\Colis\Models\Shipment::where('customer_id', auth()->id())
            ->findOrFail($id);

        if (!in_array($shipment->status, [
            \App\Domain\Colis\Enums\ShipmentStatus::CREATED,
            \App\Domain\Colis\Enums\ShipmentStatus::PRICED,
            \App\Domain\Colis\Enums\ShipmentStatus::PAID,
        ], true)) {
            return redirect()
                ->route('colis.show', $shipment->id)
                ->with('error', "Annulation impossible a ce stade.");
        }

        $stateMachine->transitionTo($shipment, \App\Domain\Colis\Enums\ShipmentStatus::CANCELED, [
            'actor_type' => 'customer',
            'actor_id' => auth()->id(),
            'notes' => 'Annule par le client.',
        ]);

        return redirect()
            ->route('colis.show', $shipment->id)
            ->with('success', 'Envoi annule avec succes.');
    }

    /**
     * Suivi public d'un colis
     */
    public function trackShipmentPublic(Request $request)
    {
        $trackingNumber = $request->get('tracking_number');
        $shipment = null;

        if ($trackingNumber) {
            $shipment = \App\Domain\Colis\Models\Shipment::where('tracking_number', $trackingNumber)
                ->with(['events' => function($query) {
                    $query->latest();
                }, 'payments' => function ($query) {
                    $query->latest('id');
                }])
                ->first();
        }

        $paymentExperience = $shipment
            ? app(\App\Services\PaymentExperienceService::class)->describe($shipment->payments->first())
            : null;

        return response()
            ->view('frontend.colis.track_public', compact('shipment', 'trackingNumber', 'paymentExperience'))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    public function storeShipment(Request $request)
    {
        // Cette route existait mais la logique métier n'était pas implémentée.
        // Redirection vers le formulaire de création jusqu'à implémentation complète.
        return redirect()->route('colis.create')->with('message', 'Utilisez ce formulaire pour créer un envoi.');
    }
}
