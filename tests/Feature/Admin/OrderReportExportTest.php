<?php

namespace Tests\Feature\Admin;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrderReportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_exports_count_a_multi_product_order_once_and_hide_client_data(): void
    {
        $admin = User::factory()->create(['type' => 'admin']);
        $client = User::factory()->create([
            'type' => 'user',
            'name' => 'Client Confidentiel',
            'phone' => '0699999999',
        ]);
        $restaurantUser = User::factory()->create(['type' => 'restaurant']);

        $restaurantId = DB::table('restaurants')->insertGetId([
            'user_id' => $restaurantUser->id,
            'name' => 'Restaurant Export',
            'user_name' => 'restaurant-export',
            'email' => 'restaurant-export@example.com',
            'password' => bcrypt('secret'),
            'services' => 'food',
            'delivery_charges' => 500,
            'city' => 'Brazzaville',
            'tax' => 0,
            'address' => 'Adresse restaurant',
            'phone' => '0500000000',
            'admin_commission' => 10,
            'approved' => 1,
            'account_name' => 'Restaurant Export',
            'account_number' => 'REST-EXPORT-001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->insertOrderLine($client->id, $restaurantId, 3000);
        $this->insertOrderLine($client->id, $restaurantId, 1500);

        $filters = [
            'date_from' => now()->subDay()->format('Y-m-d'),
            'date_to' => now()->addDay()->format('Y-m-d'),
        ];

        $accounting = $this->actingAs($admin)
            ->withoutMiddleware()
            ->get(route('admin.reports.orders.accounting', $filters));

        $accounting->assertOk();
        $accountingCsv = $accounting->streamedContent();
        $this->assertSame(1, substr_count($accountingCsv, 'TD-EXPORT-001'));
        $this->assertStringContainsString('Restaurant Export', $accountingCsv);
        $this->assertStringNotContainsString('Client Confidentiel', $accountingCsv);
        $this->assertStringNotContainsString('0699999999', $accountingCsv);
        $this->assertStringNotContainsString('Adresse client confidentielle', $accountingCsv);

        $commercial = $this->actingAs($admin)
            ->withoutMiddleware()
            ->get(route('admin.reports.orders.commercial', $filters));

        $commercial->assertOk();
        $commercialCsv = $commercial->streamedContent();
        $this->assertStringContainsString('Restaurant Export', $commercialCsv);
        $this->assertStringNotContainsString('TD-EXPORT-001', $commercialCsv);
        $this->assertStringNotContainsString('Client Confidentiel', $commercialCsv);

        $lines = array_values(array_filter(preg_split('/\r\n|\r|\n/', trim($commercialCsv))));
        $this->assertCount(2, $lines);
        $data = str_getcsv($lines[1], ';');
        $this->assertSame('1', $data[2]);
    }

    private function insertOrderLine(int $userId, int $restaurantId, int $price): void
    {
        DB::table('orders')->insert([
            'order_no' => 'TD-EXPORT-001',
            'user_id' => $userId,
            'restaurant_id' => $restaurantId,
            'qty' => 1,
            'price' => $price,
            'total_items' => 2,
            'offer_discount' => 250,
            'tax' => 0,
            'delivery_charges' => 500,
            'sub_total' => 4500,
            'total' => 4750,
            'admin_commission' => 475,
            'restaurant_commission' => 3775,
            'driver_tip' => 0,
            'status' => 'completed',
            'business_status' => 'delivered',
            'fulfillment_mode' => 'delivery',
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'cash_collection_status' => 'collected',
            'cash_collected_at' => now(),
            'delivery_address' => 'Adresse client confidentielle',
            'd_lat' => '-4.27',
            'd_lng' => '15.28',
            'ordered_time' => now()->subHour(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
