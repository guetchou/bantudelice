<?php

namespace Tests\Feature;

use App\Driver;
use App\Services\DriverLocationIngestionService;
use App\Services\MissionPresenceBroadcastService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class DriverLocationClockSkewTest extends TestCase
{
    use RefreshDatabase;

    public function test_future_sample_is_clamped_and_does_not_freeze_later_locations(): void
    {
        $driver = $this->createDriver('future-clock');
        $broadcasts = Mockery::mock(MissionPresenceBroadcastService::class);
        $broadcasts->shouldReceive('broadcastForDriver')->twice();
        $service = new DriverLocationIngestionService($broadcasts);

        $future = $service->ingest($driver, [
            'latitude' => -4.2634,
            'longitude' => 15.2429,
            'recorded_at' => now()->addMinute()->toIso8601String(),
        ]);

        $current = $service->ingest($driver->fresh(), [
            'latitude' => -4.2700,
            'longitude' => 15.2500,
            'recorded_at' => now()->toIso8601String(),
        ]);

        $this->assertTrue($future['accepted']);
        $this->assertTrue($future['location']->timestamp->lte(now()->addSecond()));
        $this->assertTrue($current['accepted']);
        $this->assertFalse($current['stale']);
        $this->assertSame(2, DB::table('driver_locations')->where('driver_id', $driver->id)->count());
        $this->assertEqualsWithDelta(-4.2700, (float) $driver->fresh()->latitude, 0.000001);
        $this->assertEqualsWithDelta(15.2500, (float) $driver->fresh()->longitude, 0.000001);
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
