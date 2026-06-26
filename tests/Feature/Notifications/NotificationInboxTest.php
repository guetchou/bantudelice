<?php

namespace Tests\Feature\Notifications;

use App\Domain\Food\Enums\OrderPaymentStatus;
use App\NotificationLog;
use App\Order;
use App\Services\NotificationLogService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NotificationInboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_event_key_is_logged_only_once(): void
    {
        $user = User::factory()->create(['type' => 'user']);
        $payload = [
            'channel' => 'push',
            'recipient_type' => 'user',
            'recipient_id' => $user->id,
            'title' => 'Commande annulée',
            'body' => 'Votre commande #TD-DEDUP-001 a été annulée.',
            'provider' => 'fcm',
            'status' => 'no_tokens',
            'context' => [
                'module' => 'food',
                'order_no' => 'TD-DEDUP-001',
                'dedup_key' => 'food:user:cancelled:TD-DEDUP-001',
            ],
        ];

        $service = app(NotificationLogService::class);
        $service->record($payload);
        $service->record($payload);

        $this->assertSame(1, NotificationLog::where('recipient_id', $user->id)->count());
    }

    public function test_payment_timeout_emits_one_detailed_customer_notification(): void
    {
        config(['food.payment_failed_hold_timeout_minutes' => 10]);

        $client = User::factory()->create(['type' => 'user']);
        $restaurantUser = User::factory()->create(['type' => 'restaurant']);
        $restaurantId = DB::table('restaurants')->insertGetId([
            'user_id' => $restaurantUser->id,
            'name' => 'Restaurant Notifications',
            'user_name' => 'restaurant-notifications',
            'email' => 'notifications@example.com',
            'password' => bcrypt('secret'),
            'services' => 'food',
            'delivery_charges' => 500,
            'city' => 'Brazzaville',
            'tax' => 0,
            'address' => 'Brazzaville',
            'phone' => '0500000000',
            'admin_commission' => 10,
            'approved' => 1,
            'account_name' => 'Restaurant Notifications',
            'account_number' => 'REST-NOTIF-001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $orderNo = 'TD-TIMEOUT-001';
        DB::table('orders')->insert([
            'order_no' => $orderNo,
            'user_id' => $client->id,
            'restaurant_id' => $restaurantId,
            'qty' => 1,
            'price' => 3000,
            'total_items' => 1,
            'offer_discount' => 0,
            'tax' => 0,
            'delivery_charges' => 500,
            'sub_total' => 3000,
            'total' => 3500,
            'admin_commission' => 0,
            'restaurant_commission' => 0,
            'driver_tip' => 0,
            'status' => 'pending',
            'business_status' => 'accepted_awaiting_payment',
            'fulfillment_mode' => 'delivery',
            'payment_method' => 'momo',
            'payment_status' => 'pending',
            'delivery_address' => 'Adresse client',
            'd_lat' => '-4.27',
            'd_lng' => '15.28',
            'accepted_at' => now()->subMinutes(20),
            'ordered_time' => now()->subMinutes(30),
            'created_at' => now()->subMinutes(30),
            'updated_at' => now()->subMinutes(20),
        ]);

        $this->artisan('food:expire-unpaid-accepted')->assertExitCode(0);

        $order = Order::where('order_no', $orderNo)->firstOrFail();
        $this->assertSame('cancelled', $order->business_status);
        $this->assertSame(OrderPaymentStatus::EXPIRED->value, $order->payment_status);

        $notifications = NotificationLog::where('recipient_type', 'user')
            ->where('recipient_id', $client->id)
            ->where('title', 'Commande annulée')
            ->get();

        $this->assertCount(1, $notifications);
        $this->assertStringContainsString('paiement n\'a pas été finalisé à temps', $notifications->first()->body);
        $this->assertSame($orderNo, $notifications->first()->orderNo());
        $this->assertNotNull($notifications->first()->routePath());
    }
}
