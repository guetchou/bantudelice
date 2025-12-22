<?php

namespace App\Domain\Colis\Services;

use App\Domain\Colis\Enums\ShipmentStatus;
use App\Domain\Colis\Models\Shipment;
use App\Domain\Colis\Models\ShipmentEvent;
use Illuminate\Support\Facades\DB;
use Exception;

class ShipmentStateMachine
{
    /**
     * Définition des transitions autorisées
     */
    protected array $transitions = [];

    public function __construct()
    {
        $this->transitions = [
            ShipmentStatus::CREATED->value => [ShipmentStatus::PRICED->value, ShipmentStatus::PAID->value, ShipmentStatus::CANCELED->value],
            ShipmentStatus::PRICED->value => [ShipmentStatus::PAID->value, ShipmentStatus::CANCELED->value],
            ShipmentStatus::PAID->value => [ShipmentStatus::PICKED_UP->value, ShipmentStatus::CANCELED->value, ShipmentStatus::DAMAGED->value, ShipmentStatus::LOST->value],
            ShipmentStatus::PICKED_UP->value => [ShipmentStatus::IN_TRANSIT->value, ShipmentStatus::AT_RELAY->value, ShipmentStatus::DAMAGED->value, ShipmentStatus::LOST->value],
            ShipmentStatus::IN_TRANSIT->value => [ShipmentStatus::OUT_FOR_DELIVERY->value, ShipmentStatus::AT_RELAY->value, ShipmentStatus::FAILED->value, ShipmentStatus::DAMAGED->value, ShipmentStatus::LOST->value],
            ShipmentStatus::AT_RELAY->value => [ShipmentStatus::OUT_FOR_DELIVERY->value, ShipmentStatus::PICKED_UP->value, ShipmentStatus::DAMAGED->value, ShipmentStatus::LOST->value],
            ShipmentStatus::OUT_FOR_DELIVERY->value => [ShipmentStatus::DELIVERED->value, ShipmentStatus::FAILED->value, ShipmentStatus::DAMAGED->value, ShipmentStatus::LOST->value],
            ShipmentStatus::FAILED->value => [ShipmentStatus::OUT_FOR_DELIVERY->value, ShipmentStatus::RETURNED->value, ShipmentStatus::DAMAGED->value, ShipmentStatus::LOST->value],
            ShipmentStatus::RETURNED->value => [], 
            ShipmentStatus::DELIVERED->value => [], 
            ShipmentStatus::CANCELED->value => [], 
            ShipmentStatus::DAMAGED->value => [ShipmentStatus::RETURNED->value, ShipmentStatus::LOST->value],
            ShipmentStatus::LOST->value => [],
        ];
    }

    /**
     * Faire passer un colis à un nouveau statut
     */
    public function transitionTo(Shipment $shipment, ShipmentStatus $newStatus, array $context = []): Shipment
    {
        $currentStatus = $shipment->status;

        if (!$this->canTransition($currentStatus, $newStatus)) {
            throw new Exception("Transition de {$currentStatus->value} vers {$newStatus->value} non autorisée.");
        }

        return DB::transaction(function () use ($shipment, $newStatus, $context) {
            $shipment->update(['status' => $newStatus]);

            // Créer l'événement de tracking
            ShipmentEvent::create([
                'shipment_id' => $shipment->id,
                'status' => $newStatus,
                'actor_type' => $context['actor_type'] ?? 'system',
                'actor_id' => $context['actor_id'] ?? null,
                'notes' => $context['notes'] ?? null,
                'meta' => $context['meta'] ?? null,
            ]);

            // Déclencher les notifications automatiques
            app(\App\Domain\Colis\Services\ShipmentNotificationService::class)->notifyStatusChange($shipment);

            // Audit Log pour les actions Admin/Système
            $actorType = $context['actor_type'] ?? 'system';
            if ($actorType === 'admin' || $actorType === 'system') {
                \App\Domain\Colis\Models\ShipmentAuditLog::create([
                    'shipment_id' => $shipment->id,
                    'user_id' => $context['actor_id'] ?? null,
                    'event' => 'status_change',
                    'new_values' => ['status' => $newStatus->value],
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);
            }

            // Actions spécifiques
            if ($newStatus === ShipmentStatus::OUT_FOR_DELIVERY) {
                // Déclencher l'envoi de l'OTP au destinataire ici via un service de notification
                $proofService = app(\App\Domain\Colis\Services\ShipmentProofService::class);
                $proofService->generateOTP($shipment);
            }

            if ($newStatus === ShipmentStatus::DELIVERED) {
                $shipment->update(['delivered_at' => now()]);
            }

            return $shipment;
        });
    }

    /**
     * Vérifier si une transition est possible
     */
    public function canTransition(ShipmentStatus $from, ShipmentStatus $to): bool
    {
        return in_array($to->value, $this->transitions[$from->value] ?? []);
    }
}

