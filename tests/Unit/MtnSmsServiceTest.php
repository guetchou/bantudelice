<?php

namespace Tests\Unit;

use App\Services\MtnSmsService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MtnSmsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('external-services.notifications.mtn_sms', [
            'enabled' => true,
            'api_url' => 'https://sms.mtncongo.net/api/sms/',
            'token' => 'test-token',
            'authorization_prefix' => 'Token',
            'sender_id' => 'BantuDelice',
            'callback_url' => null,
            'timeout' => 15,
            'retry_times' => 0,
            'retry_sleep_ms' => 0,
        ]);
    }

    public function test_it_sends_a_tinda_sms_with_expected_headers_and_payload(): void
    {
        Http::fake([
            'https://sms.mtncongo.net/api/sms/' => Http::response([
                'resultat' => 'envoyé (coût: 1 crédit)',
                'status' => '200',
                'id' => '10',
            ], 200),
        ]);

        $result = app(MtnSmsService::class)->sendSms('+242068463499', 'Message test', [
            'external_id' => 123456,
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('10', $result['message_id']);
        $this->assertSame('mtn_tinda', $result['provider']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://sms.mtncongo.net/api/sms/'
                && $request->hasHeader('Authorization', 'Token test-token')
                && $request['msg'] === 'Message test'
                && $request['sender'] === 'BantuDelice'
                && $request['receivers'] === '242068463499'
                && $request['externalId'] === 123456;
        });
    }

    public function test_it_returns_api_validation_errors_without_claiming_success(): void
    {
        Http::fake([
            'https://sms.mtncongo.net/api/sms/' => Http::response([
                'resultat' => 'Erreur MSISDN: 24206846349',
                'detail' => 'format numéro incorrect',
                'status' => '404',
            ], 404),
        ]);

        $result = app(MtnSmsService::class)->sendSms('068463499', 'Message test');

        $this->assertFalse($result['success']);
        $this->assertSame('404', $result['status']);
        $this->assertSame('format numéro incorrect', $result['error']);
    }

    public function test_it_queries_delivery_status(): void
    {
        Http::fake([
            'https://sms.mtncongo.net/api/sms/' => Http::response([
                'resultat' => ['242068463499, 1, Livré au téléphone'],
                'status' => '200',
                'externalId' => 15,
            ], 200),
        ]);

        $result = app(MtnSmsService::class)->getStatus('26');

        $this->assertTrue($result['success']);
        $this->assertSame(['242068463499, 1, Livré au téléphone'], $result['result']);

        Http::assertSent(fn ($request) => $request['op'] === 'status' && $request['id'] === '26');
    }

    public function test_it_rejects_more_than_one_thousand_receivers_before_http_call(): void
    {
        Http::fake();

        $numbers = array_map(
            fn (int $index) => '24206'.str_pad((string) $index, 7, '0', STR_PAD_LEFT),
            range(1, 1001)
        );

        $result = app(MtnSmsService::class)->sendSms($numbers, 'Message test');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('1000', $result['error']);
        Http::assertNothingSent();
    }
}
