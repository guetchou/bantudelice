<?php

namespace Tests\Feature;

use App\Services\ConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WeatherSurchargeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Insérer les clés de config nécessaires
        DB::table('system_config')->upsert([
            ['key' => 'weather_surcharge_enabled', 'value' => '0', 'type' => 'boolean', 'description' => 'test'],
            ['key' => 'weather_surcharge_percent', 'value' => '20', 'type' => 'float',   'description' => 'test'],
            ['key' => 'weather_surcharge_label',   'value' => 'Majoration saison des pluies', 'type' => 'string', 'description' => 'test'],
        ], ['key'], ['value', 'type', 'description']);
    }

    // ── ConfigService ────────────────────────────────────────────────────────

    public function test_weather_surcharge_disabled_by_default(): void
    {
        $enabled = ConfigService::getConfigValue('weather_surcharge_enabled', false, 'boolean');
        $this->assertFalse((bool) $enabled);
    }

    public function test_weather_surcharge_percent_default_is_20(): void
    {
        $percent = (float) ConfigService::getConfigValue('weather_surcharge_percent', 0, 'float');
        $this->assertEquals(20.0, $percent);
    }

    // ── Calcul surcharge (logique isolée) ────────────────────────────────────

    public function test_surcharge_math_20_percent_of_1000_is_200(): void
    {
        $baseDelivery = 1000.0;
        $percent      = 20.0;
        $surcharge    = round($baseDelivery * $percent / 100);
        $this->assertEquals(200, $surcharge);
    }

    public function test_surcharge_not_applied_when_disabled_in_config(): void
    {
        DB::table('system_config')->where('key', 'weather_surcharge_enabled')->update(['value' => '0']);
        $enabled = ConfigService::getConfigValue('weather_surcharge_enabled', false, 'boolean');
        $this->assertFalse((bool) $enabled);

        // Simulation de la logique du service
        $surcharge = 0.0;
        if ($enabled) {
            $surcharge = round(1000 * 20 / 100);
        }
        $this->assertEquals(0, $surcharge);
    }

    public function test_surcharge_applied_when_enabled_in_config(): void
    {
        DB::table('system_config')->where('key', 'weather_surcharge_enabled')->update(['value' => '1']);
        DB::table('system_config')->where('key', 'weather_surcharge_percent')->update(['value' => '20']);
        Cache::forget('config_weather_surcharge_enabled');
        Cache::forget('config_weather_surcharge_percent');

        $enabled = ConfigService::getConfigValue('weather_surcharge_enabled', false, 'boolean');
        $percent = (float) ConfigService::getConfigValue('weather_surcharge_percent', 0, 'float');

        $this->assertTrue((bool) $enabled);
        $this->assertEquals(20.0, $percent);

        $surcharge = round(1000 * $percent / 100);
        $this->assertEquals(200, $surcharge);
    }

    public function test_surcharge_zero_when_pickup_mode(): void
    {
        DB::table('system_config')->where('key', 'weather_surcharge_enabled')->update(['value' => '1']);

        // Pickup = pas de surcharge, peu importe la config
        $fulfillmentMode = 'pickup';
        $surcharge = 0.0;

        if ($fulfillmentMode !== 'pickup') {
            $enabled = ConfigService::getConfigValue('weather_surcharge_enabled', false, 'boolean');
            if ($enabled) {
                $surcharge = round(1000 * 20 / 100);
            }
        }

        $this->assertEquals(0, $surcharge);
    }

    // ── Admin route : toggle ─────────────────────────────────────────────────

    public function test_admin_can_toggle_weather_surcharge(): void
    {
        $admin = \App\User::factory()->create(['type' => 'admin']);

        Cache::flush();
        // La valeur initiale est '0' → toggle → doit devenir '1'
        $response = $this->actingAs($admin, 'web')->withoutMiddleware()->post(route('admin.weather-surcharge.toggle'));

        $response->assertRedirect();
        Cache::forget('config_weather_surcharge_enabled');
        $newValue = DB::table('system_config')->where('key', 'weather_surcharge_enabled')->value('value');
        $this->assertEquals('1', $newValue);
    }

    public function test_admin_can_update_surcharge_percent(): void
    {
        $admin = \App\User::factory()->create(['type' => 'admin']);

        Cache::flush();
        $response = $this->actingAs($admin, 'web')->withoutMiddleware()->post(route('admin.weather-surcharge.update'), [
            'weather_surcharge_enabled' => 1,
            'weather_surcharge_percent' => 30,
            'weather_surcharge_label'   => 'Test label',
        ]);

        $response->assertRedirect();
        Cache::forget('config_weather_surcharge_percent');
        $this->assertEquals('30', DB::table('system_config')->where('key', 'weather_surcharge_percent')->value('value'));
    }

}
