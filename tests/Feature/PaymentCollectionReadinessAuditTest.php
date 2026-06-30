<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PaymentCollectionReadinessAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_command_is_registered(): void
    {
        $this->artisan('finance:audit-payment-collection-readiness')->assertExitCode(0);
    }
}
