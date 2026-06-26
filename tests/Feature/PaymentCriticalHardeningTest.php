<?php

namespace Tests\Feature;

use App\Domain\Payment\Adapters\AirtelMoneyAdapter;
use App\Domain\Payment\Adapters\CashDemoAdapter;
use App\Domain\Payment\Adapters\MtnMomoAdapter;
use App\Domain\Payment\Adapters\PayPalAdapter;
use App\Domain\Payment\PaymentGatewayFactory;
use App\Domain\Payment\ValueObjects\GatewayResult;
use App\Domain\Payment\ValueObjects\GatewayStatus;
use App\Http\Controllers\Api\PaymentController;
use App\Payment;
use App\User;
use Illuminate\Http\Request;
use Tests\TestCase;

class PaymentCriticalHardeningTest extends TestCase
{
    public function test_external_payment_cannot_be_confirmed_manually_by_customer(): void
    {
        $user = new User();
        $user->id = 42;

        $payment = new Payment([
            'user_id' => 42,
            'provider' => 'momo',
            'status' => 'PENDING',
            'amount' => 1000,
            'currency' => 'XAF',
        ]);
        $payment->id = 99;

        $request = Request::create('/api/payments/99/confirm', 'POST');
        $request->setUserResolver(static fn() => $user);

        $response = app(PaymentController::class)->confirm($request, $payment);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertStringContainsString(
            'confirmé directement auprès du fournisseur',
            (string) $response->getContent()
        );
    }

    public function test_demo_initiation_is_blocked_outside_local_and_testing(): void
    {
        $originalEnvironment = $this->app['env'];
        $this->app['env'] = 'production';

        try {
            $result = GatewayResult::demo('DEMO-123', ['provider' => 'momo']);

            $this->assertFalse($result->success);
            $this->assertFalse($result->isDemo);
            $this->assertTrue((bool) ($result->meta['demo_blocked'] ?? false));
        } finally {
            $this->app['env'] = $originalEnvironment;
        }
    }

    public function test_manual_cash_result_remains_available_in_production(): void
    {
        $originalEnvironment = $this->app['env'];
        $this->app['env'] = 'production';

        try {
            $result = GatewayResult::demo('CASH-123', ['provider' => 'cash']);

            $this->assertTrue($result->success);
            $this->assertTrue($result->isDemo);
            $this->assertFalse((bool) ($result->meta['demo_blocked'] ?? false));
        } finally {
            $this->app['env'] = $originalEnvironment;
        }
    }

    public function test_demo_status_cannot_reconcile_as_paid_outside_safe_environments(): void
    {
        $originalEnvironment = $this->app['env'];
        $this->app['env'] = 'production';

        try {
            $status = GatewayStatus::paid([], 'DEMO');

            $this->assertFalse($status->isPaid());
            $this->assertSame(GatewayStatus::UNKNOWN, $status->status);
            $this->assertSame('DEMO_BLOCKED', $status->providerStatus);
        } finally {
            $this->app['env'] = $originalEnvironment;
        }
    }

    public function test_unknown_provider_never_falls_back_to_cash_demo(): void
    {
        $factory = new PaymentGatewayFactory(
            new MtnMomoAdapter(),
            new AirtelMoneyAdapter(),
            new PayPalAdapter(),
            new CashDemoAdapter(),
        );

        $this->expectException(\InvalidArgumentException::class);
        $factory->for('provider-mal-orthographie');
    }

    public function test_mobile_money_phone_routes_to_airtel_or_mtn(): void
    {
        $mtn = new MtnMomoAdapter();
        $airtel = new AirtelMoneyAdapter();
        $factory = new PaymentGatewayFactory(
            $mtn,
            $airtel,
            new PayPalAdapter(),
            new CashDemoAdapter(),
        );

        $this->assertSame($mtn, $factory->forMomoPhone('06 800 67 30'));
        $this->assertSame($airtel, $factory->forMomoPhone('05 500 00 00'));
    }
}
