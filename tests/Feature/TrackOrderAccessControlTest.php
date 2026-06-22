<?php

namespace Tests\Feature;

use App\Delivery;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TrackOrderAccessControlTest extends TestCase
{
    use RefreshDatabase;

    private function createTestOrder(string $orderNo): array
    {
        $owner = User::factory()->create(['type' => 'user', 'phone' => '0600099001']);

        $restUserId = User::factory()->create(['type' => 'restaurant', 'phone' => '0600099002'])->id;
        $restaurantId = DB::table('restaurants')->insertGetId([
            'user_id'          => $restUserId,
            'name'             => 'Track Access Restaurant',
            'user_name'        => 'track-access-restaurant',
            'email'            => 'track-access@example.com',
            'password'         => bcrypt('secret'),
            'services'         => 'both',
            'service_charges'  => 0,
            'delivery_charges' => 500,
            'city'             => 'Brazzaville',
            'tax'              => 0,
            'address'          => 'Adresse test',
            'phone'            => '0600099003',
            'admin_commission' => 10,
            'approved'         => 1,
            'featured'         => 0,
            'account_name'     => 'Track Access Restaurant',
            'account_number'   => 'REST-TRACK-001',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $driverId = DB::table('drivers')->insertGetId([
            'restaurant_id' => $restaurantId,
            'name'          => 'Driver Track Access',
            'user_name'     => 'driver-track-access',
            'phone'         => '0600099004',
            'email'         => 'driver-track-access@example.com',
            'password'      => bcrypt('secret'),
            'hourly_pay'    => 0,
            'address'       => 'Adresse driver',
            'cnic'          => 'CNIC-TRACK-001',
            'approved'      => 1,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        $orderId = DB::table('orders')->insertGetId([
            'order_no'              => $orderNo,
            'user_id'               => $owner->id,
            'restaurant_id'         => $restaurantId,
            'driver_id'             => $driverId,
            'qty'                   => 2,
            'price'                 => 3000,
            'total_items'           => 2,
            'offer_discount'        => 0,
            'tax'                   => 0,
            'delivery_charges'      => 500,
            'sub_total'             => 6000,
            'total'                 => 6500,
            'admin_commission'      => 0,
            'restaurant_commission' => 0,
            'driver_tip'            => 0,
            'status'                => 'assign',
            'business_status'       => 'out_for_delivery',
            'payment_method'        => 'cash',
            'payment_status'        => 'pending',
            'delivery_address'      => 'Poto-Poto, Brazzaville',
            'scheduled_date'        => null,
            'd_lat'                 => '-4.27',
            'd_lng'                 => '15.28',
            'ordered_time'          => now()->subHour(),
            'delivered_time'        => null,
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);

        Delivery::create([
            'order_id'                => $orderId,
            'restaurant_id'           => $restaurantId,
            'driver_id'               => $driverId,
            'status'                  => 'ON_THE_WAY',
            'delivery_fee'            => 500,
            'delivery_otp_code'       => '7777',
            'delivery_otp_expires_at' => now()->addHour(),
            'assigned_at'             => now()->subMinutes(30),
            'picked_up_at'            => now()->subMinutes(15),
        ]);

        return [$owner, $orderNo];
    }

    public function test_anonymous_user_is_redirected_to_login(): void
    {
        [, $orderNo] = $this->createTestOrder('TD-SEC-0001');

        $response = $this->get(route('track.order', ['orderNo' => $orderNo]));

        $response->assertRedirect(route('login'));
    }

    public function test_anonymous_user_never_sees_order_pii(): void
    {
        [, $orderNo] = $this->createTestOrder('TD-SEC-0002');

        $response = $this->get(route('track.order', ['orderNo' => $orderNo]));

        $response->assertDontSee('Poto-Poto, Brazzaville', false);
        $response->assertDontSee('0600099004', false);
        $response->assertDontSee('7777', false);
    }

    public function test_owner_can_view_own_order(): void
    {
        [$owner, $orderNo] = $this->createTestOrder('TD-SEC-0003');

        $response = $this->actingAs($owner)
            ->get(route('track.order', ['orderNo' => $orderNo]));

        $response->assertStatus(200);
    }

    public function test_non_owner_is_forbidden(): void
    {
        [, $orderNo] = $this->createTestOrder('TD-SEC-0004');
        $intruder = User::factory()->create(['type' => 'user', 'phone' => '0600099099']);

        $response = $this->actingAs($intruder)
            ->get(route('track.order', ['orderNo' => $orderNo]));

        $response->assertStatus(403);
    }
}
