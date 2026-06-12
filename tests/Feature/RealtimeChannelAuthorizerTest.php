<?php

namespace Tests\Feature;

use App\Driver;
use App\Order;
use App\Restaurant;
use App\Services\RealtimeChannelAuthorizer;
use App\User;
use App\Domain\Colis\Models\Shipment;
use App\Domain\Transport\Events\TransportTrackingUpdated;
use App\Domain\Transport\Models\TransportBooking;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RealtimeChannelAuthorizerTest extends TestCase
{
    use RefreshDatabase;

    public function test_food_order_channel_authorizes_customer_restaurant_and_driver_only(): void
    {
        $customer = User::factory()->create(['type' => 'user']);
        $restaurantOwner = User::factory()->create(['type' => 'restaurant']);
        $outsider = User::factory()->create(['type' => 'user']);
        $driverUser = User::factory()->create([
            'type' => 'driver',
            'email' => 'driver-food@example.com',
            'phone' => '0661000001',
            'name' => 'Driver Food',
        ]);

        $restaurantId = DB::table('restaurants')->insertGetId([
            'user_id' => $restaurantOwner->id,
            'name' => 'Restaurant realtime',
            'user_name' => 'restaurant-realtime',
            'email' => 'restaurant-realtime@example.com',
            'password' => bcrypt('secret'),
            'services' => 'food',
            'delivery_charges' => 1000,
            'city' => 'Brazzaville',
            'tax' => 5,
            'address' => 'Avenue de la Paix',
            'latitude' => -4.2634,
            'longitude' => 15.2429,
            'phone' => '0600010001',
            'description' => 'Test',
            'min_order' => 1000,
            'admin_commission' => 5,
            'approved' => 1,
            'featured' => 0,
            'account_name' => 'Restaurant realtime',
            'account_number' => 'ACC-REST-REALTIME',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $driver = Driver::create([
            'restaurant_id' => $restaurantId,
            'name' => 'Driver Food',
            'user_name' => 'driver-food',
            'hourly_pay' => 0,
            'email' => 'driver-food@example.com',
            'cnic' => 'CNIC-REALTIME-1',
            'password' => bcrypt('secret'),
            'phone' => '0661000001',
            'image' => null,
            'address' => 'Brazzaville',
            'latitude' => -4.2635,
            'longitude' => 15.2430,
            'status' => 'online',
            'approved' => true,
        ]);

        $orderId = DB::table('orders')->insertGetId([
            'order_no' => 'FD-RT-001',
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
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $order = Order::query()->findOrFail($orderId);

        $authorizer = app(RealtimeChannelAuthorizer::class);

        $this->assertTrue($authorizer->canAccessFoodOrderStatus($customer, $order->order_no));
        $this->assertTrue($authorizer->canAccessFoodOrderStatus($restaurantOwner, $order->order_no));
        $this->assertTrue($authorizer->canAccessFoodOrderStatus($driverUser, $order->order_no));
        $this->assertFalse($authorizer->canAccessFoodOrderStatus($outsider, $order->order_no));
    }

    public function test_transport_and_colis_channels_authorize_only_owner_or_assigned_driver(): void
    {
        $customer = User::factory()->create(['type' => 'user']);
        $driverUser = User::factory()->create([
            'type' => 'driver',
            'email' => 'driver-kende@example.com',
            'phone' => '0661000002',
            'name' => 'Driver Kende',
        ]);
        $outsider = User::factory()->create(['type' => 'user']);
        $hubOwner = User::factory()->create(['type' => 'restaurant']);

        $restaurantId = DB::table('restaurants')->insertGetId([
            'user_id' => $hubOwner->id,
            'name' => 'Hub realtime',
            'user_name' => 'hub-realtime',
            'email' => 'hub-realtime@example.com',
            'password' => bcrypt('secret'),
            'services' => 'transport',
            'delivery_charges' => 0,
            'city' => 'Brazzaville',
            'tax' => 0,
            'address' => 'Centre ville',
            'latitude' => -4.2634,
            'longitude' => 15.2429,
            'phone' => '0600010002',
            'description' => 'Hub',
            'min_order' => 0,
            'admin_commission' => 0,
            'approved' => 1,
            'featured' => 0,
            'account_name' => 'Hub',
            'account_number' => 'ACC-HUB-REALTIME',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $driver = Driver::create([
            'restaurant_id' => $restaurantId,
            'name' => 'Driver Kende',
            'user_name' => 'driver-kende',
            'hourly_pay' => 0,
            'email' => 'driver-kende@example.com',
            'cnic' => 'CNIC-REALTIME-2',
            'password' => bcrypt('secret'),
            'phone' => '0661000002',
            'image' => null,
            'address' => 'Brazzaville',
            'latitude' => -4.2635,
            'longitude' => 15.2430,
            'status' => 'online',
            'approved' => true,
        ]);

        $booking = TransportBooking::factory()->create([
            'user_id' => $customer->id,
            'driver_id' => $driver->id,
        ]);

        $shipment = Shipment::factory()->create([
            'customer_id' => $customer->id,
            'assigned_courier_id' => $driver->id,
        ]);

        $authorizer = app(RealtimeChannelAuthorizer::class);

        $this->assertTrue($authorizer->canAccessTransportBooking($customer, $booking->uuid));
        $this->assertTrue($authorizer->canAccessTransportBooking($driverUser, $booking->uuid));
        $this->assertFalse($authorizer->canAccessTransportBooking($outsider, $booking->uuid));
        $this->assertTrue($authorizer->canAccessTransportDriverRequests($driverUser, $driver->id));
        $this->assertFalse($authorizer->canAccessTransportDriverRequests($outsider, $driver->id));

        $this->assertTrue($authorizer->canAccessColisShipment($customer, $shipment->id));
        $this->assertTrue($authorizer->canAccessColisShipment($driverUser, $shipment->id));
        $this->assertFalse($authorizer->canAccessColisShipment($outsider, $shipment->id));
    }

    public function test_transport_tracking_event_uses_private_channel(): void
    {
        $booking = TransportBooking::factory()->make([
            'uuid' => 'booking-private-test',
        ]);

        $event = new TransportTrackingUpdated($booking, -4.2634, 15.2429, 30);
        $channel = $event->broadcastOn();

        $this->assertInstanceOf(PrivateChannel::class, $channel);
        $this->assertSame('private-transport.booking.booking-private-test.tracking', $channel->name);
    }
}
