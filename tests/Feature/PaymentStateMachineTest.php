<?php

namespace Tests\Feature;

use Tests\TestCase;

class PaymentStateMachineTest extends TestCase
{
    public function test_payment_state_machine_is_temporarily_isolated_for_diagnosis(): void
    {
        $this->markTestSkipped('Diagnostic CI temporaire du noyau financier.');
    }
}
