<?php

namespace Tests\Feature;

use App\Driver;
use App\Services\DriverLocationIngestionService;
use App\Services\MissionPresenceBroadcastService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Mockery;
use Tests\TestCase;

class RealtimeGeolocationPipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_broadcast_authorization_route_is_registered(): void
    {
        $route = collect(Route::getRoutes()->getRoutes())
            ->first(fn ($route) => $route->uri() === 'broadcasting/auth');

        $this->assertNotNull($route, 'La route /broadcasting/auth doit être enregistrée.');
        $this->assertContains('POST', $route->methods());
    }

    public function test_an_old_retried_sample_cannot_overwrite_the_current_position(): void
    {
        $driver = $this->createDriver('stale');
        $broadcasts = Mockery::mock(MissionPresenceBroadcastService::class);
        $broadcasts->shouldReceive('broadcastForDriver')->once();
        $service = new DriverLocationIngestionService($broadcasts);

        $newest = $service->ingest($driver, [
            'latitude' => -4.2634,
            'longitude' => 15.2429,
            'accuracy' => 8,
            'recorded_at' => now()->toIso8601String(),
        ]);

        $stale = $service->ingest($driver->fresh(), [
            'latitude' => -4.3000,
            'longitude' => 15.3000,
            'accuracy' => 15,
            'recorded_at' => now()->subMinutes(3)->toIso8601String(),
        ]);

        $this->assertTrue($newest['accepted']);
        $this->assertFalse($stale['accepted']);
        $this->assertTrue($stale['stale']);
        $this->assertSame(1, DB::table('driver_locations')->where('driver_id', $driver->id)->count());
        $this->assertEqualsWithDelta(-4.2634, (float) $driver->fresh()->latitude, 0.000001);
        $this->assertEqualsWithDelta(15.2429, (float) $driver->fresh()->longitude, 0.000001);
    }

    public function test_an_expired_first_sample_is_rejected(): void
    {
        $driver = $this->createDriver('expired');
        $broadcasts = Mockery::mock(MissionPresenceBroadcastService::class);
        $broadcasts->shouldNotReceive('broadcastForDriver');
        $service = new DriverLocationIngestionService($broadcasts);

        $result = $service->ingest($driver, [
            'latitude' => -4.3000,
            'longitude' => 15.3000,
            'recorded_at' => now()->subMinutes(10)->toIso8601String(),
        ]);

        $this->assertFalse($result['accepted']);
        $this->assertTrue($result['stale']);
        $this->assertDatabaseMissing('driver_locations', ['driver_id' => $driver->id]);
        $this->assertNull($driver->fresh()->latitude);
        $this->assertNull($driver->fresh()->longitude);
    }

    public function test_stationary_heartbeat_with_a_new_timestamp_is_accepted(): void
    {
        $driver = $this->createDriver('heartbeat');
        $broadcasts = Mockery::mock(MissionPresenceBroadcastService::class);
        $broadcasts->shouldReceive('broadcastForDriver')->twice();
        $service = new DriverLocationIngestionService($broadcasts);

        $service->ingest($driver, [
            'latitude' => -4.2634,
            'longitude' => 15.2429,
            'recorded_at' => now()->subMinute()->toIso8601String(),
        ]);

        $heartbeat = $service->ingest($driver->fresh(), [
            'latitude' => -4.2634,
            'longitude' => 15.2429,
            'recorded_at' => now()->toIso8601String(),
        ]);

        $this->assertTrue($heartbeat['accepted']);
        $this->assertFalse($heartbeat['duplicate']);
        $this->assertSame(2, DB::table('driver_locations')->where('driver_id', $driver->id)->count());
    }

    public function test_broadcast_failure_does_not_rollback_a_valid_location(): void
    {
        $driver = $this->createDriver('broadcast-failure');
        $broadcasts = Mockery::mock(MissionPresenceBroadcastService::class);
        $broadcasts->shouldReceive('broadcastForDriver')->once()->andThrow(new \RuntimeException('Soketi unavailable'));
        $service = new DriverLocationIngestionService($broadcasts);

        $result = $service->ingest($driver, [
            'latitude' => -4.2634,
            'longitude' => 15.2429,
            'recorded_at' => now()->toIso8601String(),
        ]);

        $this->assertTrue($result['accepted']);
        $this->assertDatabaseHas('driver_locations', ['driver_id' => $driver->id]);
        $this->assertEqualsWithDelta(-4.2634, (float) $driver->fresh()->latitude, 0.000001);
    }

    public function test_food_tracking_frontend_matches_backend_presence_contract(): void
    {
        $view = file_get_contents(resource_path('views/frontend/track_order.blade.php'));

        $this->assertStringContainsString("private-food.order.' + ORDER_NO + '.presence", $view);
        $this->assertStringContainsString('food.order.presence.updated', $view);
        $this->assertStringContainsString('data.location', $view);
        $this->assertStringNotContainsString('food.driver.location.updated', $view);
        $this->assertStringNotContainsString("presence-food.order.' + ORDER_NO", $view);
    }

    public function test_transport_tracking_frontend_consumes_tracking_presence_and_status_events(): void
    {
        $view = file_get_contents(resource_path('views/booking_detail.blade.php'));

        $this->assertStringContainsString('private-transport.booking.', $view);
        $this->assertStringContainsString('location.updated', $view);
        $this->assertStringContainsString('transport.booking.presence.updated', $view);
        $this->assertStringContainsString('transport.booking.status.updated', $view);
    }

    public function test_colis_tracking_frontend_consumes_presence_and_status_events(): void
    {
        $view = file_get_contents(resource_path('views/frontend/colis/show.blade.php'));

        $this->assertStringContainsString('private-colis.shipment.', $view);
        $this->assertStringContainsString('colis.shipment.presence.updated', $view);
        $this->assertStringContainsString('colis.shipment.status.updated', $view);
    }

    public function test_web_driver_endpoint_uses_shared_ingestion_and_broadcasts(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/DriverLocationWebController.php'));

        $this->assertStringContainsString(DriverLocationIngestionService::class, $controller);
        $this->assertStringContainsString('broadcast: true', $controller);
        $this->assertStringContainsString("'recorded_at'", $controller);
    }

    private function createDriver(string $suffix): Driver
    {
        $restaurantUserId = DB::table('users')->insertGetId([
            'name' => 'Owner GPS ' . $suffix,
            'email' => "owner-gps-{$suffix}@example.com",
            'phone' => '0608' . substr(md5($suffix), 0, 6),
            'password' => bcrypt('secret'),
            'type' => 'restaurant',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $restaurantId = DB::table('restaurants')->insertGetId([
            'user_id' => $restaurantUserId,
            'name' => 'Restaurant GPS ' . $suffix,
            'user_name' => 'restaurant-gps-' . $suffix,
            'email' => "restaurant-gps-{$suffix}@example.com",
            'password' => bcrypt('secret'),
            'services' => 'both',
            'delivery_charges' => 500,
            'city' => 'Brazzaville',
            'tax' => 0,
            'address' => 'Brazzaville',
            'phone' => '0609' . substr(md5($suffix), 0, 6),
            'admin_commission' => 10,
            'approved' => 1,
            'account_name' => 'Restaurant GPS',
            'account_number' => 'REST-GPS-' . $suffix,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $driverId = DB::table('drivers')->insertGetId([
            'restaurant_id' => $restaurantId,
            'name' => 'Livreur GPS ' . $suffix,
            'user_name' => 'driver-gps-' . $suffix,
            'phone' => '0709' . substr(md5($suffix), 0, 6),
            'email' => "driver-gps-{$suffix}@example.com",
            'password' => bcrypt('secret'),
            'hourly_pay' => 0,
            'address' => 'Brazzaville',
            'cnic' => 'CNIC-' . $suffix,
            'approved' => 1,
            'status' => 'online',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Driver::findOrFail($driverId);
    }
}
