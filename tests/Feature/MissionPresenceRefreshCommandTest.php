<?php

namespace Tests\Feature;

use App\Delivery;
use App\Domain\Colis\Enums\ShipmentStatus;
use App\Domain\Colis\Events\ShipmentMissionPresenceUpdated;
use App\Domain\Colis\Models\Shipment;
use App\Domain\Food\Events\FoodMissionPresenceUpdated;
use App\Domain\Transport\Enums\TransportStatus;
use App\Domain\Transport\Events\TransportMissionPresenceUpdated;
use App\Domain\Transport\Models\TransportBooking;
use App\Driver;
use App\DriverLocation;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class MissionPresenceRefreshCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_refresh_presence_command_rebroadcasts_stale_active_missions(): void
    {
        Event::fake([
            FoodMissionPresenceUpdated::class,
            TransportMissionPresenceUpdated::class,
            ShipmentMissionPresenceUpdated::class,
        ]);

        $customer = User::factory()->create(['type' => 'user']);
        $owner = User::factory()->create(['type' => 'restaurant']);

        $restaurantId = DB::table('restaurants')->insertGetId([
            'user_id' => $owner->id,
            'name' => 'Hub mission refresh',
            'user_name' => 'hub-mission-refresh',
            'email' => 'hub-mission-refresh@example.com',
            'password' => bcrypt('secret'),
            'services' => 'both',
            'delivery_charges' => 1000,
            'city' => 'Brazzaville',
            'tax' => 0,
            'address' => 'Centre ville',
            'latitude' => -4.2634,
            'longitude' => 15.2429,
            'phone' => '0600012999',
            'description' => 'Hub',
            'min_order' => 0,
            'admin_commission' => 0,
            'approved' => 1,
            'featured' => 0,
            'account_name' => 'Hub mission refresh',
            'account_number' => 'ACC-MISSION-REFRESH',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $driver = Driver::create([
            'restaurant_id' => $restaurantId,
            'name' => 'Operateur refresh',
            'user_name' => 'operateur-refresh',
            'hourly_pay' => 0,
            'email' => 'operateur-refresh@example.com',
            'cnic' => 'CNIC-REFRESH-001',
            'password' => bcrypt('secret'),
            'phone' => '0500012999',
            'image' => null,
            'address' => 'Brazzaville',
            'latitude' => -4.2635,
            'longitude' => 15.2430,
            'status' => 'online',
            'approved' => true,
        ]);

        DriverLocation::create([
            'driver_id' => $driver->id,
            'latitude' => -4.2636,
            'longitude' => 15.2431,
            'speed' => 12,
            'timestamp' => now()->subMinutes(6),
        ]);

        $orderId = DB::table('orders')->insertGetId([
            'order_no' => 'FD-REFRESH-001',
            'user_id' => $customer->id,
            'restaurant_id' => $restaurantId,
            'driver_id' => $driver->id,
            'total_items' => 1,
            'offer_discount' => 0,
            'tax' => 0,
            'delivery_charges' => 1000,
            'sub_total' => 3000,
            'total' => 4000,
            'admin_commission' => 0,
            'restaurant_commission' => 0,
            'driver_tip' => 0,
            'status' => 'assign',
            'business_status' => 'driver_assigned',
            'delivery_address' => 'Avenue de la Paix',
            'd_lat' => '-4.27',
            'd_lng' => '15.28',
            'ordered_time' => now(),
            'delivered_time' => now(),
            'fulfillment_mode' => 'delivery',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Delivery::create([
            'order_id' => $orderId,
            'restaurant_id' => $restaurantId,
            'driver_id' => $driver->id,
            'status' => 'ASSIGNED',
            'delivery_fee' => 1000,
            'assigned_at' => now(),
        ]);

        $booking = TransportBooking::factory()->create([
            'user_id' => $customer->id,
            'driver_id' => $driver->id,
            'status' => TransportStatus::ASSIGNED,
        ]);

        $shipment = Shipment::factory()->create([
            'customer_id' => $customer->id,
            'assigned_courier_id' => $driver->id,
            'status' => ShipmentStatus::OUT_FOR_DELIVERY,
        ]);

        $this->artisan('missions:refresh-presence')
            ->expectsOutput('Food: 1')
            ->expectsOutput('Transport: 1')
            ->expectsOutput('Colis: 1')
            ->expectsOutput('Total: 3')
            ->assertExitCode(0);

        Event::assertDispatched(FoodMissionPresenceUpdated::class, function (FoodMissionPresenceUpdated $event) {
            $payload = $event->broadcastWith();

            return $payload['order_no'] === 'FD-REFRESH-001'
                && $payload['presence_state'] === 'stale'
                && $payload['is_live'] === false
                && $payload['presence_freshness_seconds'] >= 360;
        });

        Event::assertDispatched(TransportMissionPresenceUpdated::class, function (TransportMissionPresenceUpdated $event) use ($booking) {
            $payload = $event->broadcastWith();

            return $payload['booking_uuid'] === $booking->uuid
                && $payload['presence_state'] === 'stale'
                && $payload['is_live'] === false
                && $payload['presence_freshness_seconds'] >= 360;
        });

        Event::assertDispatched(ShipmentMissionPresenceUpdated::class, function (ShipmentMissionPresenceUpdated $event) use ($shipment) {
            $payload = $event->broadcastWith();

            return $payload['shipment_id'] === $shipment->id
                && $payload['presence_state'] === 'stale'
                && $payload['is_live'] === false
                && $payload['presence_freshness_seconds'] >= 360;
        });
    }
}
