<?php

namespace Tests\Feature;

use App\Domain\GePay\Models\GePayClient;
use App\Domain\Payment\Adapters\GePayAdapter;
use App\Domain\Payment\Adapters\MtnMomoAdapter;
use App\Domain\Payment\PaymentGatewayFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class GePayPaymentFactoryFeatureFlagTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_returns_mtn_adapter_when_flag_disabled(): void
    {
        config(['gepay.bantudelice.collections_enabled' => false]);

        $factory = app(PaymentGatewayFactory::class);
        $adapter = $factory->for('momo');

        $this->assertInstanceOf(MtnMomoAdapter::class, $adapter);
    }

    public function test_factory_returns_gepay_adapter_when_flag_enabled(): void
    {
        config(['gepay.bantudelice.collections_enabled' => true]);

        $factory = app(PaymentGatewayFactory::class);
        $adapter = $factory->for('momo');

        $this->assertInstanceOf(GePayAdapter::class, $adapter);
    }

    public function test_factory_returns_gepay_adapter_for_mtn_alias_when_flag_enabled(): void
    {
        config(['gepay.bantudelice.collections_enabled' => true]);

        $factory = app(PaymentGatewayFactory::class);

        $this->assertInstanceOf(GePayAdapter::class, $factory->for('mtn'));
        $this->assertInstanceOf(GePayAdapter::class, $factory->for('mtn_momo'));
    }

    public function test_factory_airtel_is_never_routed_to_gepay(): void
    {
        config(['gepay.bantudelice.collections_enabled' => true]);

        $factory = app(PaymentGatewayFactory::class);
        $adapter = $factory->for('airtel');

        $this->assertNotInstanceOf(GePayAdapter::class, $adapter);
    }

    public function test_disabling_flag_after_boot_falls_back_to_mtn(): void
    {
        config(['gepay.bantudelice.collections_enabled' => true]);
        $this->assertInstanceOf(GePayAdapter::class, app(PaymentGatewayFactory::class)->for('momo'));

        config(['gepay.bantudelice.collections_enabled' => false]);
        $this->assertInstanceOf(MtnMomoAdapter::class, app(PaymentGatewayFactory::class)->for('momo'));
    }
}
