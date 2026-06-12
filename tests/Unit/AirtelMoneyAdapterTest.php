<?php

namespace Tests\Unit;

use App\Domain\Payment\Adapters\AirtelMoneyAdapter;
use App\Domain\Payment\ValueObjects\GatewayStatus;
use App\Payment;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AirtelMoneyAdapterTest extends TestCase
{
    private AirtelMoneyAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adapter = new AirtelMoneyAdapter();
    }

    // =========================================================================
    // provider()
    // =========================================================================

    /** @test */
    public function provider_identifier_is_airtel()
    {
        $this->assertSame('airtel', $this->adapter->provider());
    }

    // =========================================================================
    // initiate() — mode démo
    // =========================================================================

    /** @test */
    public function initiate_returns_demo_when_disabled()
    {
        Config::set('external-services.payments.airtel_money', ['enabled' => false]);

        $payment = $this->makePayment(['id' => 30]);

        $result = $this->adapter->initiate($payment, ['phone' => '0512345678']);

        $this->assertTrue($result->success);
        $this->assertTrue($result->isDemo);
        $this->assertStringStartsWith('DEMO-airtel-30-', $result->providerReference);
    }

    /** @test */
    public function initiate_fails_when_phone_context_missing_and_airtel_enabled()
    {
        $this->setAirtelConfig();

        Http::fake([
            '*/auth/oauth2/token'    => Http::response(['access_token' => 'tok', 'expires_in' => 3600]),
            '*/merchant/v1/payments' => Http::response(['status' => ['success' => false]], 400),
        ]);

        $payment = $this->makePayment(['id' => 31]);

        // Numéro non reconnu comme Airtel (ne commence pas par 05)
        $result = $this->adapter->initiate($payment, ['phone' => '0900000000']);

        $this->assertFalse($result->success);
    }

    // =========================================================================
    // initiate() — HTTP mocké
    // =========================================================================

    /** @test */
    public function initiate_succeeds_with_valid_airtel_response()
    {
        $this->setAirtelConfig();

        Http::fake([
            'https://openapiuat.airtel.africa/auth/oauth2/token'    => Http::response(['access_token' => 'airtel-tok', 'expires_in' => 3600]),
            'https://openapiuat.airtel.africa/merchant/v1/payments/' => Http::response([
                'status' => ['success' => true],
                'data'   => ['transaction' => ['id' => 'AIRTEL-TXN-001']],
            ]),
        ]);

        $payment = $this->makePayment(['id' => 32, 'amount' => 1500]);

        $result = $this->adapter->initiate($payment, ['phone' => '0512345678']);

        $this->assertTrue($result->success);
        $this->assertFalse($result->isDemo);
        $this->assertSame('AIRTEL-TXN-001', $result->providerReference);
        $this->assertSame('airtel', $result->meta['provider']);
    }

    /** @test */
    public function initiate_returns_failure_when_token_fails()
    {
        $this->setAirtelConfig();

        Http::fake([
            '*/auth/oauth2/token' => Http::response('', 401),
        ]);

        $payment = $this->makePayment(['id' => 33]);

        $result = $this->adapter->initiate($payment, ['phone' => '0512345678']);

        $this->assertFalse($result->success);
    }

    /** @test */
    public function initiate_returns_failure_when_airtel_returns_not_success()
    {
        $this->setAirtelConfig();

        Http::fake([
            '*/auth/oauth2/token'    => Http::response(['access_token' => 'tok', 'expires_in' => 3600]),
            '*/merchant/v1/payments' => Http::response(['status' => ['success' => false]]),
        ]);

        $payment = $this->makePayment(['id' => 34]);

        $result = $this->adapter->initiate($payment, ['phone' => '0512345678']);

        $this->assertFalse($result->success);
    }

    // =========================================================================
    // checkStatus()
    // =========================================================================

    /** @test */
    public function check_status_maps_ts_to_paid()
    {
        $this->setAirtelConfig();

        Http::fake([
            '*/auth/oauth2/token'    => Http::response(['access_token' => 'tok', 'expires_in' => 3600]),
            '*/standard/v1/payments/*' => Http::response([
                'data' => ['transaction' => ['status' => 'TS', 'message' => 'Success']],
            ]),
        ]);

        $status = $this->adapter->checkStatus('AIRTEL-TXN-001');

        $this->assertTrue($status->isPaid());
        $this->assertSame('TS', $status->providerStatus);
    }

    /** @test */
    public function check_status_maps_tf_to_failed()
    {
        $this->setAirtelConfig();

        Http::fake([
            '*/auth/oauth2/token'      => Http::response(['access_token' => 'tok', 'expires_in' => 3600]),
            '*/standard/v1/payments/*' => Http::response([
                'data' => ['transaction' => ['status' => 'TF', 'message' => 'Transaction Failed']],
            ]),
        ]);

        $status = $this->adapter->checkStatus('AIRTEL-TXN-002');

        $this->assertTrue($status->isFailed());
    }

    /** @test */
    public function check_status_maps_ta_and_tip_to_pending()
    {
        $this->setAirtelConfig();

        foreach (['TA', 'TIP'] as $rawStatus) {
            Http::fake([
                '*/auth/oauth2/token'      => Http::response(['access_token' => 'tok', 'expires_in' => 3600]),
                '*/standard/v1/payments/*' => Http::response([
                    'data' => ['transaction' => ['status' => $rawStatus]],
                ]),
            ]);

            $status = $this->adapter->checkStatus('ref');
            $this->assertTrue($status->isPending(), "Expected PENDING for Airtel status {$rawStatus}");
        }
    }

    /** @test */
    public function check_status_returns_paid_in_demo_mode()
    {
        Config::set('external-services.payments.airtel_money', ['enabled' => false]);

        $status = $this->adapter->checkStatus('any');

        $this->assertTrue($status->isPaid());
    }

    // =========================================================================
    // handleCallback()
    // =========================================================================

    /** @test */
    public function handle_callback_ts_returns_paid()
    {
        $status = $this->adapter->handleCallback([
            'transaction' => ['id' => 'T123', 'status' => 'TS'],
        ]);

        $this->assertTrue($status->isPaid());
        $this->assertSame('TS', $status->providerStatus);
    }

    /** @test */
    public function handle_callback_tf_returns_failed()
    {
        $status = $this->adapter->handleCallback([
            'transaction' => ['id' => 'T124', 'status' => 'TF'],
        ]);

        $this->assertTrue($status->isFailed());
    }

    /** @test */
    public function handle_callback_missing_status_returns_unknown()
    {
        $status = $this->adapter->handleCallback(['transaction' => ['id' => 'T125']]);

        $this->assertSame(GatewayStatus::UNKNOWN, $status->status);
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
            'provider' => 'airtel',
            'amount'   => 1000,
            'currency' => 'XAF',
            'status'   => 'PENDING',
            'meta'     => [],
        ], $attrs));
        $payment->id = $attrs['id'] ?? 1;

        return $payment;
    }

    private function setAirtelConfig(): void
    {
        Config::set('external-services.payments.airtel_money', [
            'enabled'     => true,
            'environment' => 'sandbox',
            'base_url'    => ['sandbox' => 'https://openapiuat.airtel.africa'],
            'client_id'   => 'test-client',
            'client_secret' => 'test-secret',
            'country'     => 'CG',
            'currency'    => 'XAF',
        ]);
    }
}
