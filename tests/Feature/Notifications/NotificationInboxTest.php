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

    public function test_historical_generic_timeout_duplicate_is_archived_not_deleted(): void
    {
        $user = User::factory()->create(['type' => 'user']);
        $orderNo = 'TD-HISTORY-001';
        $createdAt = now()->subMinute();

        $genericId = DB::table('notification_logs')->insertGetId([
            'channel' => 'push',
            'recipient_type' => 'user',
            'recipient_id' => $user->id,
            'title' => 'Commande annulée',
            'body' => 'La commande #' . $orderNo . ' a été annulée.',
            'provider' => 'fcm',
            'status' => 'no_tokens',
            'context' => json_encode(['module' => 'food', 'order_no' => $orderNo]),
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        $detailedId = DB::table('notification_logs')->insertGetId([
            'channel' => 'push',
            'recipient_type' => 'user',
            'recipient_id' => $user->id,
            'title' => 'Commande annulée',
            'body' => 'Votre commande #' . $orderNo . ' a été annulée car le paiement n\'a pas été finalisé à temps.',
            'provider' => 'fcm',
            'status' => 'no_tokens',
            'context' => json_encode(['module' => 'food', 'order_no' => $orderNo]),
            'created_at' => $createdAt->copy()->addSecond(),
            'updated_at' => $createdAt->copy()->addSecond(),
        ]);

        $unrelatedId = DB::table('notification_logs')->insertGetId([
            'channel' => 'push',
            'recipient_type' => 'user',
            'recipient_id' => $user->id,
            'title' => 'Commande annulée',
            'body' => 'La commande #TD-OTHER-001 a été annulée.',
            'provider' => 'fcm',
            'status' => 'no_tokens',
            'context' => json_encode(['module' => 'food', 'order_no' => 'TD-OTHER-001']),
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        $migration = require database_path('migrations/2026_06_25_235900_archive_duplicate_food_payment_timeout_notifications.php');
        $migration->up();

        $this->assertNotNull(DB::table('notification_logs')->where('id', $genericId)->value('archived_at'));
        $this->assertNull(DB::table('notification_logs')->where('id', $detailedId)->value('archived_at'));
        $this->assertNull(DB::table('notification_logs')->where('id', $unrelatedId)->value('archived_at'));
        $this->assertDatabaseHas('notification_logs', ['id' => $genericId]);
    }

    public function test_payment_timeout_emits_one_detailed_customer_notification(): void
    {
        config(['food.payment_failed_hold_timeout_minutes' => 10]);

        [$client, $restaurantId] = $this->createActors();
        $orderNo = 'TD-TIMEOUT-001';
        $this->createOrder($client->id, $restaurantId, $orderNo);

        $this->artisan('food:expire-unpaid-accepted')->assertExitCode(0);

        $order = Order::where('order_no', $orderNo)->firstOrFail();
        $this->assertSame('cancelled', $order->business_status);
        $this->assertSame(OrderPaymentStatus::EXPIRED->value, $order->payment_status);

        $customerNotifications = NotificationLog::query()
            ->where('recipient_type', 'user')
            ->where('recipient_id', $client->id)
            ->where('title', 'Commande annulée')
            ->whereNull('archived_at')
            ->get();

        $this->assertCount(1, $customerNotifications);
        $notification = $customerNotifications->first();
        $this->assertStringContainsString('paiement n\'a pas été finalisé à temps', $notification->body);
        $this->assertSame($orderNo, $notification->orderNo());
        $this->assertNotNull($notification->routePath());
        $this->assertSame('food:user:cancelled:payment_timeout:' . $orderNo, data_get($notification->context, 'dedup_key'));
    }

    public function test_archived_notifications_are_excluded_from_list_and_unread_count(): void
    {
        $user = User::factory()->create(['type' => 'user']);

        NotificationLog::create([
            'channel' => 'push',
            'recipient_type' => 'user',
            'recipient_id' => $user->id,
            'title' => 'Visible',
            'body' => 'Notification visible',
            'context' => ['module' => 'food'],
        ]);

        NotificationLog::create([
            'channel' => 'push',
            'recipient_type' => 'user',
            'recipient_id' => $user->id,
            'title' => 'Archivée',
            'body' => 'Notification masquée',
            'context' => ['module' => 'food'],
            'archived_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('user.notifications'));

        $response->assertOk();
        $response->assertViewHas('unreadCount', 1);
        $response->assertSee('Notification visible');
        $response->assertDontSee('Notification masquée');
    }

    private function createActors(): array
    {
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

        return [$client, $restaurantId];
    }

    private function createOrder(int $userId, int $restaurantId, string $orderNo): void
    {
        DB::table('orders')->insert([
            'order_no' => $orderNo,
            'user_id' => $userId,
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
    }
}
