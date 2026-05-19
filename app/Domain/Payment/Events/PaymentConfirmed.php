<?php

namespace App\Domain\Payment\Events;

use App\Payment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatché quand un paiement passe en statut PAID.
 *
 * Chaque domaine (Food, Colis, Transport) écoute cet événement
 * et exécute sa propre finalisation post-paiement.
 * Les listeners sont synchrones (phase 1) — même garantie
 * transactionnelle que markPaymentAsPaid().
 */
final class PaymentConfirmed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Payment $payment,
    ) {}
}
