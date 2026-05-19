<?php

namespace Tests\Unit;

use App\Domain\Payment\Adapters\CashDemoAdapter;
use App\Domain\Payment\ValueObjects\GatewayStatus;
use App\Payment;
use Tests\TestCase;

class CashDemoAdapterTest extends TestCase
{
    private CashDemoAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adapter = new CashDemoAdapter();
    }

    /** @test */
    public function provider_identifier_is_cash()
    {
        $this->assertSame('cash', $this->adapter->provider());
    }

    /** @test */
    public function initiate_returns_demo_result_with_reference()
    {
        $payment = new Payment([
            'id'       => 42,
            'provider' => 'cash',
            'amount'   => 1000,
            'currency' => 'XAF',
        ]);
        $payment->id = 42;

        $result = $this->adapter->initiate($payment, []);

        $this->assertTrue($result->success);
        $this->assertTrue($result->isDemo);
        $this->assertStringStartsWith('DEMO-CASH-42-', $result->providerReference);
    }

    /** @test */
    public function check_status_returns_pending_for_cash()
    {
        $status = $this->adapter->checkStatus('DEMO-CASH-42');

        $this->assertSame(GatewayStatus::PENDING, $status->status);
    }

    /** @test */
    public function handle_callback_returns_pending()
    {
        $status = $this->adapter->handleCallback(['anything' => 'here']);

        $this->assertSame(GatewayStatus::PENDING, $status->status);
    }

    /** @test */
    public function verify_signature_always_returns_true()
    {
        $this->assertTrue($this->adapter->verifySignature([]));
    }
}
