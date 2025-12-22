<?php

namespace App\Domain\Colis\Services;

use App\Domain\Colis\Models\Shipment;
use App\Mail\Colis\ShipmentStatusMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ShipmentNotificationService
{
    /**
     * Envoyer les notifications appropriées selon le nouveau statut
     */
    public function notifyStatusChange(Shipment $shipment)
    {
        try {
            // 1. Notification par Email au client
            if ($shipment->customer && $shipment->customer->email) {
                Mail::to($shipment->customer->email)->send(new ShipmentStatusMail($shipment));
            }

            // 2. Notification SMS (Log uniquement si pas de gateway SMS configurée)
            $this->sendSmsNotification($shipment);

            Log::info("Notifications envoyées pour le colis {$shipment->tracking_number}", [
                'status' => $shipment->status->value
            ]);
        } catch (\Exception $e) {
            Log::error("Échec de l'envoi des notifications pour le colis {$shipment->tracking_number}", [
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function sendSmsNotification(Shipment $shipment)
    {
        $phone = $shipment->customer->phone ?? null;
        if (!$phone) return;

        $message = "BantuDelice: Votre colis {$shipment->tracking_number} est passé au statut " . $shipment->status->label();
        
        // Ici, on pourrait brancher une API SMS (ex: Twilio, Infobip ou locale)
        Log::debug("SIMULATION SMS à {$phone}: {$message}");
    }
}

