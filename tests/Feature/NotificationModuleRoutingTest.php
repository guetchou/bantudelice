<?php

namespace Tests\Feature;

use App\Domain\Colis\Enums\ShipmentStatus;
use App\Domain\Colis\Models\Shipment;
use App\Domain\Colis\Services\ShipmentNotificationService;
use App\Domain\Transport\Models\TransportBooking;
use App\Domain\Transport\Services\TransportNotificationService;
use App\Driver;
use App\Order;
use App\Services\NotificationService;
use App\User;
use App\UserToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NotificationModuleRoutingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('external-services.notifications.fcm.user_key', '');
        config()->set('external-services.notifications.fcm.driver_key', '');
        config()->set('external-services.notifications.fcm.restaurant_key', '');
        config()->set('external-services.notifications.fcm.server_key', '');
    }

    public function test_food_notifications_use_food_tracking_path(): void
    {
        $customer = User::factory()->create(['type' => 'user']);
        UserToken::create([
            'user_id' => $customer->id,
            'device_tokens' => 'token-food-user',
            'site_key' => 'main',
            'active' => true,
        ]);

        $restaurantOwner = User::factory()->create(['type' => 'restaurant']);
        $restaurantId = DB::table('restaurants')->insertGetId([
            'user_id' => $restaurantOwner->id,
            'name' => 'Restaurant notif',
            'user_name' => 'restaurant-notif',
            'email' => 'restaurant-notif@example.com',
            'password' => bcrypt('secret'),
            'services' => 'food',
            'delivery_charges' => 1000,
            'city' => 'Brazzaville',
            'tax' => 5,
            'address' => 'Avenue de la Paix',
            'latitude' => -4.2634,
            'longitude' => 15.2429,
            'phone' => '0600001100',
            'description' => 'Test',
            'min_order' => 1000,
            'admin_commission' => 5,
            'approved' => 1,
            'featured' => 0,
            'account_name' => 'Restaurant notif',
            'account_number' => 'ACC-REST-NOTIF',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $driver = Driver::create([
            'restaurant_id' => $restaurantId,
            'name' => 'Livreur food',
            'user_name' => 'livreur-food',
            'hourly_pay' => 0,
            'email' => 'livreur-food@example.com',
            'cnic' => 'CNIC-FOOD-001',
            'password' => bcrypt('secret'),
            'phone' => '0500001100',
            'image' => null,
            'address' => 'Brazzaville',
            'latitude' => -4.2635,
            'longitude' => 15.2430,
            'status' => 'online',
            'approved' => true,
        ]);

        $orderId = DB::table('orders')->insertGetId([
            'order_no' => 'FD-NOTIF-001',
            'user_id' => $customer->id,
            'restaurant_id' => $restaurantId,
            'driver_id' => $driver->id,
            'total_items' => 1,
            'offer_discount' => 0,
            'tax' => 0,
            'delivery_charges' => 1000,
            'sub_total' => 3500,
            'total' => 4500,
            'admin_commission' => 0,
            'restaurant_commission' => 0,
            'driver_tip' => 0,
            'status' => 'pending',
            'business_status' => 'accepted',
            'delivery_address' => 'Avenue de la Paix',
            'd_lat' => '-4.2700',
            'd_lng' => '15.2800',
            'ordered_time' => now(),
            'delivered_time' => now(),
            'fulfillment_mode' => 'delivery',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        /** @var Order $order */
        $order = Order::query()->findOrFail($orderId);
        app(NotificationService::class)->notifyFoodOrderStatusChange($order, 'accepted');

        $log = DB::table('notification_logs')
            ->where('recipient_id', $customer->id)
            ->where('channel', 'push')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);

        $context = json_decode($log->context, true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('food', $context['module']);
        $this->assertSame('/track-order/FD-NOTIF-001', $context['route_path']);
        $this->assertSame('bantudelice://food/orders/FD-NOTIF-001', $context['deep_link']);
        $this->assertSame('food_status', $context['sound_key']);
        $this->assertSame('order_status_soft', $context['audio_cue']);
        $this->assertSame('food.order.FD-NOTIF-001.status', $context['websocket_channel']);
        $this->assertSame('food.order.status.updated', $context['websocket_event']);
        $this->assertSame('food.order.FD-NOTIF-001.presence', $context['presence_channel']);
        $this->assertSame('open_order', $context['actions'][0]['id']);
    }

    public function test_food_notification_is_filtered_out_for_device_subscribed_only_to_transport(): void
    {
        $customer = User::factory()->create(['type' => 'user']);
        UserToken::create([
            'user_id' => $customer->id,
            'device_tokens' => 'token-transport-only',
            'site_key' => 'main',
            'active' => true,
            'metadata' => [
                'subscriptions' => [
                    'modules' => ['transport'],
                    'audio_enabled' => true,
                    'interactive_enabled' => true,
                    'realtime_enabled' => true,
                ],
            ],
        ]);

        $restaurantOwner = User::factory()->create(['type' => 'restaurant']);
        $restaurantId = DB::table('restaurants')->insertGetId([
            'user_id' => $restaurantOwner->id,
            'name' => 'Restaurant filtre notif',
            'user_name' => 'restaurant-filtre-notif',
            'email' => 'restaurant-filtre-notif@example.com',
            'password' => bcrypt('secret'),
            'services' => 'food',
            'delivery_charges' => 1000,
            'city' => 'Brazzaville',
            'tax' => 5,
            'address' => 'Avenue de la Paix',
            'latitude' => -4.2634,
            'longitude' => 15.2429,
            'phone' => '0600001130',
            'description' => 'Test',
            'min_order' => 1000,
            'admin_commission' => 5,
            'approved' => 1,
            'featured' => 0,
            'account_name' => 'Restaurant filtre',
            'account_number' => 'ACC-REST-FILTRE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $driver = Driver::create([
            'restaurant_id' => $restaurantId,
            'name' => 'Livreur filtre',
            'user_name' => 'livreur-filtre',
            'hourly_pay' => 0,
            'email' => 'livreur-filtre@example.com',
            'cnic' => 'CNIC-FOOD-FILTRE',
            'password' => bcrypt('secret'),
            'phone' => '0500001130',
            'image' => null,
            'address' => 'Brazzaville',
            'latitude' => -4.2635,
            'longitude' => 15.2430,
            'status' => 'online',
            'approved' => true,
        ]);

        $orderId = DB::table('orders')->insertGetId([
            'order_no' => 'FD-NOTIF-TRONLY',
            'user_id' => $customer->id,
            'restaurant_id' => $restaurantId,
            'driver_id' => $driver->id,
            'total_items' => 1,
            'offer_discount' => 0,
            'tax' => 0,
            'delivery_charges' => 1000,
            'sub_total' => 3500,
            'total' => 4500,
            'admin_commission' => 0,
            'restaurant_commission' => 0,
            'driver_tip' => 0,
            'status' => 'pending',
            'business_status' => 'accepted',
            'delivery_address' => 'Avenue de la Paix',
            'd_lat' => '-4.2700',
            'd_lng' => '15.2800',
            'ordered_time' => now(),
            'delivered_time' => now(),
            'fulfillment_mode' => 'delivery',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $order = Order::query()->findOrFail($orderId);
        $result = NotificationService::sendToUser($customer->id, 'Commande acceptée', 'Votre commande arrive.', [
            'module' => 'food',
            'channel' => 'user',
            'type' => 'accepted',
            'order_no' => $order->order_no,
            'route_path' => '/track-order/' . $order->order_no,
        ]);

        $this->assertFalse($result['success'] ?? true);
        $this->assertSame('no_tokens', $result['error'] ?? null);
    }

    public function test_transport_notifications_use_kende_booking_path(): void
    {
        $customer = User::factory()->create(['type' => 'user']);
        UserToken::create([
            'user_id' => $customer->id,
            'device_tokens' => 'token-transport-user',
            'site_key' => 'main',
            'active' => true,
        ]);

        $transportOwner = User::factory()->create(['type' => 'driver']);
        $restaurantId = DB::table('restaurants')->insertGetId([
            'user_id' => $transportOwner->id,
            'name' => 'Hub transport notif',
            'user_name' => 'hub-transport-notif',
            'email' => 'hub-transport-notif@example.com',
            'password' => bcrypt('secret'),
            'services' => 'transport',
            'delivery_charges' => 0,
            'city' => 'Brazzaville',
            'tax' => 0,
            'address' => 'Centre ville',
            'latitude' => -4.2634,
            'longitude' => 15.2429,
            'phone' => '0600001200',
            'description' => 'Hub transport',
            'min_order' => 0,
            'admin_commission' => 0,
            'approved' => 1,
            'featured' => 0,
            'account_name' => 'Hub transport',
            'account_number' => 'ACC-TR-NOTIF',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $driver = Driver::create([
            'restaurant_id' => $restaurantId,
            'name' => 'Jean-Paul Mboumba',
            'user_name' => 'jean-paul-mboumba',
            'hourly_pay' => 0,
            'email' => 'jean-paul-mboumba@example.com',
            'cnic' => 'CNIC-TR-001',
            'password' => bcrypt('secret'),
            'phone' => '0500001200',
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

        app(TransportNotificationService::class)->notifyBookingAccepted($booking->fresh());

        $log = DB::table('notification_logs')
            ->where('recipient_id', $customer->id)
            ->where('channel', 'push')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);

        $context = json_decode($log->context, true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('transport', $context['module']);
        $this->assertSame('/transport/booking/' . $booking->uuid, $context['route_path']);
        $this->assertSame('bantudelice://kende/bookings/' . $booking->uuid, $context['deep_link']);
        $this->assertSame('kende_assignment', $context['sound_key']);
        $this->assertSame('driver_assigned_bright', $context['audio_cue']);
        $this->assertSame('transport.booking.' . $booking->uuid . '.status', $context['websocket_channel']);
        $this->assertSame('transport.booking.status.updated', $context['websocket_event']);
        $this->assertSame('transport.booking.' . $booking->uuid . '.tracking', $context['tracking_channel']);
        $this->assertSame('transport.booking.' . $booking->uuid . '.presence', $context['presence_channel']);
        $this->assertSame('open_booking', $context['actions'][0]['id']);
    }

    public function test_delivery_preferences_strip_audio_interactive_and_realtime_fields(): void
    {
        $payload = NotificationService::applyDeliveryPreferences([
            'module' => 'transport',
            'sound_key' => 'kende_assignment',
            'audio_cue' => 'driver_assigned_bright',
            'actions' => [
                ['id' => 'open_booking', 'label' => 'Voir la course', 'path' => '/transport/booking/test'],
            ],
            'websocket_channel' => 'transport.booking.test.status',
            'websocket_event' => 'transport.booking.status.updated',
            'tracking_channel' => 'transport.booking.test.tracking',
            'presence_channel' => 'transport.booking.test.presence',
        ], [
            'subscriptions' => [
                'audio_enabled' => false,
                'interactive_enabled' => false,
                'realtime_enabled' => false,
            ],
        ]);

        $this->assertArrayNotHasKey('sound_key', $payload);
        $this->assertArrayNotHasKey('audio_cue', $payload);
        $this->assertArrayNotHasKey('actions', $payload);
        $this->assertArrayNotHasKey('websocket_channel', $payload);
        $this->assertArrayNotHasKey('websocket_event', $payload);
        $this->assertArrayNotHasKey('tracking_channel', $payload);
        $this->assertArrayNotHasKey('presence_channel', $payload);
        $this->assertSame('transport', $payload['module']);
    }

    public function test_colis_notifications_use_mema_paths(): void
    {
        Mail::fake();

        $customer = User::factory()->create(['type' => 'user']);
        UserToken::create([
            'user_id' => $customer->id,
            'device_tokens' => 'token-colis-user',
            'site_key' => 'main',
            'active' => true,
        ]);

        $shipment = Shipment::factory()->create([
            'customer_id' => $customer->id,
            'tracking_number' => 'BD-CG-202604-ABCDE',
            'status' => ShipmentStatus::IN_TRANSIT,
        ]);

        app(ShipmentNotificationService::class)->notifyStatusChange($shipment->fresh());

        $pushLog = DB::table('notification_logs')
            ->where('recipient_id', $customer->id)
            ->where('channel', 'push')
            ->latest('id')
            ->first();

        $this->assertNotNull($pushLog);

        $pushContext = json_decode($pushLog->context, true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('colis', $pushContext['module']);
        $this->assertSame('/mes-colis/' . $shipment->id, $pushContext['route_path']);
        $this->assertSame('bantudelice://mema/shipments/' . $shipment->id, $pushContext['deep_link']);
        $this->assertSame('mema_status', $pushContext['sound_key']);
        $this->assertSame('shipment_status_soft', $pushContext['audio_cue']);
        $this->assertSame('colis.shipment.' . $shipment->id . '.status', $pushContext['websocket_channel']);
        $this->assertSame('colis.shipment.status.updated', $pushContext['websocket_event']);
        $this->assertSame('colis.shipment.' . $shipment->id . '.presence', $pushContext['presence_channel']);
        $this->assertSame('open_shipment', $pushContext['actions'][0]['id']);

        $smsLog = DB::table('notification_logs')
            ->where('recipient_id', $customer->id)
            ->where('channel', 'sms')
            ->latest('id')
            ->first();

        $this->assertNotNull($smsLog);
        $this->assertStringContainsString('Mema:', $smsLog->body);
    }
}
