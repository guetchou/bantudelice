<?php

namespace Tests\Unit;

use App\Domain\Payment\Adapters\AirtelMoneyAdapter;
use App\Domain\Payment\Adapters\CashDemoAdapter;
use App\Domain\Payment\Adapters\MtnMomoAdapter;
use App\Domain\Payment\Adapters\PayPalAdapter;
use App\Domain\Payment\PaymentGatewayFactory;
use Tests\TestCase;

class PaymentGatewayFactoryTest extends TestCase
{
    private PaymentGatewayFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        // GePay flag off so these tests validate direct MTN/Airtel routing
        config(['gepay.bantudelice.collections_enabled' => false]);
        $this->factory = $this->app->make(PaymentGatewayFactory::class);
    }

    /** @test */
    public function it_resolves_mtn_adapter_for_momo_provider()
    {
        $adapter = $this->factory->for('momo');
        $this->assertInstanceOf(MtnMomoAdapter::class, $adapter);
    }

    /** @test */
    public function it_resolves_mtn_adapter_for_mtn_momo_alias()
    {
        $this->assertInstanceOf(MtnMomoAdapter::class, $this->factory->for('mtn_momo'));
        $this->assertInstanceOf(MtnMomoAdapter::class, $this->factory->for('mtn'));
    }

    /** @test */
    public function it_resolves_airtel_adapter_for_airtel_provider()
    {
        $adapter = $this->factory->for('airtel');
        $this->assertInstanceOf(AirtelMoneyAdapter::class, $adapter);
    }

    /** @test */
    public function it_resolves_airtel_adapter_for_airtel_money_alias()
    {
        $this->assertInstanceOf(AirtelMoneyAdapter::class, $this->factory->for('airtel_money'));
    }

    /** @test */
    public function it_resolves_paypal_adapter()
    {
        $adapter = $this->factory->for('paypal');
        $this->assertInstanceOf(PayPalAdapter::class, $adapter);
    }

    /** @test */
    public function it_resolves_cash_adapter_for_cash_provider()
    {
        $adapter = $this->factory->for('cash');
        $this->assertInstanceOf(CashDemoAdapter::class, $adapter);
    }

    /** @test */
    public function it_resolves_cash_adapter_for_demo_and_cod()
    {
        $this->assertInstanceOf(CashDemoAdapter::class, $this->factory->for('demo'));
        $this->assertInstanceOf(CashDemoAdapter::class, $this->factory->for('cod'));
    }

    /** @test */
    public function it_throws_for_unknown_provider()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->factory->for('unknown_provider_xyz');
    }

    /** @test */
    public function it_is_case_insensitive()
    {
        $this->assertInstanceOf(MtnMomoAdapter::class, $this->factory->for('MOMO'));
        $this->assertInstanceOf(PayPalAdapter::class, $this->factory->for('PAYPAL'));
    }

    /** @test */
    public function supports_returns_true_for_known_providers()
    {
        $this->assertTrue($this->factory->supports('momo'));
        $this->assertTrue($this->factory->supports('airtel'));
        $this->assertTrue($this->factory->supports('paypal'));
        $this->assertTrue($this->factory->supports('cash'));
    }

    /** @test */
    public function supports_returns_false_for_unknown_provider()
    {
        $this->assertFalse($this->factory->supports('bitcoin'));
    }

    /** @test */
    public function adapter_provider_identifiers_are_correct()
    {
        $this->assertSame('momo', $this->factory->for('momo')->provider());
        $this->assertSame('airtel', $this->factory->for('airtel')->provider());
        $this->assertSame('paypal', $this->factory->for('paypal')->provider());
        $this->assertSame('cash', $this->factory->for('cash')->provider());
    }
}
