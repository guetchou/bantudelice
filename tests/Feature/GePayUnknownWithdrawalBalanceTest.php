<?php

namespace Tests\Feature;

use App\Restaurant;
use App\Services\PartnerFinancialDashboardService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class GePayUnknownWithdrawalBalanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_withdrawal_remains_in_pending_balance(): void
    {
        $user = User::factory()->create([
            'type' => 'restaurant',
            'phone' => '0600008001',
        ]);

        $restaurantId = DB::table('restaurants')->insertGetId([
            'user_id' => $user->id,
            'name' => 'Restaurant GePay Unknown',
            'user_name' => 'restaurant-gepay-unknown',
            'email' => 'restaurant-gepay-unknown@example.com',
            'password' => bcrypt('secret'),
            'slogan' => 'Test',
            'logo' => null,
            'cover_image' => null,
            'services' => 'both',
            'service_charges' => 0,
            'delivery_charges' => 0,
            'city' => 'Brazzaville',
            'tax' => 0,
            'address' => 'Adresse test',
            'latitude' => null,
            'longitude' => null,
            'phone' => '0600008002',
            'description' => null,
            'min_order' => 0,
            'avg_delivery_time' => null,
            'delivery_range' => null,
            'admin_commission' => 0,
            'approved' => 1,
            'featured' => 0,
            'account_name' => 'Restaurant GePay Unknown',
            'account_number' => 'REST-GEPAY-UNKNOWN',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('partner_withdrawals')->insert([
            'uuid' => (string) Str::uuid(),
            'partner_type' => 'restaurant',
            'partner_id' => $restaurantId,
            'operator' => 'mtn',
            'provider' => 'gepay',
            'phone' => '0600008002',
            'requested_amount' => 5000,
            'fee_amount' => 0,
            'net_amount' => 5000,
            'currency' => 'XAF',
            'status' => 'unknown',
            'external_reference' => 'WITHDRAWAL-UNKNOWN-BALANCE',
            'idempotency_key' => 'unknown-balance-key',
            'source' => 'self_service',
            'initiated_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $dashboard = app(PartnerFinancialDashboardService::class)
            ->forRestaurant(Restaurant::findOrFail($restaurantId));

        $this->assertSame(0.0, $dashboard['cards'][4]['amount']);
        $this->assertSame(5000.0, $dashboard['cards'][5]['amount']);
    }
}
