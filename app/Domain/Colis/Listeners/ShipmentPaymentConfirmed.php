<?php

namespace App\Domain\Colis\Listeners;

use App\Domain\Colis\Services\ShipmentPaymentService;
use App\Domain\Payment\Events\PaymentConfirmed;
use Illuminate\Support\Facades\Log;

class ShipmentPaymentConfirmed
{
    public function handle(PaymentConfirmed $event): void
    {
        $payment = $event->payment;

        if (! $payment->shipment_id) {
            return;
        }

        $shipment = $payment->shipment;

        if (! $shipment) {
            Log::warning('ShipmentPaymentConfirmed : colis introuvable', [
                'payment_id'  => $payment->id,
                'shipment_id' => $payment->shipment_id,
            ]);
            return;
        }

        app(ShipmentPaymentService::class)->finalizePayment($shipment, $payment->fresh());
    }
}
