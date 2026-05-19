<?php

namespace App\Domain\Colis\Services;

use App\Domain\Colis\Models\Shipment;
use App\Mail\Colis\ShipmentStatusMail;
use App\Services\NotificationService;
use App\Services\NotificationLogService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ShipmentNotificationService
{
    public function __construct(
        protected ?NotificationLogService $notificationLogService = null,
        protected ?NotificationService $notificationService = null,
    ) {}

    public function notifyStatusChange(Shipment $shipment)
    {
        try {
            if ($shipment->customer_id) {
                $this->pushCustomerNotification($shipment);
            }

            if ($shipment->customer && $shipment->customer->email) {
                Mail::to($shipment->customer->email)->send(new ShipmentStatusMail($shipment));
                $this->notificationLogs()->record([
                    'channel' => 'email',
                    'recipient_type' => 'user',
                    'recipient_id' => $shipment->customer_id,
                    'recipient_address' => $shipment->customer->email,
                    'title' => 'Statut colis mis à jour',
                    'body' => "Colis {$shipment->tracking_number} -> {$shipment->status->label()}",
                    'provider' => 'mail',
                    'status' => 'sent',
                    'context' => [
                        'module' => 'colis',
                        'shipment_id' => $shipment->id,
                        'tracking_number' => $shipment->tracking_number,
                        'status' => $shipment->status->value,
                        'route_path' => NotificationService::routePath('colis.show', [$shipment->id]),
                        'deep_link' => 'bantudelice://mema/shipments/' . $shipment->id,
                    ],
                ]);
            }

            $this->sendSmsNotification($shipment);

            Log::info("Notifications envoyées pour le colis {$shipment->tracking_number}", [
                'status' => $shipment->status->value,
            ]);
        } catch (\Exception $e) {
            $this->notificationLogs()->record([
                'channel' => 'email',
                'recipient_type' => 'user',
                'recipient_id' => $shipment->customer_id,
                'recipient_address' => $shipment->customer->email ?? null,
                'title' => 'Statut colis mis à jour',
                'body' => "Colis {$shipment->tracking_number} -> {$shipment->status->label()}",
                'provider' => 'mail',
                'status' => 'failed',
                'context' => [
                    'module' => 'colis',
                    'shipment_id' => $shipment->id,
                    'error' => $e->getMessage(),
                    'route_path' => NotificationService::routePath('colis.show', [$shipment->id]),
                    'deep_link' => 'bantudelice://mema/shipments/' . $shipment->id,
                ],
            ]);

            Log::error("Échec de l'envoi des notifications pour le colis {$shipment->tracking_number}", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function sendSmsNotification(Shipment $shipment)
    {
        $phone = $shipment->customer->phone ?? null;
        if (!$phone) {
            return;
        }

        $message = "Mema: Votre colis {$shipment->tracking_number} est passé au statut " . $shipment->status->label();
        Log::debug("SIMULATION SMS à {$phone}: {$message}");

        $this->notificationLogs()->record([
            'channel' => 'sms',
            'recipient_type' => 'user',
            'recipient_id' => $shipment->customer_id,
            'recipient_address' => $phone,
            'title' => 'Statut colis',
            'body' => $message,
            'provider' => 'simulated_sms',
            'status' => 'sent',
            'context' => [
                'module' => 'colis',
                'shipment_id' => $shipment->id,
                'tracking_number' => $shipment->tracking_number,
                'route_path' => NotificationService::routePath('colis.show', [$shipment->id]),
                'deep_link' => 'bantudelice://mema/shipments/' . $shipment->id,
            ],
        ]);
    }

    protected function pushCustomerNotification(Shipment $shipment): void
    {
        $title = 'Statut colis mis à jour';
        $body = "Votre colis {$shipment->tracking_number} est maintenant {$shipment->status->label()}.";

        $this->notificationService()->sendToUser($shipment->customer_id, $title, $body, [
            'module' => 'colis',
            'channel' => 'user',
            'type' => 'shipment_status_changed',
            'shipment_id' => $shipment->id,
            'tracking_number' => $shipment->tracking_number,
            'route_path' => NotificationService::routePath('colis.show', [$shipment->id]),
            'deep_link' => 'bantudelice://mema/shipments/' . $shipment->id,
            'public_tracking_path' => NotificationService::routePath('colis.track_public') . '?tracking=' . urlencode($shipment->tracking_number),
            'sound_key' => 'mema_status',
            'audio_cue' => 'shipment_status_soft',
            'actions' => [
                ['id' => 'open_shipment', 'label' => 'Voir l’envoi', 'path' => NotificationService::routePath('colis.show', [$shipment->id])],
                ['id' => 'track_public', 'label' => 'Suivre', 'path' => NotificationService::routePath('colis.track_public') . '?tracking=' . urlencode($shipment->tracking_number)],
            ],
            'websocket_channel' => 'colis.shipment.' . $shipment->id . '.status',
            'websocket_event' => 'colis.shipment.status.updated',
            'presence_channel' => 'colis.shipment.' . $shipment->id . '.presence',
        ]);
    }

    protected function notificationLogs(): NotificationLogService
    {
        return $this->notificationLogService ?? app(NotificationLogService::class);
    }

    protected function notificationService(): NotificationService
    {
        return $this->notificationService ?? app(NotificationService::class);
    }
}
