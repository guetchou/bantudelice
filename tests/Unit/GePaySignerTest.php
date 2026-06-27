<?php

namespace Tests\Unit;

use App\Domain\GePay\Services\GePaySigner;
use PHPUnit\Framework\TestCase;

class GePaySignerTest extends TestCase
{
    public function test_signature_is_deterministic_and_sensitive_to_body(): void
    {
        $first = GePaySigner::sign('secret', '1710000000', 'POST', '/api/gepay/v1/collections', '{"amount":500}');
        $second = GePaySigner::sign('secret', '1710000000', 'POST', '/api/gepay/v1/collections', '{"amount":500}');
        $different = GePaySigner::sign('secret', '1710000000', 'POST', '/api/gepay/v1/collections', '{"amount":501}');

        $this->assertSame($first, $second);
        $this->assertNotSame($first, $different);
    }
}
