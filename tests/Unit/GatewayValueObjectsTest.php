<?php

namespace Tests\Unit;

use App\Domain\Payment\ValueObjects\GatewayResult;
use App\Domain\Payment\ValueObjects\GatewayStatus;
use Tests\TestCase;

class GatewayValueObjectsTest extends TestCase
{
    // -------------------------------------------------------------------------
    // GatewayResult
    // -------------------------------------------------------------------------

    /** @test */
    public function gateway_result_success_has_correct_shape()
    {
        $result = GatewayResult::success('REF-001', ['provider' => 'momo'], null, false);

        $this->assertTrue($result->success);
        $this->assertSame('REF-001', $result->providerReference);
        $this->assertFalse($result->isDemo);
        $this->assertNull($result->error);
    }

    /** @test */
    public function gateway_result_demo_sets_demo_flag_in_meta()
    {
        $result = GatewayResult::demo('DEMO-001', ['extra' => 'val']);

        $this->assertTrue($result->success);
        $this->assertTrue($result->isDemo);
        $this->assertTrue($result->meta['demo']);
    }

    /** @test */
    public function gateway_result_failure_has_no_provider_reference()
    {
        $result = GatewayResult::failure('Something went wrong');

        $this->assertFalse($result->success);
        $this->assertNull($result->providerReference);
        $this->assertSame('Something went wrong', $result->error);
    }

    /** @test */
    public function gateway_result_to_array_is_compatible_with_existing_payment_service_contract()
    {
        $result = GatewayResult::success('REF-XYZ', ['foo' => 'bar'], 'https://redirect.example.com');
        $array  = $result->toArray();

        $this->assertArrayHasKey('provider_reference', $array);
        $this->assertArrayHasKey('meta', $array);
        $this->assertArrayHasKey('redirect_url', $array);
        $this->assertSame('REF-XYZ', $array['provider_reference']);
        $this->assertSame('https://redirect.example.com', $array['redirect_url']);
    }

    // -------------------------------------------------------------------------
    // GatewayStatus
    // -------------------------------------------------------------------------

    /** @test */
    public function gateway_status_paid_is_correctly_identified()
    {
        $status = GatewayStatus::paid([], 'SUCCESSFUL');

        $this->assertTrue($status->isPaid());
        $this->assertFalse($status->isFailed());
        $this->assertFalse($status->isPending());
        $this->assertSame('PAID', $status->status);
        $this->assertSame('SUCCESSFUL', $status->providerStatus);
    }

    /** @test */
    public function gateway_status_failed_carries_reason_and_action()
    {
        $status = GatewayStatus::failed('FAILED', 'NOT_ENOUGH_FUNDS', 'Rechargez le compte');

        $this->assertTrue($status->isFailed());
        $this->assertSame('NOT_ENOUGH_FUNDS', $status->failureReason);
        $this->assertSame('Rechargez le compte', $status->failureAction);
    }

    /** @test */
    public function gateway_status_pending_is_not_paid_or_failed()
    {
        $status = GatewayStatus::pending('PENDING');

        $this->assertFalse($status->isPaid());
        $this->assertFalse($status->isFailed());
        $this->assertTrue($status->isPending());
    }

    /** @test */
    public function gateway_status_to_array_has_expected_keys()
    {
        $status = GatewayStatus::paid(['raw' => 'data'], 'SUCCESSFUL');
        $array  = $status->toArray();

        $this->assertArrayHasKey('status', $array);
        $this->assertArrayHasKey('provider_status', $array);
        $this->assertArrayHasKey('failure_reason', $array);
        $this->assertArrayHasKey('failure_action', $array);
        $this->assertArrayHasKey('meta', $array);
    }
}
