<?php

namespace Tests\Feature;

use App\User;
use App\UserToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PushDevicesApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_push_device_routes_require_authentication(): void
    {
        $this->postJson('/api/push/devices', [
            'device_token' => 'token-public-1',
        ])->assertStatus(401);

        $this->deleteJson('/api/push/devices', [
            'device_token' => 'token-public-1',
        ])->assertStatus(401);
    }

    public function test_push_device_store_validates_required_device_token(): void
    {
        $user = User::factory()->create(['type' => 'user']);

        $this->actingAs($user, 'api')
            ->postJson('/api/push/devices', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['device_token']);
    }

    public function test_user_can_register_and_deactivate_push_device(): void
    {
        $user = User::factory()->create(['type' => 'user']);

        $this->actingAs($user, 'api')
            ->postJson('/api/push/devices', [
                'device_token' => 'token-user-123',
                'platform' => 'android',
                'locale' => 'fr',
                'site_key' => 'bantudelice',
                'modules' => ['transport', 'colis'],
                'audio_enabled' => false,
                'interactive_enabled' => true,
                'realtime_enabled' => true,
            ])
            ->assertStatus(201)
            ->assertJson([
                'status' => true,
                'message' => 'Appareil enregistré pour les notifications push.',
                'subscriptions' => [
                    'modules' => ['transport', 'colis'],
                    'audio_enabled' => false,
                    'interactive_enabled' => true,
                    'realtime_enabled' => true,
                ],
            ]);

        $this->assertDatabaseHas('user_tokens', [
            'user_id' => $user->id,
            'device_tokens' => 'token-user-123',
        ]);

        $this->actingAs($user, 'api')
            ->deleteJson('/api/push/devices', [
                'device_token' => 'token-user-123',
            ])
            ->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => 'Appareil désactivé.',
            ]);

        $token = UserToken::where('user_id', $user->id)
            ->where('device_tokens', 'token-user-123')
            ->first();

        $this->assertNotNull($token);
        $this->assertFalse((bool) $token->active);
        $this->assertSame(['transport', 'colis'], $token->metadata['subscriptions']['modules'] ?? null);
    }
}
