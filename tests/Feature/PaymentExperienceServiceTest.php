<?php

namespace Tests\Feature;

use App\Payment;
use App\Services\PaymentExperienceService;
use Tests\TestCase;

class PaymentExperienceServiceTest extends TestCase
{
    public function test_failed_payment_exposes_customer_and_support_contract()
    {
        $payment = new Payment([
            'id' => 501,
            'provider' => 'momo',
            'status' => 'FAILED',
            'amount' => 1,
            'currency' => 'XAF',
            'meta' => [
                'type' => 'colis',
                'failure_reason' => 'PAYER_NOT_FOUND',
                'failure_message' => 'Le numero MTN MoMo du payeur est introuvable ou inactif.',
                'failure_action' => 'Verifiez le numero et l\'activation du compte MTN MoMo du client.',
            ],
        ]);

        $payload = app(PaymentExperienceService::class)->describe($payment);

        $this->assertSame('colis', $payload['module']);
        $this->assertFalse($payload['retry_allowed']);
        $this->assertSame('Le numero MTN MoMo du payeur est introuvable ou inactif.', $payload['customer_message']);
        $this->assertSame('Verifiez le numero et l\'activation du compte MTN MoMo du client.', $payload['support_action']);
    }

    public function test_pending_payment_exposes_confirmation_message()
    {
        $payment = new Payment([
            'id' => 502,
            'provider' => 'momo',
            'status' => 'PENDING',
            'amount' => 1,
            'currency' => 'XAF',
            'transport_booking_id' => 77,
            'meta' => [
                'instructions' => [
                    'Vous allez recevoir une notification sur votre telephone',
                ],
            ],
        ]);

        $payload = app(PaymentExperienceService::class)->describe($payment);

        $this->assertSame('transport', $payload['module']);
        $this->assertFalse($payload['retry_allowed']);
        $this->assertSame('Vous allez recevoir une notification sur votre telephone', $payload['customer_message']);
        $this->assertSame(
            'Attendez la confirmation du provider puis relancez la verification si necessaire.',
            $payload['support_action']
        );
    }

    public function test_retry_allowed_stays_true_for_recoverable_provider_failures()
    {
        $payment = new Payment([
            'id' => 503,
            'provider' => 'momo',
            'status' => 'FAILED',
            'amount' => 1,
            'currency' => 'XAF',
            'meta' => [
                'failure_reason' => 'COULD_NOT_PERFORM_TRANSACTION',
                'failure_message' => 'MTN MoMo n\'a pas pu finaliser la transaction.',
                'failure_action' => 'Demandez au client de confirmer sur son telephone, puis reessayez si le probleme persiste.',
            ],
        ]);

        $payload = app(PaymentExperienceService::class)->describe($payment);

        $this->assertTrue($payload['retry_allowed']);
        $this->assertSame('food', $payload['module']);
    }
}
