<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyBridgeSignature;
use App\Http\Controllers\Api\MobileMoneyBridgeController;
use App\Services\MobileMoneyBridgeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class MobileMoneyBridgeApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('mobile-money-bridge.enabled', true);
        config()->set('mobile-money-bridge.tolerance_seconds', 300);
        config()->set('mobile-money-bridge.clients', [
            'bridge-test' => [
                'name' => 'Bridge Test',
                'secret' => 'bridge-secret-test',
            ],
        ]);
    }

    private function signedHeaders(string $method, string $path, string $body = '', ?string $secret = null): array
    {
        $timestamp = (string) now()->timestamp;
        $secret = $secret ?? 'bridge-secret-test';
        $payload = implode("\n", [
            $timestamp,
            strtoupper($method),
            $path,
            $body,
        ]);

        return [
            'X-Bridge-Key' => 'bridge-test',
            'X-Bridge-Timestamp' => $timestamp,
            'X-Bridge-Signature' => hash_hmac('sha256', $payload, $secret),
        ];
    }

    public function test_bridge_routes_require_signature_headers(): void
    {
        $this->postJson('/api/bridge/mobile-money/payments', [])
            ->assertStatus(401)
            ->assertJson([
                'status' => false,
                'message' => 'En-têtes de signature passerelle manquants.',
            ]);
    }

    public function test_bridge_routes_reject_invalid_signature(): void
    {
        $body = json_encode([
            'external_reference' => 'EXT-1',
            'amount' => 1000,
            'phone' => '060000000',
        ], JSON_UNESCAPED_SLASHES);

        $this->withHeaders($this->signedHeaders('POST', '/api/bridge/mobile-money/payments', $body, 'wrong-secret'))
            ->postJson('/api/bridge/mobile-money/payments', [
                'external_reference' => 'EXT-1',
                'amount' => 1000,
                'phone' => '060000000',
            ])
            ->assertStatus(401)
            ->assertJson([
                'status' => false,
                'message' => 'Signature passerelle invalide.',
            ]);
    }

    public function test_bridge_show_returns_not_found_for_unknown_reference(): void
    {
        $request = Request::create('/api/bridge/mobile-money/payments/MMB-99999999', 'GET');
        $request->headers->set('Accept', 'application/json');
        $request->attributes->set('bridge_client', [
            'key' => 'bridge-test',
            'name' => 'Bridge Test',
        ]);

        $controller = new MobileMoneyBridgeController(app(MobileMoneyBridgeService::class));

        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);

        $controller->show($request, 'MMB-99999999');
    }

    public function test_bridge_store_validates_required_fields_with_valid_signature(): void
    {
        $body = json_encode([], JSON_UNESCAPED_SLASHES);

        $this->withHeaders($this->signedHeaders('POST', '/api/bridge/mobile-money/payments', $body))
            ->postJson('/api/bridge/mobile-money/payments', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['external_reference', 'amount', 'phone']);
    }
}
