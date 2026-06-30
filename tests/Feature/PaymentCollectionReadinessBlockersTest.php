<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PaymentCollectionReadinessBlockersTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_can_return_json(): void
    {
        $this->artisan('finance:audit-payment-collection-readiness', ['--json' => true])
            ->assertExitCode(0);
    }
}
