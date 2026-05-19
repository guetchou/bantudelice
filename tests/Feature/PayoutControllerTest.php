<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PayoutControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createRestaurantUserWithRestaurant(): array
    {
        $user = User::factory()->create([
            'type' => 'restaurant',
            'phone' => '0600001001',
        ]);

        $restaurantId = DB::table('restaurants')->insertGetId([
            'user_id' => $user->id,
            'name' => 'Restaurant Payout Test',
            'user_name' => 'restaurant-payout-test',
            'email' => 'restaurant-payout@example.com',
            'password' => bcrypt('secret'),
            'slogan' => 'Test',
            'logo' => null,
            'cover_image' => null,
            'services' => 'both',
            'service_charges' => 0,
            'delivery_charges' => 500,
            'city' => 'Brazzaville',
            'tax' => 0,
            'address' => 'Adresse test',
            'latitude' => null,
            'longitude' => null,
            'phone' => '0600001002',
            'description' => null,
            'min_order' => 0,
            'avg_delivery_time' => null,
            'delivery_range' => null,
            'admin_commission' => 10,
            'approved' => 1,
            'featured' => 0,
            'account_name' => 'Restaurant Test',
            'account_number' => 'REST-0001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$user, $restaurantId];
    }

    private function createDriverWithRestaurant(): array
    {
        [, $restaurantId] = $this->createRestaurantUserWithRestaurant();

        $driverId = DB::table('drivers')->insertGetId([
            'restaurant_id' => $restaurantId,
            'name' => 'Driver Payout Test',
            'user_name' => 'driver-payout-test',
            'phone' => '0700001001',
            'email' => 'driver-payout@example.com',
            'image' => null,
            'password' => bcrypt('secret'),
            'hourly_pay' => 0,
            'address' => 'Adresse driver',
            'cnic' => 'CNIC-PAYOUT-1',
            'approved' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$restaurantId, $driverId];
    }

    public function test_admin_can_pay_pending_restaurant_payout(): void
    {
        $admin = User::factory()->create(['type' => 'admin']);
        [, $restaurantId] = $this->createRestaurantUserWithRestaurant();

        $requestId = DB::table('restaurant_payments')->insertGetId([
            'restaurant_id' => $restaurantId,
            'payout_amount' => 12500,
            'transaction_id' => 'PENDING-REST-1',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->from(route('restaurant_payout'))
            ->post(route('restaurant_pay'), [
                'request_id' => $requestId,
                'transaction_id' => 'REST-PAID-20260327',
            ])
            ->assertRedirect(route('restaurant_payout'));

        $this->assertDatabaseHas('restaurant_payments', [
            'id' => $requestId,
            'status' => 'paid',
            'transaction_id' => 'REST-PAID-20260327',
        ]);
    }

    public function test_admin_can_trigger_automatic_restaurant_payout_with_mtn_disbursement(): void
    {
        Cache::flush();

        $admin = User::factory()->create(['type' => 'admin']);
        [, $restaurantId] = $this->createRestaurantUserWithRestaurant();

        $requestId = DB::table('restaurant_payments')->insertGetId([
            'restaurant_id' => $restaurantId,
            'payout_amount' => 12500,
            'transaction_id' => 'PENDING-REST-AUTO',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        config([
            'external-services.payments.mtn_momo.environment' => 'production',
            'external-services.payments.mtn_momo.target_environment' => 'mtncongo',
            'external-services.payments.mtn_momo.base_url.production' => 'https://proxy.momoapi.mtn.com',
            'external-services.payments.mtn_momo.use_callback_header' => false,
            'external-services.payments.mtn_momo.callback_url' => null,
            'external-services.payments.mtn_momo.disbursements.subscription_key' => 'disbursement-sub-key',
            'external-services.payments.mtn_momo.disbursements.api_user' => 'disbursement-api-user',
            'external-services.payments.mtn_momo.disbursements.api_key' => 'disbursement-api-key',
        ]);

        Http::fake([
            'https://proxy.momoapi.mtn.com/disbursement/token/' => Http::response([
                'access_token' => 'token-disbursement',
                'expires_in' => 3600,
            ], 200),
            'https://proxy.momoapi.mtn.com/disbursement/v1_0/accountholder/msisdn/*/active' => Http::response('true', 200, [
                'Content-Type' => 'application/json',
            ]),
            'https://proxy.momoapi.mtn.com/disbursement/v1_0/transfer' => Http::response([], 202),
            'https://proxy.momoapi.mtn.com/disbursement/v1_0/transfer/*' => Http::response([
                'status' => 'SUCCESSFUL',
            ], 200),
        ]);

        $response = $this->actingAs($admin)
            ->from(route('restaurant_payout'))
            ->post(route('restaurant_pay'), [
                'request_id' => $requestId,
            ]);

        $response->assertRedirect(route('restaurant_payout'));
        $response->assertSessionHas('alert', function (array $alert) {
            return ($alert['type'] ?? null) === 'success';
        });

        $payment = DB::table('restaurant_payments')->where('id', $requestId)->first();

        $this->assertNotNull($payment);
        $this->assertSame('paid', $payment->status);
        $this->assertTrue(\Illuminate\Support\Str::isUuid($payment->transaction_id));
    }

    public function test_admin_can_trigger_restaurant_payout_via_disbursement_proxy(): void
    {
        Cache::flush();

        $admin = User::factory()->create(['type' => 'admin']);
        [, $restaurantId] = $this->createRestaurantUserWithRestaurant();

        $requestId = DB::table('restaurant_payments')->insertGetId([
            'restaurant_id' => $restaurantId,
            'payout_amount' => 1000,
            'transaction_id' => 'PENDING-REST-PROXY',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        config([
            'external-services.payments.mtn_momo.environment' => 'production',
            'external-services.payments.mtn_momo.target_environment' => 'mtncongo',
            'external-services.payments.mtn_momo.disbursement_proxy.enabled' => true,
            'external-services.payments.mtn_momo.disbursement_proxy.url' => 'https://164.68.106.186/api/disbursements',
            'external-services.payments.mtn_momo.disbursement_proxy.status_url' => 'https://164.68.106.186/api/disbursements/{reference}',
            'external-services.payments.mtn_momo.disbursement_proxy.token' => 'proxy-token',
            'external-services.payments.mtn_momo.disbursement_proxy.source_ip' => '164.68.106.186',
        ]);

        Http::fake([
            'https://164.68.106.186/api/disbursements' => Http::response([
                'success' => true,
                'provider_reference' => 'proxy-ref-001',
                'status' => 'PENDING',
                'message' => 'Décaissement transmis à la gateway proxy',
            ], 202),
            'https://164.68.106.186/api/disbursements/*' => Http::response([
                'status' => 'SUCCESSFUL',
                'message' => 'Décaissement exécuté par la gateway proxy',
            ], 200),
        ]);

        $response = $this->actingAs($admin)
            ->from(route('restaurant_payout'))
            ->post(route('restaurant_pay'), [
                'request_id' => $requestId,
            ]);

        $response->assertRedirect(route('restaurant_payout'));
        $response->assertSessionHas('alert', function (array $alert) {
            return ($alert['type'] ?? null) === 'success';
        });

        $this->assertDatabaseHas('restaurant_payments', [
            'id' => $requestId,
            'status' => 'paid',
            'transaction_id' => 'proxy-ref-001',
        ]);
    }

    public function test_admin_cannot_repay_already_paid_driver_request(): void
    {
        $admin = User::factory()->create(['type' => 'admin']);
        [, $driverId] = $this->createDriverWithRestaurant();

        $requestId = DB::table('driver_payments')->insertGetId([
            'driver_id' => $driverId,
            'payout_amount' => 8400,
            'transaction_id' => 'DRV-ALREADY-PAID',
            'status' => 'paid',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->from(route('driver_payout'))
            ->post(route('driver_pay'), [
                'request_id' => $requestId,
                'transaction_id' => 'DRV-SHOULD-NOT-CHANGE',
            ]);

        $response->assertRedirect(route('driver_payout'));
        $response->assertSessionHas('alert', function (array $alert) {
            return ($alert['type'] ?? null) === 'danger';
        });

        $this->assertDatabaseHas('driver_payments', [
            'id' => $requestId,
            'status' => 'paid',
            'transaction_id' => 'DRV-ALREADY-PAID',
        ]);
    }

    public function test_restaurant_pay_requires_valid_payload(): void
    {
        $admin = User::factory()->create(['type' => 'admin']);

        $response = $this->actingAs($admin)
            ->from(route('restaurant_payout'))
            ->post(route('restaurant_pay'), [
                'request_id' => 999999,
                'transaction_id' => '',
            ]);

        $response->assertRedirect(route('restaurant_payout'));
        $response->assertSessionHasErrors(['request_id']);
    }

    public function test_admin_can_export_pending_restaurant_payouts_as_bulk_csv(): void
    {
        $admin = User::factory()->create(['type' => 'admin']);
        [, $restaurantId] = $this->createRestaurantUserWithRestaurant();

        DB::table('restaurant_payments')->insert([
            'restaurant_id' => $restaurantId,
            'payout_amount' => 12500,
            'transaction_id' => 'REST-PENDING-BULK-1',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('restaurant_payments')->insert([
            'restaurant_id' => $restaurantId,
            'payout_amount' => 9800,
            'transaction_id' => 'REST-PAID-EXCLUDED',
            'status' => 'paid',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('restaurant_payout.export_csv'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();

        $this->assertStringContainsString('Payee Name', $csv);
        $this->assertStringContainsString('MSISDN', $csv);
        $this->assertStringContainsString('Amount (FCFA)', $csv);
        $this->assertStringContainsString('Restaurant Payout Test', $csv);
        $this->assertStringContainsString('242600001002,12500', $csv);
        $this->assertStringNotContainsString('9800', $csv);
    }

    public function test_admin_can_export_pending_driver_payouts_as_bulk_csv(): void
    {
        $admin = User::factory()->create(['type' => 'admin']);
        [, $driverId] = $this->createDriverWithRestaurant();

        DB::table('driver_payments')->insert([
            'driver_id' => $driverId,
            'payout_amount' => 8400,
            'transaction_id' => 'DRV-PENDING-BULK-1',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('driver_payments')->insert([
            'driver_id' => $driverId,
            'payout_amount' => 9100,
            'transaction_id' => 'DRV-PAID-EXCLUDED',
            'status' => 'paid',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('driver_payout.export_csv'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();

        $this->assertStringContainsString('Payee Name', $csv);
        $this->assertStringContainsString('MSISDN', $csv);
        $this->assertStringContainsString('Amount (FCFA)', $csv);
        $this->assertStringContainsString('Driver Payout Test', $csv);
        $this->assertStringContainsString('242700001001,8400', $csv);
        $this->assertStringNotContainsString('9100', $csv);
    }
}
