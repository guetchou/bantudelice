<?php

namespace Tests\Unit;

use App\Domain\GePay\Services\GePaySigner;
use PHPUnit\Framework\TestCase;

class GePaySignerTest extends TestCase
{
    public function test_signature_is_deterministic_and_sensitive_to_signed_fields(): void
    {
        $first = GePaySigner::sign(
            'secret',
            '1710000000',
            'POST',
            '/api/gepay/v1/collections',
            '{"amount":500}',
            'nonce-1',
            'idem-1'
        );
        $second = GePaySigner::sign(
            'secret',
            '1710000000',
            'POST',
            '/api/gepay/v1/collections',
            '{"amount":500}',
            'nonce-1',
            'idem-1'
        );
        $differentBody = GePaySigner::sign(
            'secret',
            '1710000000',
            'POST',
            '/api/gepay/v1/collections',
            '{"amount":501}',
            'nonce-1',
            'idem-1'
        );
        $differentNonce = GePaySigner::sign(
            'secret',
            '1710000000',
            'POST',
            '/api/gepay/v1/collections',
            '{"amount":500}',
            'nonce-2',
            'idem-1'
        );
        $differentIdempotency = GePaySigner::sign(
            'secret',
            '1710000000',
            'POST',
            '/api/gepay/v1/collections',
            '{"amount":500}',
            'nonce-1',
            'idem-2'
        );

        $this->assertSame($first, $second);
        $this->assertNotSame($first, $differentBody);
        $this->assertNotSame($first, $differentNonce);
        $this->assertNotSame($first, $differentIdempotency);
    }
}
