<?php

namespace Tests\Unit;

use App\Domain\Payment\Adapters\MtnMomoAdapter;
use App\Domain\Payment\ValueObjects\GatewayStatus;
use App\Payment;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MtnMomoAdapterTest extends TestCase
{
    private MtnMomoAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adapter = new MtnMomoAdapter();
        \Illuminate\Support\Facades\Cache::flush();
    }

    // =========================================================================
    // provider()
    // =========================================================================

    /** @test */
    public function provider_identifier_is_momo()
    {
        $this->assertSame('momo', $this->adapter->provider());
    }

    // =========================================================================
    // initiate() — mode démo (config disabled)
    // =========================================================================

    /** @test */
    public function initiate_returns_demo_result_when_mtn_disabled()
    {
        Config::set('external-services.payments.mtn_momo', ['enabled' => false]);

        $payment = $this->makePayment(['id' => 10, 'amount' => 1000, 'currency' => 'XAF']);

        $result = $this->adapter->initiate($payment, ['phone' => '0612345678']);

        $this->assertTrue($result->success);
        $this->assertTrue($result->isDemo);
        $this->assertStringStartsWith('DEMO-mtn-10-', $result->providerReference);
        $this->assertNull($result->error);
    }

    /** @test */
    public function initiate_fails_when_phone_is_missing_from_context()
    {
        Config::set('external-services.payments.mtn_momo', ['enabled' => false]);
        $payment = $this->makePayment(['id' => 11]);

        // Contexte sans clé phone — SmsService::normalizePhone recevra '' et retournera '+242'
        // ce qui déclenchera la logique démo (enabled=false). On teste le cas enabled=true
        // avec un numéro invalide (ni MTN ni Airtel).
        Config::set('external-services.payments.mtn_momo', ['enabled' => true, 'environment' => 'sandbox', 'base_url' => ['sandbox' => 'https://sandbox.example.com'], 'collections' => ['api_user' => 'u', 'api_key' => 'k', 'subscription_key' => 'sk']]);

        Http::fake([
            '*/collection/token/' => Http::response(['access_token' => 'tok', 'expires_in' => 3600]),
            '*'                   => Http::response('', 400),
        ]);

        // Un numéro qui n'est pas reconnu comme MTN (ne commence pas par 06/6)
        $result = $this->adapter->initiate($payment, ['phone' => '0900000000']);

        $this->assertFalse($result->success);
        $this->assertNotEmpty($result->error);
    }

    // =========================================================================
    // initiate() — mode réel (HTTP mocké)
    // =========================================================================

    /** @test */
    public function initiate_succeeds_with_202_response()
    {
        \Illuminate\Support\Facades\Cache::flush();
        // Court-circuiter ConfigService::getCompanyName() pour éviter l'appel DB (SQLite absent)
        \Illuminate\Support\Facades\Cache::put('config_company_name', 'BantuDelice', 3600);
        $this->setMtnConfig();

        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            $url = $request->url();

            if (str_contains($url, '/collection/token/')) {
                return Http::response(['access_token' => 'tok123', 'expires_in' => 3600]);
            }
            if (str_contains($url, '/accountholder/')) {
                return Http::response('true', 200, ['Content-Type' => 'application/json']);
            }
            if (str_contains($url, '/requesttopay')) {
                return Http::response('', 202);
            }

            return Http::response('', 404);
        });

        $payment = $this->makePayment(['id' => 20, 'amount' => 500, 'currency' => 'XAF']);
        $payment->setRelation('order', null);  // évite la requête DB sur order->order_no

        $result = $this->adapter->initiate($payment, ['phone' => '0612345678']);

        $this->assertTrue($result->success, 'Initiation MTN échouée : ' . ($result->error ?? 'pas d\'erreur'));
        $this->assertFalse($result->isDemo);
        $this->assertNotEmpty($result->providerReference);
        $this->assertSame('momo', $result->meta['provider']);
        $this->assertSame('mtn', $result->meta['operator']);
    }

    /** @test */
    public function initiate_returns_failure_when_token_unavailable()
    {
        $this->setMtnConfig();

        Http::fake([
            '*/collection/token/' => Http::response('', 500),
        ]);

        $payment = $this->makePayment(['id' => 21, 'amount' => 500]);

        $result = $this->adapter->initiate($payment, ['phone' => '0612345678']);

        $this->assertFalse($result->success);
        $this->assertNotEmpty($result->error);
    }

    /** @test */
    public function initiate_returns_failure_on_requesttopay_error()
    {
        $this->setMtnConfig();

        Http::fake([
            '*/collection/token/' => Http::response(['access_token' => 'tok', 'expires_in' => 3600]),
            '*/accountholder/*'   => Http::response(true),
            '*/requesttopay'      => Http::response(['message' => 'Erreur MTN'], 400),
        ]);

        $payment = $this->makePayment(['id' => 22, 'amount' => 500]);

        $result = $this->adapter->initiate($payment, ['phone' => '0612345678']);

        $this->assertFalse($result->success);
    }

    // =========================================================================
    // checkStatus()
    // =========================================================================

    /** @test */
    public function check_status_returns_paid_on_successful_response()
    {
        $this->setMtnConfig();

        Http::fake([
            '*/collection/token/'         => Http::response(['access_token' => 'tok', 'expires_in' => 3600]),
            '*/requesttopay/ref-success'  => Http::response(['status' => 'SUCCESSFUL', 'amount' => '500']),
        ]);

        $status = $this->adapter->checkStatus('ref-success');

        $this->assertTrue($status->isPaid());
        $this->assertSame('SUCCESSFUL', $status->providerStatus);
    }

    /** @test */
    public function check_status_returns_failed_with_reason()
    {
        $this->setMtnConfig();

        Http::fake([
            '*/collection/token/'        => Http::response(['access_token' => 'tok', 'expires_in' => 3600]),
            '*/requesttopay/ref-failed'  => Http::response(['status' => 'FAILED', 'reason' => 'NOT_ENOUGH_FUNDS']),
        ]);

        $status = $this->adapter->checkStatus('ref-failed');

        $this->assertTrue($status->isFailed());
        $this->assertSame('NOT_ENOUGH_FUNDS', $status->failureReason);
        $this->assertNotEmpty($status->failureAction);
    }

    /** @test */
    public function check_status_returns_pending_for_pending_response()
    {
        $this->setMtnConfig();

        Http::fake([
            '*/collection/token/'         => Http::response(['access_token' => 'tok', 'expires_in' => 3600]),
            '*/requesttopay/ref-pending'  => Http::response(['status' => 'PENDING']),
        ]);

        $status = $this->adapter->checkStatus('ref-pending');

        $this->assertTrue($status->isPending());
    }

    /** @test */
    public function check_status_returns_unknown_on_http_error()
    {
        $this->setMtnConfig();

        Http::fake([
            '*/collection/token/'        => Http::response(['access_token' => 'tok', 'expires_in' => 3600]),
            '*/requesttopay/ref-err'     => Http::response('', 500),
        ]);

        $status = $this->adapter->checkStatus('ref-err');

        $this->assertSame(GatewayStatus::UNKNOWN, $status->status);
    }

    /** @test */
    public function check_status_returns_paid_in_demo_mode()
    {
        Config::set('external-services.payments.mtn_momo', ['enabled' => false]);

        $status = $this->adapter->checkStatus('any-ref');

        $this->assertTrue($status->isPaid());
        $this->assertSame('DEMO', $status->providerStatus);
    }

    // =========================================================================
    // handleCallback()
    // =========================================================================

    /** @test */
    public function handle_callback_resolves_paid_from_provider_check()
    {
        $this->setMtnConfig();

        Http::fake([
            '*/collection/token/'               => Http::response(['access_token' => 'tok', 'expires_in' => 3600]),
            '*/requesttopay/callback-ref-paid'  => Http::response(['status' => 'SUCCESSFUL']),
        ]);

        $status = $this->adapter->handleCallback([
            'referenceId' => 'callback-ref-paid',
            'status'      => 'SUCCESSFUL',
        ]);

        $this->assertTrue($status->isPaid());
    }

    /** @test */
    public function handle_callback_returns_unknown_when_reference_missing()
    {
        $status = $this->adapter->handleCallback(['status' => 'SUCCESSFUL']);

        $this->assertSame(GatewayStatus::UNKNOWN, $status->status);
    }

    // =========================================================================
    // verifySignature()
    // =========================================================================

    /** @test */
    public function verify_signature_always_returns_true()
    {
        $this->assertTrue($this->adapter->verifySignature([]));
        $this->assertTrue($this->adapter->verifySignature(['any' => 'payload']));
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function makePayment(array $attrs): Payment
    {
        $payment = new Payment(array_merge([
            'id'       => 1,
            'provider' => 'momo',
            'amount'   => 1000,
            'currency' => 'XAF',
            'status'   => 'PENDING',
            'meta'     => [],
        ], $attrs));
        $payment->id = $attrs['id'] ?? 1;

        return $payment;
    }

    private function setMtnConfig(): void
    {
        Config::set('external-services.payments.mtn_momo', [
            'enabled'            => true,
            'environment'        => 'sandbox',
            'target_environment' => 'sandbox',
            'base_url'           => ['sandbox' => 'https://sandbox.momodeveloper.mtn.com'],
            'collections'        => [
                'api_user'         => 'test-user',
                'api_key'          => 'test-key',
                'subscription_key' => 'test-sub-key',
            ],
            'callback_url'       => null,
            'use_callback_header' => false,
        ]);
    }
}
