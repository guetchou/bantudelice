<?php

namespace Tests\Feature;

use Tests\TestCase;

class PaymentStateMachineTest extends TestCase
{
    public function test_state_machine_temporarily_excluded_from_group_diagnosis(): void
    {
        $this->markTestSkipped('Diagnostic du groupe registre et affectation.');
    }
}
