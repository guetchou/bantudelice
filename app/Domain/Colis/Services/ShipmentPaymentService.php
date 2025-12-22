<?php

namespace App\Domain\Colis\Services;

use App\Domain\Colis\Models\Shipment;
use App\Domain\Colis\Enums\ShipmentStatus;
use App\Payment;
use Illuminate\Support\Facades\DB;

class ShipmentPaymentService
{
    /**
     * Initier un paiement pour un colis
     */
    public function initiatePayment(Shipment $shipment, string $provider): array
    {
        // On crée une entrée dans la table existante 'payments'
        $payment = Payment::create([
            'user_id' => $shipment->customer_id,
            'order_id' => null, 
            'shipment_id' => $shipment->id,
            'provider' => $provider,
            'status' => 'PENDING',
            'amount' => $shipment->total_price,
            'currency' => $shipment->currency,
            'meta' => [
                'tracking_number' => $shipment->tracking_number,
                'type' => 'colis'
            ],
        ]);

        return [
            'payment_id' => $payment->id,
            'checkout_url' => url("/api/v1/payments/process/{$payment->id}"), 
            'status' => 'pending'
        ];
    }

    /**
     * Confirmer le paiement et débloquer le colis
     */
    public function finalizePayment(Shipment $shipment, Payment $payment): bool
    {
        return DB::transaction(function () use ($shipment, $payment) {
            $shipment->update([
                'payment_status' => 'paid'
            ]);

            // Faire progresser le colis via la State Machine
            $stateMachine = app(ShipmentStateMachine::class);
            $stateMachine->transitionTo($shipment, ShipmentStatus::PAID, [
                'actor_type' => 'system',
                'notes' => "Paiement confirmé via {$payment->provider}."
            ]);

            return true;
        });
    }

    /**
     * Gérer le Cash on Delivery (COD)
     */
    public function handleCOD(Shipment $shipment): void
    {
        $shipment->update([
            'payment_status' => 'cod_pending'
        ]);
        
        // Un colis COD passe directement de PRICED à PAID (prêt pour pickup) 
        // mais avec un flag de paiement à collecter par le coursier
        $stateMachine = app(ShipmentStateMachine::class);
        $stateMachine->transitionTo($shipment, ShipmentStatus::PAID, [
            'actor_type' => 'customer',
            'notes' => "Option Paiement à la livraison choisie."
        ]);
    }

    /**
     * Calculer le montant total que le coursier doit reverser (COD collectés)
     */
    public function getPendingCourierCOD(int $courierId): array
    {
        $shipments = Shipment::where('assigned_courier_id', $courierId)
            ->where('status', ShipmentStatus::DELIVERED)
            ->where('payment_status', 'cod_pending')
            ->where('cod_amount', '>', 0)
            ->get();

        return [
            'count' => $shipments->count(),
            'total_amount' => $shipments->sum('cod_amount'),
            'shipment_ids' => $shipments->pluck('id')->toArray(),
        ];
    }

    /**
     * Effectuer la réconciliation (reversement des fonds)
     */
    public function reconcileCourier(int $courierId, int $adminId, array $shipmentIds, float $amount): \App\Domain\Colis\Models\ShipmentReconciliation
    {
        return DB::transaction(function () use ($courierId, $adminId, $shipmentIds, $amount) {
            // Créer le log de réconciliation
            $reconciliation = \App\Domain\Colis\Models\ShipmentReconciliation::create([
                'courier_id' => $courierId,
                'admin_id' => $adminId,
                'amount_collected' => $amount,
                'amount_reconciled' => $amount,
                'shipment_ids' => $shipmentIds,
                'status' => 'completed',
                'notes' => "Réconciliation manuelle effectuée par l'admin."
            ]);

            // Mettre à jour les colis comme payés
            Shipment::whereIn('id', $shipmentIds)->update([
                'payment_status' => 'paid'
            ]);

            return $reconciliation;
        });
    }
}

