<?php

namespace Tests\Feature\Admin;

use App\Order;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CashCollectionAdminTest extends TestCase
{
    use RefreshDatabase;

    private int $restaurantId;
    private User $admin;
    private User $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['type' => 'admin']);
        $this->client = User::factory()->create(['type' => 'user']);
        $restaurantUser = User::factory()->create(['type' => 'restaurant']);

        $this->restaurantId = DB::table('restaurants')->insertGetId([
            'user_id' => $restaurantUser->id,
            'name' => 'Restaurant Cash Test',
            'user_name' => 'restaurant-cash-test',
            'email' => 'cash-test@example.com',
            'password' => bcrypt('secret'),
            'services' => 'food',
            'delivery_charges' => 500,
            'city' => 'Brazzaville',
            'tax' => 0,
            'address' => 'Adresse restaurant',
            'phone' => '0600033333',
            'admin_commission' => 10,
            'approved' => 1,
            'account_name' => 'Restaurant Cash Test',
            'account_number' => 'REST-CASH-001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_admin_can_filter_cash_collection_statuses(): void
    {
        $this->createOrder('TD-CASH-0001', 'cash', 'pending_collection');
        $this->createOrder('TD-CASH-0002', 'cash', 'collected');
        $this->createOrder('TD-ONLINE-0001', 'paypal', null);

        $response = $this->actingAs($this->admin)
            ->withoutMiddleware()
            ->get(route('admin.cash_collections.index', ['status' => 'pending_collection']));

        $response->assertOk();
        $response->assertViewHas('orders', function ($orders): bool {
            return $orders->getCollection()->pluck('order_no')->all() === ['TD-CASH-0001'];
        });
    }

    public function test_admin_can_export_filtered_cash_collections_without_client_contact_data(): void
    {
        $this->client->update(['name' => 'Client Export', 'phone' => '0699999999']);
        $this->createOrder('TD-CASH-EXPORT', 'cash', 'collected');

        $response = $this->actingAs($this->admin)
            ->withoutMiddleware()
            ->get(route('admin.cash_collections.export', ['status' => 'collected']));

        $response->assertOk();
        $content = $response->streamedContent();

        $this->assertStringContainsString('TD-CASH-EXPORT', $content);
        $this->assertStringContainsString('Restaurant Cash Test', $content);
        $this->assertStringNotContainsString('0699999999', $content);
    }

    private function createOrder(string $orderNo, string $paymentMethod, ?string $cashStatus): Order
    {
        $id = DB::table('orders')->insertGetId([
            'order_no' => $orderNo,
            'user_id' => $this->client->id,
            'restaurant_id' => $this->restaurantId,
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
            'status' => 'completed',
            'business_status' => 'delivered',
            'payment_method' => $paymentMethod,
            'payment_status' => $paymentMethod === 'cash' ? 'paid' : 'completed',
            'cash_collection_status' => $cashStatus,
            'cash_collected_at' => $cashStatus === 'collected' ? now() : null,
            'cash_collection_confirmed_at' => $cashStatus === 'collected' ? now() : null,
            'delivery_address' => 'Adresse client confidentielle',
            'd_lat' => '-4.27',
            'd_lng' => '15.28',
            'ordered_time' => now()->subHour(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Order::findOrFail($id);
    }
}
