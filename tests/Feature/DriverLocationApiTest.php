<?php

namespace Tests\Feature;

use App\Delivery;
use App\Driver;
use App\Domain\Colis\Enums\ShipmentStatus;
use App\Domain\Colis\Events\ShipmentMissionPresenceUpdated;
use App\Domain\Colis\Models\Shipment;
use App\Domain\Food\Events\FoodMissionPresenceUpdated;
use App\Domain\Transport\Enums\TransportStatus;
use App\Domain\Transport\Events\TransportMissionPresenceUpdated;
use App\Domain\Transport\Models\TransportBooking;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DriverLocationApiTest extends TestCase
{
    use RefreshDatabase;

    private function createDriver(string $suffix = '1'): int
    {
        $restaurantUserId = DB::table('users')->insertGetId([
            'name' => 'Owner GPS ' . $suffix,
            'email' => "restaurant-gps-owner-{$suffix}@example.com",
            'phone' => '060800' . str_pad($suffix, 2, '0', STR_PAD_LEFT),
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
            'slogan' => 'GPS',
            'logo' => null,
            'cover_image' => null,
            'services' => 'both',
            'service_charges' => 0,
            'delivery_charges' => 500,
            'city' => 'Brazzaville',
            'tax' => 0,
            'address' => 'Adresse GPS',
            'latitude' => null,
            'longitude' => null,
            'phone' => '060900' . str_pad($suffix, 2, '0', STR_PAD_LEFT),
            'description' => null,
            'min_order' => 0,
            'avg_delivery_time' => null,
            'delivery_range' => null,
            'admin_commission' => 10,
            'approved' => 1,
            'featured' => 0,
            'account_name' => 'Restaurant GPS',
            'account_number' => 'REST-GPS-' . $suffix,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('drivers')->insertGetId([
            'restaurant_id' => $restaurantId,
            'name' => 'Livreur GPS ' . $suffix,
            'user_name' => 'driver-gps-' . $suffix,
            'phone' => '070900' . str_pad($suffix, 2, '0', STR_PAD_LEFT),
            'email' => "driver-gps-{$suffix}@example.com",
            'image' => null,
            'password' => bcrypt('secret'),
            'hourly_pay' => 0,
            'address' => 'Adresse driver',
            'cnic' => 'CNIC-GPS-' . $suffix,
            'approved' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_update_location_validates_coordinates(): void
    {
        $driverId = $this->createDriver('11');
        $driver = Driver::findOrFail($driverId);

        $this->actingAs($driver, 'driver_api')
            ->postJson('/api/driver/' . $driverId . '/location', [])
            ->assertStatus(422)
            ->assertJson([
                'status' => false,
                'message' => 'Données invalides',
            ])
            ->assertJsonValidationErrors(['latitude', 'longitude']);
    }

    public function test_update_location_returns_not_found_for_unknown_driver(): void
    {
        $driverId = $this->createDriver('12');
        $driver = Driver::findOrFail($driverId);

        $this->actingAs($driver, 'driver_api')
            ->postJson('/api/driver/999999/location', [
                'latitude' => -4.2634,
                'longitude' => 15.2429,
            ])
            ->assertStatus(404)
            ->assertJson([
                'status' => false,
                'message' => 'Livreur non trouvé',
            ]);
    }

    public function test_update_location_updates_driver_coordinates(): void
    {
        $driverId = $this->createDriver('21');
        $driver = Driver::findOrFail($driverId);

        $this->actingAs($driver, 'driver_api')
            ->postJson('/api/driver/' . $driverId . '/location', [
                'latitude' => -4.2634,
                'longitude' => 15.2429,
                'accuracy' => 5.2,
            ])
            ->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => 'Position mise à jour avec succès',
            ]);

        $this->assertDatabaseHas('drivers', [
            'id' => $driverId,
            'status' => 'online',
        ]);
    }

    public function test_update_location_broadcasts_presence_for_active_food_transport_and_colis_missions(): void
    {
        Event::fake([
            FoodMissionPresenceUpdated::class,
            TransportMissionPresenceUpdated::class,
            ShipmentMissionPresenceUpdated::class,
        ]);

        $driverId = $this->createDriver('31');
        $driver = \App\Driver::query()->findOrFail($driverId);
        $customer = User::factory()->create(['type' => 'user']);

        $orderId = DB::table('orders')->insertGetId([
            'order_no' => 'FD-GPS-001',
            'user_id' => $customer->id,
            'restaurant_id' => $driver->restaurant_id,
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
            'restaurant_id' => $driver->restaurant_id,
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

        $this->actingAs($driver, 'driver_api')
            ->postJson('/api/driver/' . $driverId . '/location', [
                'latitude' => -4.2634,
                'longitude' => 15.2429,
                'speed' => 19.5,
            ])->assertStatus(200);

        Event::assertDispatched(FoodMissionPresenceUpdated::class, function (FoodMissionPresenceUpdated $event) {
            $payload = $event->broadcastWith();

            return $payload['order_no'] === 'FD-GPS-001'
                && $payload['location']['lat'] === -4.2634
                && $payload['location']['lng'] === 15.2429
                && $payload['is_live'] === true
                && $payload['presence_state'] === 'live'
                && $payload['presence_stale_after_seconds'] === 120
                && !empty($payload['presence_expires_at']);
        });

        Event::assertDispatched(TransportMissionPresenceUpdated::class, function (TransportMissionPresenceUpdated $event) use ($booking) {
            $payload = $event->broadcastWith();

            return $payload['booking_uuid'] === $booking->uuid
                && $payload['location']['lat'] === -4.2634
                && $payload['location']['lng'] === 15.2429
                && $payload['is_live'] === true
                && $payload['presence_state'] === 'live'
                && $payload['presence_stale_after_seconds'] === 120
                && !empty($payload['presence_expires_at']);
        });

        Event::assertDispatched(ShipmentMissionPresenceUpdated::class, function (ShipmentMissionPresenceUpdated $event) use ($shipment) {
            $payload = $event->broadcastWith();

            return $payload['shipment_id'] === $shipment->id
                && $payload['location']['lat'] === -4.2634
                && $payload['location']['lng'] === 15.2429
                && $payload['is_live'] === true
                && $payload['presence_state'] === 'live'
                && $payload['presence_stale_after_seconds'] === 120
                && !empty($payload['presence_expires_at']);
        });
    }
}
