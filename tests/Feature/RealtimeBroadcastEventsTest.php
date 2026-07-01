<?php

namespace Tests\Feature;

use App\Delivery;
use App\Domain\Colis\Enums\ShipmentStatus;
use App\Domain\Colis\Events\ShipmentMissionPresenceUpdated;
use App\Domain\Colis\Events\ShipmentStatusUpdated;
use App\Domain\Colis\Models\Shipment;
use App\Domain\Colis\Services\ShipmentStateMachine;
use App\Domain\Food\Events\FoodDriverOrderUpdated;
use App\Domain\Food\Events\FoodMissionPresenceUpdated;
use App\Domain\Food\Events\FoodOrderStatusUpdated;
use App\Domain\Food\Events\FoodRestaurantOrderUpdated;
use App\Domain\Transport\Enums\TransportStatus;
use App\Domain\Transport\Events\TransportBookingStatusUpdated;
use App\Domain\Transport\Events\TransportMissionPresenceUpdated;
use App\Driver;
use App\DriverLocation;
use App\Order;
use App\Services\FoodOrderStateMachineService;
use App\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RealtimeBroadcastEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_food_state_machine_dispatches_realtime_events_for_customer_restaurant_and_driver(): void
    {
        Event::fake([
            FoodOrderStatusUpdated::class,
            FoodRestaurantOrderUpdated::class,
            FoodDriverOrderUpdated::class,
            FoodMissionPresenceUpdated::class,
        ]);

        $customer = User::factory()->create(['type' => 'user']);
        $restaurantOwner = User::factory()->create(['type' => 'restaurant']);
        $restaurantId = DB::table('restaurants')->insertGetId([
            'user_id' => $restaurantOwner->id,
            'name' => 'Restaurant realtime broadcast',
            'user_name' => 'restaurant-realtime-broadcast',
            'email' => 'restaurant-realtime-broadcast@example.com',
            'password' => bcrypt('secret'),
            'services' => 'food',
            'delivery_charges' => 1000,
            'city' => 'Brazzaville',
            'tax' => 5,
            'address' => 'Avenue de la Paix',
            'latitude' => -4.2634,
            'longitude' => 15.2429,
            'phone' => '0600012001',
            'description' => 'Test',
            'min_order' => 1000,
            'admin_commission' => 5,
            'approved' => 1,
            'featured' => 0,
            'account_name' => 'Restaurant realtime broadcast',
            'account_number' => 'ACC-REST-RT-BC',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $driver = Driver::create([
            'restaurant_id' => $restaurantId,
            'name' => 'Livreur realtime',
            'user_name' => 'livreur-realtime',
            'hourly_pay' => 0,
            'email' => 'livreur-realtime@example.com',
            'cnic' => 'CNIC-RT-FOOD-001',
            'password' => bcrypt('secret'),
            'phone' => '0500012001',
            'image' => null,
            'address' => 'Brazzaville',
            'latitude' => -4.2635,
            'longitude' => 15.2430,
            'status' => 'online',
            'approved' => true,
        ]);

        $orderId = DB::table('orders')->insertGetId([
            'order_no' => 'FD-RT-BCAST-001',
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
            'status' => 'pending',
            'business_status' => 'accepted',
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

        DriverLocation::create([
            'driver_id' => $driver->id,
            'latitude' => -4.2636,
            'longitude' => 15.2431,
            'speed' => 22,
            'timestamp' => now(),
        ]);

        app(FoodOrderStateMachineService::class)->transitionOrderGroup('FD-RT-BCAST-001', 'driver_assigned', [
            'force' => true,
            'suppress_notifications' => true,
        ]);

        Event::assertDispatched(FoodOrderStatusUpdated::class, function (FoodOrderStatusUpdated $event) {
            $channel = $event->broadcastOn();
            $payload = $event->broadcastWith();

            return $channel instanceof PrivateChannel
                && $channel->name === 'private-food.order.FD-RT-BCAST-001.status'
                && $event->broadcastAs() === 'food.order.status.updated'
                && $payload['order_no'] === 'FD-RT-BCAST-001'
                && $payload['business_status'] === 'driver_assigned'
                && $payload['route_path'] === '/track-order/FD-RT-BCAST-001';
        });

        Event::assertDispatched(FoodRestaurantOrderUpdated::class, function (FoodRestaurantOrderUpdated $event) use ($restaurantId) {
            $channel = $event->broadcastOn();
            $payload = $event->broadcastWith();

            return $channel instanceof PrivateChannel
                && $channel->name === 'private-food.restaurant.' . $restaurantId . '.orders'
                && $event->broadcastAs() === 'food.restaurant.order.updated'
                && $payload['restaurant_id'] === $restaurantId;
        });

        Event::assertDispatched(FoodDriverOrderUpdated::class, function (FoodDriverOrderUpdated $event) use ($driver) {
            $channel = $event->broadcastOn();
            $payload = $event->broadcastWith();

            return $channel instanceof PrivateChannel
                && $channel->name === 'private-food.delivery.' . $driver->id . '.orders'
                && $event->broadcastAs() === 'food.delivery.assignment.updated'
                && $payload['driver_id'] === $driver->id;
        });

        Event::assertDispatched(FoodMissionPresenceUpdated::class, function (FoodMissionPresenceUpdated $event) use ($driver) {
            $channel = $event->broadcastOn();
            $payload = $event->broadcastWith();

            return $channel instanceof PrivateChannel
                && $channel->name === 'private-food.order.FD-RT-BCAST-001.presence'
                && $event->broadcastAs() === 'food.order.presence.updated'
                && $payload['driver_id'] === $driver->id
                && $payload['location']['lat'] === -4.2636
                && $payload['location']['lng'] === 15.2431
                && $payload['presence_state'] === 'live'
                && $payload['presence_stale_after_seconds'] === 120
                && !empty($payload['presence_expires_at']);
        });
    }

    public function test_shipment_state_machine_dispatches_realtime_status_event(): void
    {
        Event::fake([ShipmentStatusUpdated::class, ShipmentMissionPresenceUpdated::class]);

        $customer = User::factory()->create(['type' => 'user']);
        $restaurantOwner = User::factory()->create(['type' => 'restaurant']);
        $restaurantId = DB::table('restaurants')->insertGetId([
            'user_id' => $restaurantOwner->id,
            'name' => 'Hub colis realtime',
            'user_name' => 'hub-colis-realtime',
            'email' => 'hub-colis-realtime@example.com',
            'password' => bcrypt('secret'),
            'services' => 'colis',
            'delivery_charges' => 0,
            'city' => 'Brazzaville',
            'tax' => 0,
            'address' => 'Centre ville',
            'latitude' => -4.2634,
            'longitude' => 15.2429,
            'phone' => '0600012002',
            'description' => 'Hub',
            'min_order' => 0,
            'admin_commission' => 0,
            'approved' => 1,
            'featured' => 0,
            'account_name' => 'Hub realtime colis',
            'account_number' => 'ACC-COLIS-RT',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $courier = Driver::create([
            'restaurant_id' => $restaurantId,
            'name' => 'Coursier realtime',
            'user_name' => 'coursier-realtime',
            'hourly_pay' => 0,
            'email' => 'coursier-realtime@example.com',
            'cnic' => 'CNIC-RT-COLIS-001',
            'password' => bcrypt('secret'),
            'phone' => '0500012002',
            'image' => null,
            'address' => 'Brazzaville',
            'latitude' => -4.2635,
            'longitude' => 15.2430,
            'status' => 'online',
            'approved' => true,
        ]);

        $shipment = Shipment::factory()->create([
            'customer_id' => $customer->id,
            'assigned_courier_id' => $courier->id,
            'tracking_number' => 'BD-CG-202604-RTB01',
            'status' => ShipmentStatus::CREATED,
        ]);

        DriverLocation::create([
            'driver_id' => $courier->id,
            'latitude' => -4.2640,
            'longitude' => 15.2440,
            'speed' => 18,
            'timestamp' => now(),
        ]);

        app(ShipmentStateMachine::class)->transitionTo($shipment, ShipmentStatus::PAID, [
            'actor_type' => 'system',
            'actor_id' => null,
        ]);

        Event::assertDispatched(ShipmentStatusUpdated::class, function (ShipmentStatusUpdated $event) use ($shipment, $courier) {
            $channel = $event->broadcastOn();
            $payload = $event->broadcastWith();

            return $channel instanceof PrivateChannel
                && $channel->name === 'private-colis.shipment.' . $shipment->id . '.status'
                && $event->broadcastAs() === 'colis.shipment.status.updated'
                && $payload['tracking_number'] === 'BD-CG-202604-RTB01'
                && $payload['status'] === ShipmentStatus::PAID->value
                && $payload['assigned_courier_id'] === $courier->id
                && $payload['route_path'] === '/mes-colis/' . $shipment->id;
        });

        Event::assertDispatched(ShipmentMissionPresenceUpdated::class, function (ShipmentMissionPresenceUpdated $event) use ($shipment, $courier) {
            $channel = $event->broadcastOn();
            $payload = $event->broadcastWith();

            return $channel instanceof PrivateChannel
                && $channel->name === 'private-colis.shipment.' . $shipment->id . '.presence'
                && $event->broadcastAs() === 'colis.shipment.presence.updated'
                && $payload['courier_id'] === $courier->id
                && $payload['location']['lat'] === -4.2640
                && $payload['location']['lng'] === 15.2440
                && $payload['presence_state'] === 'live'
                && $payload['presence_stale_after_seconds'] === 120
                && !empty($payload['presence_expires_at']);
        });
    }

    public function test_transport_service_dispatches_status_and_presence_events(): void
    {
        Event::fake([
            TransportBookingStatusUpdated::class,
            TransportMissionPresenceUpdated::class,
        ]);

        $customer = User::factory()->create(['type' => 'user']);
        $owner = User::factory()->create(['type' => 'driver']);

        $restaurantId = DB::table('restaurants')->insertGetId([
            'user_id' => $owner->id,
            'name' => 'Hub kende realtime',
            'user_name' => 'hub-kende-realtime',
            'email' => 'hub-kende-realtime@example.com',
            'password' => bcrypt('secret'),
            'services' => 'transport',
            'delivery_charges' => 0,
            'city' => 'Brazzaville',
            'tax' => 0,
            'address' => 'Centre ville',
            'latitude' => -4.2634,
            'longitude' => 15.2429,
            'phone' => '0600012003',
            'description' => 'Hub',
            'min_order' => 0,
            'admin_commission' => 0,
            'approved' => 1,
            'featured' => 0,
            'account_name' => 'Hub kende realtime',
            'account_number' => 'ACC-KENDE-RT',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $driver = Driver::create([
            'restaurant_id' => $restaurantId,
            'name' => 'Chauffeur realtime',
            'user_name' => 'chauffeur-realtime',
            'hourly_pay' => 0,
            'email' => 'chauffeur-realtime@example.com',
            'cnic' => 'CNIC-RT-TR-001',
            'password' => bcrypt('secret'),
            'phone' => '0500012003',
            'image' => null,
            'address' => 'Brazzaville',
            'latitude' => -4.2635,
            'longitude' => 15.2430,
            'status' => 'online',
            'approved' => true,
        ]);

        $booking = \App\Domain\Transport\Models\TransportBooking::factory()->create([
            'user_id' => $customer->id,
            'driver_id' => $driver->id,
            'status' => TransportStatus::ASSIGNED,
        ]);

        $booking->trackingPoints()->create([
            'lat' => -4.2650,
            'lng' => 15.2460,
            'speed' => 28,
            'recorded_at' => now(),
        ]);

        app(\App\Domain\Transport\Services\TransportService::class)->updateStatus($booking, TransportStatus::DRIVER_ARRIVING);

        Event::assertDispatched(TransportBookingStatusUpdated::class, function (TransportBookingStatusUpdated $event) use ($booking, $driver) {
            $channel = $event->broadcastOn();
            $payload = $event->broadcastWith();

            return $channel instanceof PrivateChannel
                && $channel->name === 'private-transport.booking.' . $booking->uuid . '.status'
                && $event->broadcastAs() === 'transport.booking.status.updated'
                && $payload['status'] === TransportStatus::DRIVER_ARRIVING->value
                && $payload['driver_id'] === $driver->id;
        });

        Event::assertDispatched(TransportMissionPresenceUpdated::class, function (TransportMissionPresenceUpdated $event) use ($booking, $driver) {
            $channel = $event->broadcastOn();
            $payload = $event->broadcastWith();

            return $channel instanceof PrivateChannel
                && $channel->name === 'private-transport.booking.' . $booking->uuid . '.presence'
                && $event->broadcastAs() === 'transport.booking.presence.updated'
                && $payload['driver_id'] === $driver->id
                && $payload['location']['lat'] === -4.2650
                && $payload['location']['lng'] === 15.2460
                && $payload['presence_state'] === 'live'
                && $payload['presence_stale_after_seconds'] === 120
                && !empty($payload['presence_expires_at']);
        });
    }

    public function test_food_presence_broadcast_survives_deleted_order_model(): void
    {
        $customer = User::factory()->create(['type' => 'user']);
        $restaurantOwner = User::factory()->create(['type' => 'restaurant']);
        $restaurantId = DB::table('restaurants')->insertGetId([
            'user_id' => $restaurantOwner->id,
            'name' => 'Restaurant stale food presence',
            'user_name' => 'restaurant-stale-food-presence',
            'email' => 'restaurant-stale-food-presence@example.com',
            'password' => bcrypt('secret'),
            'services' => 'food',
            'delivery_charges' => 1000,
            'city' => 'Brazzaville',
            'tax' => 5,
            'address' => 'Avenue de la Paix',
            'latitude' => -4.2634,
            'longitude' => 15.2429,
            'phone' => '0600099001',
            'description' => 'Test',
            'min_order' => 1000,
            'admin_commission' => 5,
            'approved' => 1,
            'featured' => 0,
            'account_name' => 'Restaurant stale food presence',
            'account_number' => 'ACC-STALE-FOOD',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $orderId = DB::table('orders')->insertGetId([
            'order_no' => 'FD-STALE-BCAST-001',
            'user_id' => $customer->id,
            'restaurant_id' => $restaurantId,
            'driver_id' => null,
            'total_items' => 1,
            'offer_discount' => 0,
            'tax' => 0,
            'delivery_charges' => 1000,
            'sub_total' => 3000,
            'total' => 4000,
            'admin_commission' => 0,
            'restaurant_commission' => 0,
            'driver_tip' => 0,
            'status' => 'pending',
            'business_status' => 'accepted',
            'delivery_address' => 'Avenue de la Paix',
            'd_lat' => '-4.27',
            'd_lng' => '15.28',
            'ordered_time' => now(),
            'delivered_time' => now(),
            'fulfillment_mode' => 'delivery',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $event = new FoodMissionPresenceUpdated(Order::findOrFail($orderId));

        DB::table('orders')->where('id', $orderId)->delete();

        $restored = unserialize(serialize($event));
        $channel = $restored->broadcastOn();
        $payload = $restored->broadcastWith();

        $this->assertInstanceOf(PrivateChannel::class, $channel);
        $this->assertSame('private-food.order.FD-STALE-BCAST-001.presence', $channel->name);
        $this->assertSame('food.order.presence.updated', $restored->broadcastAs());
        $this->assertFalse($payload['entity_exists']);
        $this->assertFalse($payload['is_live']);
        $this->assertSame('offline', $payload['presence_state']);
        $this->assertNull($payload['location']['lat']);
        $this->assertNull($payload['location']['lng']);
    }

    public function test_shipment_presence_broadcast_survives_deleted_shipment_model(): void
    {
        $customer = User::factory()->create(['type' => 'user']);
        $shipment = Shipment::factory()->create([
            'customer_id' => $customer->id,
            'tracking_number' => 'BD-CG-STALE-001',
            'status' => ShipmentStatus::CREATED,
        ]);

        $event = new ShipmentMissionPresenceUpdated($shipment);

        $shipment->delete();

        $restored = unserialize(serialize($event));
        $channel = $restored->broadcastOn();
        $payload = $restored->broadcastWith();

        $this->assertInstanceOf(PrivateChannel::class, $channel);
        $this->assertSame('private-colis.shipment.' . $shipment->id . '.presence', $channel->name);
        $this->assertSame('colis.shipment.presence.updated', $restored->broadcastAs());
        $this->assertFalse($payload['entity_exists']);
        $this->assertFalse($payload['is_live']);
        $this->assertSame('offline', $payload['presence_state']);
        $this->assertSame('BD-CG-STALE-001', $payload['tracking_number']);
        $this->assertNull($payload['location']['lat']);
        $this->assertNull($payload['location']['lng']);
    }

    public function test_transport_presence_broadcast_survives_deleted_booking_model(): void
    {
        $customer = User::factory()->create(['type' => 'user']);
        $booking = \App\Domain\Transport\Models\TransportBooking::factory()->create([
            'user_id' => $customer->id,
            'status' => TransportStatus::ASSIGNED,
        ]);

        $event = new TransportMissionPresenceUpdated($booking);

        $booking->delete();

        $restored = unserialize(serialize($event));
        $channel = $restored->broadcastOn();
        $payload = $restored->broadcastWith();

        $this->assertInstanceOf(PrivateChannel::class, $channel);
        $this->assertSame('private-transport.booking.' . $booking->uuid . '.presence', $channel->name);
        $this->assertSame('transport.booking.presence.updated', $restored->broadcastAs());
        $this->assertFalse($payload['entity_exists']);
        $this->assertFalse($payload['is_live']);
        $this->assertSame('offline', $payload['presence_state']);
        $this->assertSame($booking->uuid, $payload['booking_uuid']);
        $this->assertNull($payload['location']['lat']);
        $this->assertNull($payload['location']['lng']);
    }

}
