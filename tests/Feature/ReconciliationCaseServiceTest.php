<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReconciliationCaseServiceTest extends TestCase
{
    public function test_reconciliation_temporarily_excluded_from_group_diagnosis(): void
    {
        $this->markTestSkipped('Diagnostic du groupe registre et affectation.');
    }
}
