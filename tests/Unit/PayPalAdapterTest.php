<?php

namespace Tests\Unit;

use App\Domain\Payment\Adapters\PayPalAdapter;
use App\Domain\Payment\ValueObjects\GatewayStatus;
use App\Payment;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PayPalAdapterTest extends TestCase
{
    private PayPalAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adapter = new PayPalAdapter();
    }

    // =========================================================================
    // provider()
    // =========================================================================

    /** @test */
    public function provider_identifier_is_paypal()
    {
        $this->assertSame('paypal', $this->adapter->provider());
    }

    // =========================================================================
    // initiate() — mode démo (config manquante)
    // =========================================================================

    /** @test */
    public function initiate_returns_demo_when_credentials_missing()
    {
        Config::set('external-services.payments.paypal', [
            'client_id' => '',
            'secret'    => '',
            'mode'      => 'sandbox',
            'currency'  => 'XAF',
        ]);

        $payment = $this->makePayment(['id' => 40]);

        $result = $this->adapter->initiate($payment, []);

        $this->assertTrue($result->success);
        $this->assertTrue($result->isDemo);
        $this->assertStringStartsWith('PAYPAL-DEMO-40-', $result->providerReference);
        $this->assertNotNull($result->redirectUrl);
    }

    // =========================================================================
    // initiate() — HTTP mocké
    // =========================================================================

    /** @test */
    public function initiate_succeeds_with_valid_paypal_response()
    {
        $this->setPayPalConfig();

        Http::fake([
            '*/v1/oauth2/token'       => Http::response(['access_token' => 'pp-tok', 'expires_in' => 3600]),
            '*/v2/checkout/orders'    => Http::response([
                'id'     => 'PP-ORDER-001',
                'status' => 'CREATED',
                'links'  => [
                    ['rel' => 'approve', 'href' => 'https://paypal.com/approve?token=PP-ORDER-001'],
                    ['rel' => 'self',    'href' => 'https://api.paypal.com/v2/checkout/orders/PP-ORDER-001'],
                ],
            ]),
        ]);

        $payment = $this->makePayment(['id' => 41, 'amount' => 2000]);

        $result = $this->adapter->initiate($payment, [
            'paypal_return_url' => 'https://example.com/return',
            'paypal_cancel_url' => 'https://example.com/cancel',
        ]);

        $this->assertTrue($result->success);
        $this->assertFalse($result->isDemo);
        $this->assertSame('PP-ORDER-001', $result->providerReference);
        $this->assertSame('https://paypal.com/approve?token=PP-ORDER-001', $result->redirectUrl);
        $this->assertSame('paypal', $result->meta['provider']);
    }

    /** @test */
    public function initiate_returns_failure_when_token_fails()
    {
        $this->setPayPalConfig();

        Http::fake([
            '*/v1/oauth2/token' => Http::response('', 401),
        ]);

        $result = $this->adapter->initiate($this->makePayment(['id' => 42]), [
            'paypal_return_url' => 'https://example.com/return',
            'paypal_cancel_url' => 'https://example.com/cancel',
        ]);

        $this->assertFalse($result->success);
    }

    /** @test */
    public function initiate_returns_failure_when_orders_api_fails()
    {
        $this->setPayPalConfig();

        Http::fake([
            '*/v1/oauth2/token'    => Http::response(['access_token' => 'tok', 'expires_in' => 3600]),
            '*/v2/checkout/orders' => Http::response(['error' => 'BAD_REQUEST'], 400),
        ]);

        $result = $this->adapter->initiate($this->makePayment(['id' => 43]), [
            'paypal_return_url' => 'https://example.com/return',
            'paypal_cancel_url' => 'https://example.com/cancel',
        ]);

        $this->assertFalse($result->success);
    }

    /** @test */
    public function initiate_returns_failure_when_approval_url_missing()
    {
        $this->setPayPalConfig();

        Http::fake([
            '*/v1/oauth2/token'    => Http::response(['access_token' => 'tok', 'expires_in' => 3600]),
            '*/v2/checkout/orders' => Http::response([
                'id'    => 'PP-ORDER-002',
                'links' => [['rel' => 'self', 'href' => 'https://api.paypal.com/v2/checkout/orders/PP-ORDER-002']],
            ]),
        ]);

        $result = $this->adapter->initiate($this->makePayment(['id' => 44]), [
            'paypal_return_url' => 'https://example.com/return',
            'paypal_cancel_url' => 'https://example.com/cancel',
        ]);

        $this->assertFalse($result->success);
    }

    // =========================================================================
    // checkStatus()
    // =========================================================================

    /** @test */
    public function check_status_returns_pending_awaiting_capture()
    {
        $status = $this->adapter->checkStatus('PP-ORDER-001');

        $this->assertTrue($status->isPending());
        $this->assertSame('AWAITING_CAPTURE', $status->providerStatus);
    }

    // =========================================================================
    // capture()
    // =========================================================================

    /** @test */
    public function capture_returns_paid_on_completed_status()
    {
        $this->setPayPalConfig();

        Http::fake([
            '*/v1/oauth2/token'              => Http::response(['access_token' => 'tok', 'expires_in' => 3600]),
            '*/v2/checkout/orders/*/capture' => Http::response(['status' => 'COMPLETED', 'id' => 'CAP-001']),
        ]);

        $status = $this->adapter->capture('PP-ORDER-001');

        $this->assertTrue($status->isPaid());
        $this->assertSame('COMPLETED', $status->providerStatus);
    }

    /** @test */
    public function capture_returns_failed_on_non_completed_status()
    {
        $this->setPayPalConfig();

        Http::fake([
            '*/v1/oauth2/token'              => Http::response(['access_token' => 'tok', 'expires_in' => 3600]),
            '*/v2/checkout/orders/*/capture' => Http::response(['status' => 'PENDING']),
        ]);

        $status = $this->adapter->capture('PP-ORDER-003');

        $this->assertTrue($status->isFailed());
    }

    /** @test */
    public function capture_throws_when_credentials_missing()
    {
        Config::set('external-services.payments.paypal', ['client_id' => '', 'secret' => '']);

        $this->expectException(\RuntimeException::class);
        $this->adapter->capture('PP-ORDER-004');
    }

    // =========================================================================
    // handleCallback()
    // =========================================================================

    /** @test */
    public function handle_callback_completed_returns_paid()
    {
        $status = $this->adapter->handleCallback(['status' => 'COMPLETED']);

        $this->assertTrue($status->isPaid());
    }

    /** @test */
    public function handle_callback_failed_returns_failed()
    {
        $status = $this->adapter->handleCallback(['status' => 'FAILED']);

        $this->assertTrue($status->isFailed());
    }

    /** @test */
    public function handle_callback_unknown_status_returns_pending()
    {
        $status = $this->adapter->handleCallback(['status' => 'CREATED']);

        $this->assertTrue($status->isPending());
    }

    // =========================================================================
    // verifySignature()
    // =========================================================================

    /** @test */
    public function verify_signature_always_returns_true()
    {
        $this->assertTrue($this->adapter->verifySignature([]));
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function makePayment(array $attrs): Payment
    {
        $payment = new Payment(array_merge([
            'id'       => 1,
            'provider' => 'paypal',
            'amount'   => 1000,
            'currency' => 'XAF',
            'status'   => 'PENDING',
            'meta'     => [],
        ], $attrs));
        $payment->id = $attrs['id'] ?? 1;

        return $payment;
    }

    private function setPayPalConfig(): void
    {
        Config::set('external-services.payments.paypal', [
            'client_id' => 'pp-client-id',
            'secret'    => 'pp-secret',
            'mode'      => 'sandbox',
            'currency'  => 'XAF',
        ]);
    }
}
