<?php

namespace App\Domain\Colis\Services;

use App\Domain\Colis\Events\ShipmentStatusUpdated;
use App\Domain\Colis\Events\ShipmentMissionPresenceUpdated;
use App\Domain\Colis\Enums\ShipmentStatus;
use App\Domain\Colis\Models\Shipment;
use App\Domain\Colis\Models\ShipmentEvent;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Exception;

class ShipmentStateMachine
{
    protected array $transitions = [];

    public function __construct(
        protected ?AuditLogService $auditLogService = null
    )
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

    public function transitionTo(Shipment $shipment, ShipmentStatus $newStatus, array $context = []): Shipment
    {
        $currentStatus = $shipment->status;

        if (!$this->canTransition($currentStatus, $newStatus)) {
            throw new Exception("Transition de {$currentStatus->value} vers {$newStatus->value} non autorisée.");
        }

        $updatedShipment = DB::transaction(function () use ($shipment, $currentStatus, $newStatus, $context) {
            $updatePayload = ['status' => $newStatus];

            if (Schema::hasColumn('shipments', 'last_status_changed_at')) {
                $updatePayload['last_status_changed_at'] = now();
            }

            if ($newStatus === ShipmentStatus::DELIVERED) {
                $updatePayload['delivered_at'] = now();

                if (Schema::hasColumn('shipments', 'delivered_latitude') && isset($context['meta']['delivery_latitude'])) {
                    $updatePayload['delivered_latitude'] = $context['meta']['delivery_latitude'];
                }

                if (Schema::hasColumn('shipments', 'delivered_longitude') && isset($context['meta']['delivery_longitude'])) {
                    $updatePayload['delivered_longitude'] = $context['meta']['delivery_longitude'];
                }
            }

            $shipment->update($updatePayload);

            ShipmentEvent::create([
                'shipment_id' => $shipment->id,
                'status' => $newStatus,
                'actor_type' => $context['actor_type'] ?? 'system',
                'actor_id' => $context['actor_id'] ?? null,
                'notes' => $context['notes'] ?? null,
                'meta' => array_merge([
                    'from_status' => $currentStatus->value,
                    'to_status' => $newStatus->value,
                ], $context['meta'] ?? []),
            ]);

            enqueue_job('colis', 'send_shipment_status_notification', [
                'shipment_id' => $shipment->id,
            ]);
            $this->auditLogs()->record([
                'actor_type' => $context['actor_type'] ?? 'system',
                'actor_id' => $context['actor_id'] ?? null,
                'target_type' => 'shipment',
                'target_id' => $shipment->id,
                'target_ref' => $shipment->tracking_number,
                'action' => 'status_transition',
                'status' => $newStatus->value,
                'meta' => [
                    'from_status' => $currentStatus->value,
                    'to_status' => $newStatus->value,
                ],
            ]);

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

            if ($newStatus === ShipmentStatus::OUT_FOR_DELIVERY) {
                enqueue_job('colis', 'generate_shipment_delivery_otp', [
                    'shipment_id' => $shipment->id,
                ]);
            }

            return $shipment->fresh();
        });

        if (empty($context['suppress_realtime'])) {
            $updatedShipment = $updatedShipment->loadMissing(['customer', 'courier']);
            event(new ShipmentStatusUpdated($updatedShipment));
            event(new ShipmentMissionPresenceUpdated($updatedShipment));
        }

        return $updatedShipment;
    }

    public function canTransition(ShipmentStatus $from, ShipmentStatus $to): bool
    {
        return in_array($to->value, $this->transitions[$from->value] ?? [], true);
    }

    protected function auditLogs(): AuditLogService
    {
        return $this->auditLogService ?? app(AuditLogService::class);
    }
}
