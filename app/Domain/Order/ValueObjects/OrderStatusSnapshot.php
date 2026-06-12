<?php

namespace App\Domain\Order\ValueObjects;

/**
 * Lecture unifiée du statut d'une commande food.
 *
 * Encapsule les quatre sources de vérité qui coexistent :
 *   - order.status           (champ legacy, enum 2020)
 *   - order.business_status  (champ domaine, 18+ valeurs)
 *   - order.technical_status (code raison interne, souvent null)
 *   - delivery.status        (cycle de vie livraison, UPPERCASE)
 *
 * Cette classe est en lecture seule — elle n'écrit rien en base.
 * Les transitions restent dans FoodOrderStateMachineService.
 * Les méthodes existantes de Order.php sont conservées et appelées
 * par Order::statusSnapshot() pour rester la source canonique.
 *
 * Créée en phase 1 : unifier la LECTURE.
 * Phase 2 centralisera les TRANSITIONS.
 */
final class OrderStatusSnapshot
{
    public function __construct(
        private readonly string  $effectiveBusinessStatus,
        private readonly string  $trackingStatus,
        private readonly int     $trackingProgress,
        private readonly ?string $technicalStatus,
        private readonly ?string $deliveryStatus,
        private readonly string  $paymentStatus,
        private readonly bool    $isPickup,
        private readonly bool    $canBeModified,
    ) {}

    // =========================================================================
    // Statut effectif — source canonique
    // =========================================================================

    /**
     * Statut métier résolu : delivery.status > business_status > legacy status.
     * Même priorité que Order::resolveEffectiveBusinessStatus().
     */
    public function effectiveBusinessStatus(): string
    {
        return $this->effectiveBusinessStatus;
    }

    /**
     * Statut de suivi côté client (legacy enum : pending/prepairing/assign/pickup/onway/completed/cancelled).
     */
    public function trackingStatus(): string
    {
        return $this->trackingStatus;
    }

    /**
     * Progression en pourcentage (0–100).
     */
    public function trackingProgress(): int
    {
        return $this->trackingProgress;
    }

    /**
     * Code raison technique interne (dispatch_retry, fraud_hold, etc.) — souvent null.
     */
    public function technicalStatus(): ?string
    {
        return $this->technicalStatus;
    }

    /**
     * Statut de la livraison associée (PENDING/ASSIGNED/PICKED_UP/ON_THE_WAY/DELIVERED/CANCELLED).
     * Null si pas de livraison (commande retrait ou livraison non encore créée).
     */
    public function deliveryStatus(): ?string
    {
        return $this->deliveryStatus;
    }

    /**
     * Statut du paiement (pending/paid/failed).
     */
    public function paymentStatus(): string
    {
        return $this->paymentStatus;
    }

    // =========================================================================
    // Prédicats métier
    // =========================================================================

    public function isPickup(): bool
    {
        return $this->isPickup;
    }

    public function isDelivery(): bool
    {
        return ! $this->isPickup;
    }

    public function isPaid(): bool
    {
        return $this->paymentStatus === 'paid';
    }

    public function isCancelable(): bool
    {
        return in_array($this->effectiveBusinessStatus, [
            'pending_restaurant_acceptance',
            'accepted',
        ], true);
    }

    public function canBeModified(): bool
    {
        return $this->canBeModified;
    }

    public function isReadyForDelivery(): bool
    {
        return in_array($this->effectiveBusinessStatus, [
            'ready_for_pickup',
            'dispatching',
        ], true);
    }

    public function isInDelivery(): bool
    {
        return in_array($this->effectiveBusinessStatus, [
            'driver_assigned',
            'driver_arrived_at_restaurant',
            'picked_up',
            'out_for_delivery',
            'delivery_attempt_failed',
        ], true);
    }

    public function isDelivered(): bool
    {
        return in_array($this->effectiveBusinessStatus, ['delivered', 'closed'], true);
    }

    public function isCancelled(): bool
    {
        return in_array($this->effectiveBusinessStatus, ['cancelled', 'refunded'], true);
    }

    public function isIncidentOpen(): bool
    {
        return $this->effectiveBusinessStatus === 'incident_open';
    }

    /**
     * Vrai si le statut technique signale un besoin de traitement manuel
     * (fraud_hold, restaurant_timeout non résolu, etc.).
     */
    public function requiresManualReview(): bool
    {
        return in_array($this->technicalStatus, [
            'fraud_hold',
            'payment_disputed',
            'restaurant_timeout',
            'driver_timeout',
        ], true);
    }

    /**
     * Vrai si la commande est dans un état terminal (plus aucune transition possible).
     */
    public function isTerminal(): bool
    {
        return in_array($this->effectiveBusinessStatus, [
            'delivered',
            'picked_up_by_customer',
            'closed',
            'cancelled',
            'refunded',
            'no_show',
        ], true);
    }

    // =========================================================================
    // Sérialisation — compatibilité avec les réponses JSON existantes
    // =========================================================================

    public function toArray(): array
    {
        return [
            'effective_business_status' => $this->effectiveBusinessStatus,
            'tracking_status'           => $this->trackingStatus,
            'tracking_progress'         => $this->trackingProgress,
            'technical_status'          => $this->technicalStatus,
            'delivery_status'           => $this->deliveryStatus,
            'payment_status'            => $this->paymentStatus,
            'is_pickup'                 => $this->isPickup,
            'is_paid'                   => $this->isPaid(),
            'is_cancelable'             => $this->isCancelable(),
            'is_ready_for_delivery'     => $this->isReadyForDelivery(),
            'is_in_delivery'            => $this->isInDelivery(),
            'is_delivered'              => $this->isDelivered(),
            'is_cancelled'              => $this->isCancelled(),
            'is_terminal'               => $this->isTerminal(),
            'requires_manual_review'    => $this->requiresManualReview(),
        ];
    }
}
